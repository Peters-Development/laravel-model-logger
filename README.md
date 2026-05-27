# Laravel Model Logger

Lightweight polymorphic model activity logging for Laravel.

- **Zero-config for simple apps** — `composer require`, `php artisan migrate`, start logging.
- **Channels for advanced apps** — route logs to different tables, connections, or morph key types (numeric/uuid/ulid) when multiple packages or databases are involved.

## Installation

```bash
composer require peters-development/laravel-model-logger
php artisan migrate
```

The service provider is auto-discovered. The default migration creates a `model_logs` table on your default connection.

## Basic usage

Add the `HasModelLogs` trait to any Eloquent model:

```php
use PetersDevelopment\ModelLogger\HasModelLogs;

class Order extends Model
{
    use HasModelLogs;
}
```

Log activity:

```php
$order->log('Order was shipped');
$order->log('Payment received', ['amount' => 99.99, 'method' => 'ideal']);

$order->logs;        // MorphMany — collection of ModelLog
$log = $order->log('Status changed', ['from' => 'pending', 'to' => 'shipped']);
```

The authenticated user is automatically recorded. Meta is stored as JSON.

### Querying

```php
use PetersDevelopment\ModelLogger\ModelLog;

$log->user;                                // User model that performed the action
$logsByUser = ModelLog::forUser($userId)->get();
```

## Configuration

Publish to customise:

```bash
php artisan vendor:publish --tag=model-logger-config
```

The config is built around **channels**. Each channel owns its own table, connection, and morph-key type. Simple apps use the single bundled `default` channel and never touch this:

```php
// config/model-logger.php (default — works without publishing)
return [
    'default' => env('MODEL_LOGGER_DEFAULT_CHANNEL', 'default'),

    'channels' => [
        'default' => [
            'table' => 'model_logs',
            'connection' => null,           // null = Laravel default
            'morph_key_type' => 'numeric',  // 'numeric' | 'uuid' | 'ulid'
            'user_model' => null,           // null = config('auth.providers.users.model')
        ],
    ],
];
```

## Channels (advanced)

Channels let multiple loggers coexist in one app without colliding. Typical use cases:

- A multi-tenant SaaS that wants **landlord** auth-events on one connection and **tenant** business-events on another.
- A package (e.g. a billing or auth package) that ships its own table-prefixed channel without overwriting the host app's logs.
- An app with **UUID** or **ULID** keyed models that wants properly-sized morph columns.

### Declaring a channel

Add a channel entry to your published config:

```php
// config/model-logger.php
return [
    'default' => 'default',

    'channels' => [
        'default' => [
            'table' => 'model_logs',
            'connection' => null,
            'morph_key_type' => 'numeric',
            'user_model' => null,
        ],
        'auth' => [
            'table' => 'auth_logs',
            'connection' => 'landlord',
            'morph_key_type' => 'numeric',
            'user_model' => \App\Models\User::class,
        ],
    ],
];
```

### Routing a model's logs to a channel

Override `getModelLogChannel()`:

```php
class User extends Authenticatable
{
    use HasModelLogs;

    public function getModelLogChannel(): string
    {
        return 'auth';
    }
}
```

`$user->log(...)` and `$user->logs` now read/write the `auth` channel's table on the `landlord` connection.

### Creating the channel's table

Write a migration on the channel's target connection. The package ships a schema helper so you don't have to copy column definitions:

```php
use PetersDevelopment\ModelLogger\Schema\ModelLogsBlueprint;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('landlord')->create('auth_logs', function (Blueprint $table) {
            ModelLogsBlueprint::columns($table, morphKeyType: 'numeric');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('auth_logs');
    }
};
```

### Packages that ship their own channel

A consuming package can register its own channel from its service provider so it never clobbers the host app's `default`:

```php
public function register(): void
{
    config()->set('model-logger.channels.mypackage', [
        'table' => 'mypackage_logs',
        'connection' => config('mypackage.database.connection'),
        'morph_key_type' => 'numeric',
    ]);
}
```

Then every model in the package overrides `getModelLogChannel()` to return `'mypackage'`.

## morph_key_type

| Value | When to use |
|---|---|
| `numeric` | Default. Bigint auto-increment IDs — fits ~95% of Laravel apps. |
| `uuid` | Models keyed by UUID. |
| `ulid` | Models keyed by ULID (Laravel's `HasUlids` trait). |

A single app can mix all three by using one channel per key type.

## License

MIT
