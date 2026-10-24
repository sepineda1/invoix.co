<?php

namespace App\Services\Backup;

use App\Models\Backup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/** Genera el ZIP de respaldo (datos en JSON por tabla + archivos), cifrado con AES-256 y la contraseña elegida. */
class BackupExporter
{
    public function export(Backup $backup, string $password): void
    {
        $codec = SecretCodec::current();
        $disk = Storage::disk(config('backup.disk'));
        $tmp = storage_path('app/tmp/backup-'.$backup->id.'-'.bin2hex(random_bytes(4)));
        File::ensureDirectoryExists($tmp.'/data');
        $relative = 'backups/nubicta-respaldo-'.now()->format('Ymd-His').'-'.$backup->id.'.zip';
        $zipPath = $disk->path($relative);
        File::ensureDirectoryExists(dirname($zipPath));

        try {
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo crear el archivo de respaldo.');
            }
            $add = function (string $name, string $source = null, string $contents = null) use ($zip, $password) {
                $source !== null ? $zip->addFile($source, $name) : $zip->addFromString($name, $contents);
                $zip->setEncryptionName($name, ZipArchive::EM_AES_256, $password);
            };

            $tables = [];
            foreach ($this->tables() as $table) {
                $file = "$tmp/data/$table.jsonl";
                $tables[$table] = ['rows' => $this->dumpTable($table, $file, $codec), 'columns' => $this->columns($table)];
                $add("data/$table.jsonl", $file);
            }

            $files = [];
            foreach ($disk->allFiles() as $path) {
                if ($this->skipped($path)) {
                    continue;
                }
                $contents = $disk->get($path);
                $encrypted = strlen($contents) < 20_000_000 && str_starts_with($contents, 'eyJpdiI6') && SecretCodec::isPayload(trim($contents));
                if ($encrypted) {
                    try {
                        $contents = $codec->open(trim($contents));
                    } catch (\Throwable) {
                        $encrypted = false;
                    }
                }
                $add("files/$path", null, $contents);
                $files[] = ['path' => $path, 'encrypted' => $encrypted];
            }

            $public = [];
            foreach (DB::table('companies')->whereNotNull('logo_path')->pluck('logo_path') as $path) {
                if (Storage::disk('public')->exists($path)) {
                    $add('public/'.$path, Storage::disk('public')->path($path));
                    $public[] = $path;
                }
            }

            $company = DB::table('companies')->orderBy('id')->first(['business_name', 'nit']);
            $manifest = [
                'app' => 'nubicta', 'format' => config('backup.format'), 'edition' => config('backup.edition'), 'created_at' => now()->toIso8601String(),
                'company' => ['name' => $company?->business_name, 'nit' => $company?->nit],
                'migrations' => DB::table('migrations')->orderBy('id')->pluck('migration'),
                'tables' => $tables, 'files' => $files, 'public' => $public,
            ];
            $add('manifest.json', null, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $zip->addFromString('LEEME.txt', "Respaldo de OYARCEGROUP INC. Contiene toda la información de la empresa {$company?->business_name}.\n"
                ."Los archivos están cifrados (AES-256) con la contraseña que elegiste al generarlo.\n"
                ."Para restaurarlo: Configuración > Copias de seguridad > Restaurar.\n");
            if (! $zip->close()) {
                throw new RuntimeException('No se pudo terminar el archivo de respaldo.');
            }

            $backup->update([
                'status' => 'listo', 'path' => $relative, 'size' => filesize($zipPath), 'finished_at' => now(),
                'summary' => ['tablas' => count($tables), 'filas' => array_sum(array_column($tables, 'rows')), 'archivos' => count($files)],
            ]);
        } catch (\Throwable $e) {
            @unlink($zipPath);
            throw $e;
        } finally {
            File::deleteDirectory($tmp);
        }
    }

    /** @return list<string> */
    public function tables(): array
    {
        $skip = config('backup.skip_tables');

        return collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
            ->map(fn ($r) => array_values((array) $r)[0])
            ->reject(fn ($t) => in_array($t, $skip, true) || str_starts_with($t, 'telescope_') || str_starts_with($t, 'pulse_'))
            ->values()->all();
    }

    /** @return list<string> */
    public function columns(string $table): array
    {
        return array_map(fn ($c) => $c->Field, DB::select("SHOW COLUMNS FROM `$table`"));
    }

    private function dumpTable(string $table, string $file, SecretCodec $codec): int
    {
        $out = fopen($file, 'w');
        $n = 0;
        $write = function ($row) use ($out, $codec, &$n) {
            $row = (array) $row;
            foreach ($row as $k => $v) {
                if (is_string($v)) {
                    $row[$k] = $codec->open($v);
                }
            }
            fwrite($out, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)."\n");
            $n++;
        };
        if (in_array('id', $this->columns($table), true)) {
            DB::table($table)->orderBy('id')->chunkById(2000, fn ($rows) => $rows->each($write));
        } else {
            foreach (DB::table($table)->cursor() as $row) {
                $write($row);
            }
        }
        fclose($out);

        return $n;
    }

    private function skipped(string $path): bool
    {
        foreach (config('backup.skip_dirs') as $dir) {
            if ($path === $dir || str_starts_with($path, $dir.'/')) {
                return true;
            }
        }

        return str_starts_with(basename($path), '.');
    }
}
