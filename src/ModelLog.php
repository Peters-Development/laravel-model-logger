<?php

namespace PetersDevelopment\ModelLogger;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

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

    /**
     * Channel name this instance is bound to. Set via ::onChannel() so the
     * same ModelLog class can serve every channel without per-channel
     * subclasses. The HasModelLogs trait reads the channel from each
     * loggable model's getModelLogChannel() override.
     */
    protected ?string $channel = null;

    /**
     * Build a ModelLog instance configured for the given channel:
     * table, connection, and (transitively, via the loggable relation) the
     * morph key type and user model are all resolved from the channel config.
     *
     * Falls back to the package's default channel when $name is unknown,
     * so consuming apps never crash hard on a typo — but the configured
     * default is just whatever 'default' resolves to in config.
     */
    public static function onChannel(string $name): static
    {
        $config = config("model-logger.channels.{$name}");

        if (! is_array($config)) {
            $fallback = config('model-logger.default', 'default');
            $config = config("model-logger.channels.{$fallback}");
        }

        if (! is_array($config)) {
            throw new InvalidArgumentException(
                "model-logger has no channel '{$name}' and no usable default channel configured."
            );
        }

        $instance = new static;
        $instance->channel = $name;
        $instance->setTable($config['table'] ?? 'model_logs');

        if (! empty($config['connection'])) {
            $instance->setConnection($config['connection']);
        }

        return $instance;
    }

    /**
     * Channel this instance was created on. null = the class default
     * (i.e. the instance was constructed directly without onChannel()).
     */
    public function getChannelName(): ?string
    {
        return $this->channel;
    }

    /**
     * The user that performed the action. Resolves the user model from the
     * bound channel's user_model setting, falling back to the framework's
     * default auth provider.
     */
    public function user(): BelongsTo
    {
        $userModel = null;

        if ($this->channel !== null) {
            $userModel = config("model-logger.channels.{$this->channel}.user_model");
        }

        $userModel ??= config('auth.providers.users.model', \App\Models\User::class);

        return $this->belongsTo($userModel);
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForUser(Builder $query, int|string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
