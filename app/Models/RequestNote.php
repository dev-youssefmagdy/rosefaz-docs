<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['body'])]
class RequestNote extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Request notes are append-only.'));
        static::deleting(fn () => throw new LogicException('Request notes are append-only.'));
    }

    public function contractRequest(): BelongsTo
    {
        return $this->belongsTo(ContractRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
