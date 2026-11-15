<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserPreferenceController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Preferences/Edit', [
            'preferences' => [
                'locale' => $request->user()->locale,
                'timezone' => $request->user()->timezone,
            ],
            'supportedLocales' => config('app.supported_locales', ['es', 'en']),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['nullable', 'string', 'in:'.implode(',', config('app.supported_locales', ['es', 'en']))],
            'timezone' => ['nullable', 'timezone:all'],
        ]);

        $request->user()->update($validated);

        return back()->with('success', __('messages.preferences_updated'));
    }
}
