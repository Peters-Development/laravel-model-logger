<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Channel
    |--------------------------------------------------------------------------
    |
    | When a model uses HasModelLogs without overriding getModelLogChannel(),
    | this channel is used. Simple single-table apps never need to think
    | about channels — the 'default' channel ships preconfigured.
    |
    */

    'default' => env('MODEL_LOGGER_DEFAULT_CHANNEL', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Channels
    |--------------------------------------------------------------------------
    |
    | Each channel owns its own table, connection, and morph-key type. Most
    | apps only use the 'default' channel and never touch this. Multi-package
    | apps (or apps that split logs across databases like landlord/tenant)
    | declare additional channels here.
    |
    | Options per channel:
    |
    | - table:           Database table for this channel's logs.
    | - connection:      Database connection name. null = Laravel's default.
    | - morph_key_type:  'numeric' (default — fits ~95% of Laravel apps that
    |                    use bigint auto-increment keys), 'uuid', or 'ulid'.
    | - user_model:      Class used by the `user()` relation. null =
    |                    config('auth.providers.users.model').
    |
    */

    'channels' => [

        'default' => [
            'table' => 'model_logs',
            'connection' => null,
            'morph_key_type' => 'numeric',
            'user_model' => null,
        ],

    ],

];
