<?php

namespace PetersDevelopment\ModelLogger;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModelLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'message',
        'loggable_type',
        'loggable_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function getTable(): string
    {
        return config('model-logger.table_name', 'model_logs');
    }

    /**
     * User that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'));
    }

    /**
     * Object ModelLog belongs to.
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope logs by user.
     */
    public function scopeForUser(Builder $query, int|string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
