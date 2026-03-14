# Laravel Model Logger

Lightweight polymorphic model activity logging for Laravel.

## Installation

```bash
composer require peters-development/laravel-model-logger
```

The service provider is auto-discovered. Migrations run automatically.

## Usage

Add the `HasModelLogs` trait to any Eloquent model:

```php
use PetersDevelopment\ModelLogger\HasModelLogs;

class Order extends Model
{
    use HasModelLogs;
}
```

Then log activity:

```php
$order->log('Order was shipped');
$order->log('Payment received', ['amount' => 99.99, 'method' => 'ideal']);

// Retrieve logs
$order->logs; // Collection of ModelLog
```

The authenticated user is automatically recorded. Meta data is stored as JSON.

The `log()` method returns the created `ModelLog` instance:

```php
$log = $order->log('Status changed', ['from' => 'pending', 'to' => 'shipped']);
```

## Querying Logs

Access the user that performed the action:

```php
$log->user; // Returns the User model
```

Filter logs by user:

```php
use PetersDevelopment\ModelLogger\ModelLog;

$logs = ModelLog::forUser($userId)->get();
```

## Configuration

Publish the config to customize the table name:

```bash
php artisan vendor:publish --tag=model-logger-config
```

```php
// config/model-logger.php
return [
    'table_name' => 'model_logs',
];
```

## License

MIT
