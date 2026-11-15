<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\Tenant;
use App\Support\Exports\ExportBuilder;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    public function __construct(public int $exportId) {}

    public function handle(ExportBuilder $builder): void
    {
        $export = Export::query()->withoutGlobalScope('tenant')->findOrFail($this->exportId);
        $tenant = Tenant::query()->findOrFail($export->tenant_id);
        $previousLocale = app()->getLocale();

        try {
            app(TenantContext::class)->run($tenant, function () use ($export, $builder, $tenant): void {
                app()->setLocale($tenant->locale ?? config('app.locale'));
                $export->update(['status' => 'PROCESSING', 'error' => null]);

                $payload = $builder->build($export->type, $export->filters ?? []);
                $stream = fopen('php://temp', 'w+b');

                if ($stream === false) {
                    throw new \RuntimeException('No se pudo crear el archivo temporal de exportación.');
                }

                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, $payload['headers'], ';');

                foreach ($payload['rows'] as $row) {
                    fputcsv($stream, $row, ';');
                }

                rewind($stream);

                $path = 'exports/'.$tenant->id.'/'.$export->id.'-'.$payload['filename'];
                Storage::disk('local')->writeStream($path, $stream);
                fclose($stream);

                $export->update([
                    'status' => 'READY',
                    'path' => $path,
                    'filename' => $payload['filename'],
                    'expires_at' => now()->addDay(),
                ]);
            });
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    public function failed(Throwable $exception): void
    {
        Export::query()->withoutGlobalScope('tenant')
            ->whereKey($this->exportId)
            ->update([
                'status' => 'FAILED',
                'error' => mb_substr($exception->getMessage(), 0, 1000),
            ]);
    }
}
