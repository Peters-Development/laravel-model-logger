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

## Configuration

Publish the config to customize the table name:

```bash
php artisan vendor:publish --tag=model-logger
```

```php
// config/model-logger.php
return [
    'table_name' => 'model_logs',
];
```

## License

MIT
