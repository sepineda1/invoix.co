<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $doc->name }} - {{ $company['name'] ?? '' }}</title>
    <link rel="icon" href="/brand/favicon.png" type="image/png">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background: #f1f5f9; color: #1e293b; }
        header { background: #fff; border-bottom: 1px solid #e2e8f0; }
        .wrap { max-width: 1000px; margin: 0 auto; padding: 16px; }
        .head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
        h1 { margin: 0; font-size: 18px; }
        .muted { color: #64748b; font-size: 13px; margin-top: 2px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 8px 14px; border-radius: 6px; font-size: 14px; font-weight: 600; text-decoration: none; border: 1px solid #0275b3; color: #0275b3; background: #fff; }
        .btn.primary { background: #0275b3; color: #fff; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin: 16px 0; }
        .card { background: #fff; border-radius: 8px; padding: 12px 16px; border: 1px solid #e2e8f0; }
        .card b { display: block; font-size: 18px; margin-top: 4px; }
        iframe { width: 100%; height: 80vh; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
        footer { text-align: center; color: #64748b; font-size: 12px; padding: 16px; }
    </style>
</head>
<body>
    <header>
        <div class="wrap head">
            <div>
                <h1>{{ $doc->name }}</h1>
                <div class="muted">{{ $company['name'] ?? '' }} · NIT {{ $company['nit'] ?? '' }}</div>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ $downloadUrl }}">Descargar PDF</a>
                @if ($hasXml)
                    <a class="btn" href="{{ $xmlUrl }}">Descargar XML</a>
                @endif
            </div>
        </div>
    </header>
    <main class="wrap">
        <div class="cards">
            <div class="card">Fecha<b>{{ $doc->date->format('Y-m-d') }}</b></div>
            <div class="card">Valor<b>$ {{ number_format((float) $doc->total, 2, ',', '.') }}</b></div>
            @if (in_array($doc->type, ['FV', 'ND'], true))
                <div class="card">Saldo<b>$ {{ number_format((float) $doc->balance, 2, ',', '.') }}</b></div>
            @endif
            @if ($doc->due_date)
                <div class="card">Vencimiento<b>{{ $doc->due_date->format('Y-m-d') }}</b></div>
            @endif
        </div>
        <iframe src="{{ $pdfUrl }}" title="{{ $doc->name }}"></iframe>
    </main>
    <footer>Documento emitido por {{ $company['name'] ?? 'el facturador' }}. Para reclamos sobre este documento comunícate directamente con el emisor.</footer>
</body>
</html>
