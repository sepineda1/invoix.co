<?php

namespace App\Jobs;

use App\Http\Controllers\ApiV1\ApiPresenter;
use App\Models\ApiWebhook;
use App\Models\Contact;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseDocument;
use App\Models\Receipt;
use App\Models\SalesDocument;
use App\Models\WarehouseStock;
use App\Services\Api\Webhooks;
use App\Support\CurrentCompany;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/** Envía un evento firmado (HMAC-SHA256) a la URL de un webhook; reintenta y lo desactiva tras 50 fallas seguidas. */
class DeliverWebhook implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 120, 600, 1800];

    public int $uniqueFor = 30;

    public function __construct(public int $webhookId, public int $companyId, public string $event, public string $model, public int|string $id) {}

    public function uniqueId(): string
    {
        return "{$this->webhookId}|{$this->event}|{$this->model}|{$this->id}";
    }

    public function handle(): void
    {
        CurrentCompany::set($this->companyId);
        $hook = ApiWebhook::query()->find($this->webhookId);
        if (! $hook || ! $hook->active) {
            return;
        }
        if ($error = Webhooks::safeUrl($hook->url)) {
            $hook->forceFill(['last_error' => $error, 'failures' => $hook->failures + 1])->save();

            return;
        }
        $body = json_encode([
            'evento' => $this->event, 'id' => (string) Str::uuid(), 'fecha' => now()->toIso8601String(), 'data' => $this->payload(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $response = Http::timeout(10)->withOptions(['allow_redirects' => false])->withBody($body, 'application/json')->withHeaders([
                'User-Agent' => 'Invoix-Webhooks/1.0',
                'X-Invoix-Evento' => $this->event,
                'X-Invoix-Firma' => 'sha256='.hash_hmac('sha256', $body, $hook->secret),
            ])->post($hook->url);
            $ok = $response->successful();
            $hook->forceFill([
                'last_status' => $response->status(), 'last_sent_at' => now(), 'last_error' => $ok ? null : mb_substr('HTTP '.$response->status().': '.$response->body(), 0, 500),
                'failures' => $ok ? 0 : $hook->failures + 1,
            ])->save();
        } catch (\Throwable $e) {
            $ok = false;
            $hook->forceFill(['last_status' => null, 'last_sent_at' => now(), 'last_error' => mb_substr($e->getMessage(), 0, 500), 'failures' => $hook->failures + 1])->save();
        }
        if (! $ok && $hook->failures >= 50) {
            $hook->forceFill(['active' => false])->save();

            return;
        }
        if (! $ok && $this->job) {
            $this->release($this->backoff[min($this->attempts() - 1, count($this->backoff) - 1)]);
        }
    }

    private function payload(): array
    {
        if ($this->model === 'prueba') {
            return ['mensaje' => 'Evento de prueba desde INVOIX INC.'];
        }
        $m = $this->model::query()->find($this->id);
        if (! $m) {
            return ['id' => $this->id, 'eliminado' => true];
        }

        return match (true) {
            $m instanceof Contact => ApiPresenter::contact($m),
            $m instanceof Product => ApiPresenter::product($m),
            $m instanceof WarehouseStock => ['bodega_id' => $m->warehouse_id, 'cantidad' => (float) $m->quantity,
                'producto' => ($p = Product::query()->find($m->product_id)) ? ApiPresenter::product($p) : ['id' => $m->product_id]],
            $m instanceof SalesDocument => ApiPresenter::sale($m->load('items', 'contact'), true),
            $m instanceof PurchaseDocument => ApiPresenter::purchase($m->load('items', 'contact'), true),
            $m instanceof Receipt => ApiPresenter::receipt($m->load('items', 'contact'), true),
            $m instanceof JournalEntry => ApiPresenter::journal($m->load('lines'), true),
            default => ['id' => $this->id],
        };
    }
}
