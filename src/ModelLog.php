<?php

namespace PetersDevelopment\ModelLogger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModelLog extends Model
{
    use HasModelLogs;

    protected $fillable = [
        'user_id',
        'message',
        'loggable_type',
        'loggable_id',
        'meta',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'message' => 'string',
        'loggable_type' => 'string',
        'loggable_id' => 'string',
        'meta' => 'array',
    ];

    public function getTable(): string
    {
        return config('model-logger.table_name', 'model_logs');
    }

    /**
     * Object ModelLog belongs to
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }
}
