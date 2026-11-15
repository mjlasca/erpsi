<?php

use App\Models\Export;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Export::query()
        ->withoutGlobalScope('tenant')
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->each(function (Export $export): void {
            if ($export->path !== null) {
                Storage::disk('local')->delete($export->path);
            }

            $export->delete();
        });
})->daily()->name('prune-expired-exports');
