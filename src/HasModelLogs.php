<?php

namespace PetersDevelopment\ModelLogger;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait that adds polymorphic activity logging to an Eloquent model.
 *
 * Default behaviour writes to the package's configured default channel —
 * a single `model_logs` table on the default connection. Zero config
 * needed for simple apps.
 *
 * Apps with multiple log destinations route a model's logs by defining
 * a `getModelLogChannel()` method on the model itself OR on a parent
 * class. Because PHP trait methods override inherited methods from parent
 * classes, the trait deliberately does NOT declare getModelLogChannel() —
 * it discovers an override via method_exists() so an inheritance chain
 * (e.g. a package's BaseModel providing the channel for all subclasses)
 * works as expected.
 *
 *     class User extends Authenticatable {
 *         use HasModelLogs;
 *         public function getModelLogChannel(): string { return 'auth'; }
 *     }
 *
 *     // Or via a shared parent class:
 *     abstract class LarsaSubBase extends Model {
 *         public function getModelLogChannel(): string { return 'larsasub'; }
 *     }
 *     class Invoice extends LarsaSubBase {
 *         use HasModelLogs;  // inherits the 'larsasub' channel
 *     }
 *
 * The user recorded on each entry is discovered the same way: a
 * `getModelLogUserId()` method on the model or a parent class decides it,
 * otherwise it is the default guard's `auth()->id()`. An app whose actions
 * happen behind a non-default guard points it there:
 *
 *     abstract class LarsaSubBase extends Model {
 *         public function getModelLogUserId(): int|string|null {
 *             return auth('billing')->id() ?? auth()->id();
 *         }
 *     }
 */
trait HasModelLogs
{
    /**
     * Resolve the channel this model writes logs to. Discovers an override
     * (own or inherited) via method_exists, falling back to the package
     * default. Kept private to the trait so subclasses don't accidentally
     * shadow it — they override via getModelLogChannel() instead.
     */
    private function resolveModelLogChannel(): string
    {
        if (method_exists($this, 'getModelLogChannel')) {
            return $this->getModelLogChannel();
        }

        return config('model-logger.default', 'default');
    }

    /**
     * Resolve the user id recorded on a new entry. Discovers an override
     * (own or inherited) via method_exists, for the same reason as the
     * channel: a trait-declared method would shadow one on a parent class.
     * Without one it is the default guard's user, as it always was.
     */
    private function resolveModelLogUserId(): int|string|null
    {
        if (method_exists($this, 'getModelLogUserId')) {
            return $this->getModelLogUserId();
        }

        return auth()->id();
    }

    /**
     * Write a log entry for this model on its configured channel.
     */
    public function log(string $message, array $meta = []): ModelLog
    {
        $log = ModelLog::onChannel($this->resolveModelLogChannel());

        $log->fill([
            'user_id' => $this->resolveModelLogUserId(),
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
        $instance = ModelLog::onChannel($this->resolveModelLogChannel());
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
