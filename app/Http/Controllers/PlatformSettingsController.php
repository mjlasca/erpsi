<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        abort_unless($request->user()?->is_platform_admin, 403);

        return Inertia::render('Platform/Settings/Edit', [
            'settings' => [
                'platform_name' => PlatformSetting::value('platform_name', (string) config('app.name')),
                'support_email' => PlatformSetting::value('support_email'),
                'default_locale' => PlatformSetting::value('default_locale', (string) config('app.locale')),
                'default_timezone' => PlatformSetting::value('default_timezone', (string) config('app.timezone')),
                'default_currency' => PlatformSetting::value('default_currency', (string) config('app.default_currency', 'MXN')),
                'default_trial_days' => (int) PlatformSetting::value('default_trial_days', '14'),
                'supported_locales' => array_values(array_filter(explode(',', (string) PlatformSetting::value('supported_locales', implode(',', config('app.supported_locales', ['es', 'en'])))))),
            ],
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()?->is_platform_admin, 403);

        $validated = $request->validate([
            'platform_name' => ['required', 'string', 'max:100'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'default_locale' => ['required', Rule::in(['es', 'en'])],
            'default_timezone' => ['required', 'timezone:all'],
            'default_currency' => ['required', 'string', 'size:3', 'alpha'],
            'default_trial_days' => ['required', 'integer', 'between:0,365'],
            'supported_locales' => ['required', 'array', 'min:1'],
            'supported_locales.*' => ['required', Rule::in(['es', 'en'])],
        ]);

        PlatformSetting::put('platform_name', $validated['platform_name']);
        PlatformSetting::put('support_email', $validated['support_email']);
        PlatformSetting::put('default_locale', $validated['default_locale']);
        PlatformSetting::put('default_timezone', $validated['default_timezone']);
        PlatformSetting::put('default_currency', $validated['default_currency']);
        PlatformSetting::put('default_trial_days', (string) $validated['default_trial_days']);
        PlatformSetting::put('supported_locales', implode(',', $validated['supported_locales']));

        $audit->record(
            'platform.settings_updated',
            'Configuración global actualizada.',
            null,
            null,
            ['settings' => $validated],
        );

        return back()->with('success', __('messages.settings_updated'));
    }
}
