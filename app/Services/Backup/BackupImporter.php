<?php

namespace App\Services\Backup;

use App\Events\BackupRestored;
use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Restaura un respaldo de Nubicta (de nube o de privado): reemplaza todos los datos de la empresa y sus archivos.
 * Se hace en una sola transaccion; si algo falla no se cambia nada. Quien restaura conserva su usuario y contraseña.
 */
class BackupImporter
{
    public static function epochKey(): string
    {
        return config('backup.epoch_key', 'backup:restore_epoch');
    }

    /** Abre el ZIP y devuelve su manifiesto, o falla si la contraseña no es correcta o no es un respaldo de Nubicta. */
    public function inspect(string $zipPath, string $password): array
    {
        $zip = $this->open($zipPath, $password);
        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
        $zip->close();
        if (! is_array($manifest) || ! in_array($manifest['app'] ?? null, ['nubicta', 'friigo'], true)) {
            throw new RuntimeException('La contraseña no es correcta o el archivo no es un respaldo de INVOIX INC.');
        }
        if ((int) ($manifest['format'] ?? 0) > (int) config('backup.format')) {
            throw new RuntimeException('El respaldo es de una versión más nueva. Actualiza este sistema antes de restaurarlo.');
        }

        return $manifest;
    }

    public function import(Backup $backup, string $zipPath, string $password, User $restorer, string $restorerPassword): void
    {
        $manifest = $this->inspect($zipPath, $password);
        $zip = $this->open($zipPath, $password);
        $codec = SecretCodec::current();
        $me = (array) DB::table('users')->where('id', $restorer->id)->first();
        $targetTables = array_flip(app(BackupExporter::class)->tables());
        $sealed = SealedColumns::map();
        $skipped = [];
        $rows = 0;

        DB::transaction(function () use ($zip, $manifest, $codec, $targetTables, $sealed, &$skipped, &$rows) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                foreach ($manifest['tables'] as $table => $info) {
                    if (! isset($targetTables[$table])) {
                        $skipped[] = $table;

                        continue;
                    }
                    $columns = array_flip(app(BackupExporter::class)->columns($table));
                    $modes = $sealed[$table] ?? [];
                    DB::table($table)->delete();
                    $stream = $zip->getStream("data/$table.jsonl");
                    if ($stream === false) {
                        throw new RuntimeException("Al respaldo le falta la tabla $table.");
                    }
                    $batch = [];
                    while (($line = fgets($stream)) !== false) {
                        $row = json_decode($line, true);
                        if (! is_array($row)) {
                            continue;
                        }
                        $row = array_intersect_key($row, $columns);
                        foreach ($row as $k => $v) {
                            if (is_string($v)) {
                                $row[$k] = match ($modes[$k] ?? null) {
                                    'all' => $codec->sealAll($v),
                                    'markers' => $codec->seal($v),
                                    default => $codec->reveal($v),
                                };
                            }
                        }
                        $batch[] = $row;
                        if (count($batch) >= 500) {
                            DB::table($table)->insert($batch);
                            $rows += count($batch);
                            $batch = [];
                        }
                    }
                    fclose($stream);
                    if ($batch) {
                        DB::table($table)->insert($batch);
                        $rows += count($batch);
                    }
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        });

        $user = $this->keepRestorer($me);
        $files = $this->restoreFiles($zip, $manifest, $codec);
        $zip->close();

        DB::table('users')->update(['remember_token' => null]);
        Cache::forever(self::epochKey(), now()->timestamp);
        event(new BackupRestored($user, $restorerPassword, $manifest));

        $backup->update(['status' => 'listo', 'finished_at' => now(), 'summary' => [
            'empresa' => $manifest['company']['name'] ?? null, 'origen' => $manifest['edition'] ?? null, 'fecha' => $manifest['created_at'] ?? null,
            'filas' => $rows, 'archivos' => $files, 'omitidas' => $skipped,
        ]]);
    }

    /** Quien restaura sigue entrando con su correo y contraseña actuales, como administrador. */
    private function keepRestorer(array $me): User
    {
        $existing = DB::table('users')->where('email', $me['email'])->first();
        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update(['password' => $me['password'], 'active' => 1]);
            $user = User::query()->findOrFail($existing->id);
        } else {
            $columns = array_flip(app(BackupExporter::class)->columns('users'));
            $row = array_intersect_key($me, $columns);
            unset($row['id']);
            $row['company_id'] = DB::table('companies')->orderBy('id')->value('id') ?? $row['company_id'] ?? null;
            $user = User::query()->findOrFail(DB::table('users')->insertGetId($row));
        }
        $user->forgetCachedPermissions();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $user->assignRole('Administrador');

        return $user;
    }

    private function restoreFiles(ZipArchive $zip, array $manifest, SecretCodec $codec): int
    {
        $disk = Storage::disk(config('backup.disk'));
        $skip = config('backup.skip_dirs');
        foreach ($disk->directories() as $dir) {
            if (! in_array($dir, $skip, true)) {
                $disk->deleteDirectory($dir);
            }
        }
        foreach ($disk->files() as $file) {
            if (! str_starts_with(basename($file), '.')) {
                $disk->delete($file);
            }
        }
        $n = 0;
        foreach ($manifest['files'] ?? [] as $f) {
            $contents = $zip->getFromName('files/'.$f['path']);
            if ($contents === false) {
                continue;
            }
            $disk->put($f['path'], $f['encrypted'] ? $codec->seal($contents) : $contents);
            $n++;
        }
        $prefix = (string) config('backup.public_prefix');
        foreach ($manifest['public'] ?? [] as $path) {
            $contents = $zip->getFromName('public/'.$path);
            if ($contents === false) {
                continue;
            }
            $target = $prefix.ltrim(preg_replace('#^t\d+/#', '', $path), '/');
            Storage::disk('public')->put($target, $contents);
            DB::table('companies')->where('logo_path', $path)->update(['logo_path' => $target]);
        }

        return $n;
    }

    private function open(string $zipPath, string $password): ZipArchive
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('El archivo no es un ZIP válido.');
        }
        $zip->setPassword($password);

        return $zip;
    }
}
