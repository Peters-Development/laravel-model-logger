<?php

namespace PetersDevelopment\ModelLogger\Tests;

use PetersDevelopment\ModelLogger\HasModelLogs;
use PetersDevelopment\ModelLogger\ModelLog;

class ModelLogTest extends TestCase
{
    public function test_default_channel_table_is_model_logs(): void
    {
        $this->assertSame('model_logs', (new ModelLog)->getTable());
    }

    public function test_on_channel_binds_instance_to_channel_table(): void
    {
        config()->set('model-logger.channels.custom', [
            'table' => 'custom_logs',
            'connection' => null,
            'morph_key_type' => 'numeric',
            'user_model' => null,
        ]);

        $log = ModelLog::onChannel('custom');

        $this->assertSame('custom_logs', $log->getTable());
        $this->assertSame('custom', $log->getChannelName());
    }

    public function test_on_channel_falls_back_to_default_when_channel_unknown(): void
    {
        $log = ModelLog::onChannel('does-not-exist');

        $this->assertSame('model_logs', $log->getTable());
    }

    public function test_loggable_relation(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->log('Test relation');

        $log = ModelLog::first();

        $this->assertInstanceOf(TestModel::class, $log->loggable);
        $this->assertEquals($model->id, $log->loggable->id);
    }

    public function test_user_relation(): void
    {
        config()->set('auth.providers.users.model', TestUser::class);

        $user = TestUser::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($user);

        $model = TestModel::create(['name' => 'Test']);
        $model->log('User relation test');

        $log = ModelLog::first();

        $this->assertInstanceOf(TestUser::class, $log->user);
        $this->assertEquals($user->id, $log->user->id);
    }

    public function test_scope_for_user(): void
    {
        $user = TestUser::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $model = TestModel::create(['name' => 'Test']);

        $this->actingAs($user);
        $model->log('By user');

        auth()->logout();
        $model->log('Anonymous');

        $logs = ModelLog::forUser($user->id)->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('By user', $logs->first()->message);
    }

    public function test_does_not_have_updated_at(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->log('No updated_at');

        $log = ModelLog::first();

        $this->assertNotNull($log->created_at);
        $this->assertNull($log->updated_at);
    }

    public function test_does_not_use_has_model_logs_trait(): void
    {
        $this->assertFalse(
            in_array(HasModelLogs::class, class_uses(ModelLog::class)),
            'ModelLog should not use HasModelLogs trait'
        );
    }
}
