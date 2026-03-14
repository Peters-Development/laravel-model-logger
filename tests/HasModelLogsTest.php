<?php

namespace PetersDevelopment\ModelLogger\Tests;

use PetersDevelopment\ModelLogger\ModelLog;

class HasModelLogsTest extends TestCase
{
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

    public function test_log_returns_model_log_instance(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $log = $model->log('Something happened');

        $this->assertInstanceOf(ModelLog::class, $log);
        $this->assertEquals('Something happened', $log->message);
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
}
