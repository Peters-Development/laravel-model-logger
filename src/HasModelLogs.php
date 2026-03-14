<?php

namespace PetersDevelopment\ModelLogger;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasModelLogs
{
    /**
     * Log action on model.
     */
    public function log(string $message, array $meta = []): ModelLog
    {
        return ModelLog::create([
            'user_id'       => auth()->id(),
            'message'       => $message,
            'loggable_id'   => $this->getKey(),
            'loggable_type' => $this->getMorphClass(),
            'meta'          => $meta,
        ]);
    }

    /**
     * Get all ModelLogs for morph object
     */
    public function logs(): MorphMany
    {
        return $this->morphMany(ModelLog::class, 'loggable');
    }
}
