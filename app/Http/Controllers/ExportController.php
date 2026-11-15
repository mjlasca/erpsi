<?php

namespace App\Http\Controllers;

use App\Models\Export;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index(Request $request): Response
    {
        $exports = Export::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);
        $polling = $exports->getCollection()
            ->contains(fn (Export $export): bool => in_array($export->status, ['PENDING', 'PROCESSING'], true));

        return Inertia::render('Exports/Index', [
            'exports' => $exports->through(fn (Export $export): array => [
                'id' => $export->id,
                'type' => $export->type,
                'status' => $export->status,
                'filename' => $export->filename,
                'error' => $export->error,
                'expires_at' => $export->expires_at,
                'created_at' => $export->created_at,
                'can_download' => $export->isReady() && $export->expires_at?->isFuture(),
            ]),
            'polling' => $polling,
        ]);
    }

    public function download(Request $request, Export $export): StreamedResponse
    {
        abort_unless($export->user_id === $request->user()->id, 403);
        abort_unless($export->isReady(), 404);
        abort_unless($export->expires_at === null || $export->expires_at->isFuture(), 410);

        abort_unless(Storage::disk('local')->exists($export->path), 404);

        return Storage::disk('local')->download($export->path, $export->filename);
    }
}
