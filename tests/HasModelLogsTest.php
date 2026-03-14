<?php

namespace PetersDevelopment\ModelLogger\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use PetersDevelopment\ModelLogger\HasModelLogs;
use PetersDevelopment\ModelLogger\ModelLog;

class HasModelLogsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Run the model_logs migration
        $this->artisan('migrate');

        // Create a dummy table for testing
        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Create users table for auth testing
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function test_log_creates_model_log_record(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $model->log('Something happened');

        $this->assertDatabaseHas('model_logs', [
            'message' => 'Something happened',
            'loggable_id' => $model->getKey(),
            'loggable_type' => TestModel::class,
        ]);
    }

    public function test_logs_relation_returns_correct_records(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $model->log('First log');
        $model->log('Second log');

        $logs = $model->logs;

        $this->assertCount(2, $logs);
        $this->assertEquals('First log', $logs[0]->message);
        $this->assertEquals('Second log', $logs[1]->message);
    }

    public function test_meta_array_is_stored_correctly(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $model->log('With meta', ['key' => 'value', 'nested' => ['a' => 1]]);

        $log = ModelLog::first();

        $this->assertEquals(['key' => 'value', 'nested' => ['a' => 1]], $log->meta);
    }

    public function test_user_id_is_filled_from_auth(): void
    {
        $user = TestUser::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($user);

        $model = TestModel::create(['name' => 'Test']);
        $model->log('Authenticated log');

        $log = ModelLog::first();

        $this->assertEquals($user->id, $log->user_id);
    }

    public function test_user_id_is_null_when_not_authenticated(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->log('Anonymous log');

        $log = ModelLog::first();

        $this->assertNull($log->user_id);
    }

    public function test_custom_table_name_via_config(): void
    {
        $this->assertEquals('model_logs', (new ModelLog())->getTable());

        config()->set('model-logger.table_name', 'custom_logs');

        $this->assertEquals('custom_logs', (new ModelLog())->getTable());
    }

    public function test_model_log_has_loggable_relation(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->log('Test relation');

        $log = ModelLog::first();

        $this->assertInstanceOf(TestModel::class, $log->loggable);
        $this->assertEquals($model->id, $log->loggable->id);
    }
}

class TestModel extends \Illuminate\Database\Eloquent\Model
{
    use HasModelLogs;

    protected $fillable = ['name'];
}

class TestUser extends Authenticatable
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
}
