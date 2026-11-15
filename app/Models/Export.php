<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $status
 * @property array<string, mixed>|null $filters
 * @property string $filename
 * @property string|null $path
 * @property string|null $error
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 */
#[Fillable([
    'user_id',
    'type',
    'status',
    'filters',
    'filename',
    'path',
    'error',
    'expires_at',
])]
class Export extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'READY' && $this->path !== null;
    }
}
