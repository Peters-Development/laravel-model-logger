<?php

namespace PetersDevelopment\ModelLogger;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait that adds polymorphic activity logging to an Eloquent model.
 *
 * Default behaviour writes to the package's configured default channel
 * (a single `model_logs` table on the default connection — zero config
 * needed for simple apps).
 *
 * Apps with multiple log destinations override getModelLogChannel() per
 * model to route logs to a specific channel:
 *
 *     class User extends Authenticatable {
 *         use HasModelLogs;
 *         public function getModelLogChannel(): string { return 'auth'; }
 *     }
 */
trait HasModelLogs
{
    /**
     * Override in your model to route its logs to a non-default channel.
     */
    public function getModelLogChannel(): string
    {
        return config('model-logger.default', 'default');
    }

    /**
     * Write a log entry for this model on its configured channel.
     */
    public function log(string $message, array $meta = []): ModelLog
    {
        $log = ModelLog::onChannel($this->getModelLogChannel());

        $log->fill([
            'user_id' => auth()->id(),
            'message' => $message,
            'loggable_id' => $this->getKey(),
            'loggable_type' => $this->getMorphClass(),
            'meta' => $meta,
        ])->save();

        return $log;
    }

    /**
     * MorphMany relation that reads logs from the model's configured
     * channel (table + connection). Built manually instead of via
     * $this->morphMany(...) because we need to bind the related instance's
     * table/connection at runtime, not statically on the class.
     */
    public function logs(): MorphMany
    {
        $instance = ModelLog::onChannel($this->getModelLogChannel());
        [$type, $id] = $this->getMorphs('loggable', null, null);
        $table = $instance->getTable();

        return $this->newMorphMany(
            $instance->newQuery(),
            $this,
            $table.'.'.$type,
            $table.'.'.$id,
            $this->getKeyName(),
        );
    }
}
