<?php

namespace PetersDevelopment\ModelLogger\Tests;

use Illuminate\Database\Eloquent\Model;
use PetersDevelopment\ModelLogger\HasModelLogs;
use PetersDevelopment\ModelLogger\ModelLog;

class UserIdTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // A second session guard next to the default one, the shape of an
        // app whose billing area authenticates on its own guard.
        $app['config']->set('auth.providers.test_users', [
            'driver' => 'eloquent',
            'model' => TestUser::class,
        ]);
        $app['config']->set('auth.guards.billing', [
            'driver' => 'session',
            'provider' => 'test_users',
        ]);
    }

    public function test_a_model_without_the_hook_records_the_default_guard(): void
    {
        $default = $this->user('default@example.com');
        $billing = $this->user('billing@example.com');

        auth()->guard()->setUser($default);
        auth()->guard('billing')->setUser($billing);

        TestModel::create(['name' => 'A'])->log('no hook');

        $this->assertSame($default->getKey(), ModelLog::first()->user_id);
    }

    public function test_the_hook_decides_the_recorded_user(): void
    {
        $default = $this->user('default@example.com');
        $billing = $this->user('billing@example.com');

        // setUser() rather than actingAs(): actingAs() also calls
        // shouldUse(), which would make 'billing' the default guard and
        // hide the difference this test is about.
        auth()->guard()->setUser($default);
        auth()->guard('billing')->setUser($billing);

        BillingGuardModel::create(['name' => 'B'])->log('hooked');

        $this->assertSame($billing->getKey(), ModelLog::first()->user_id);
    }

    public function test_the_hook_can_be_inherited_from_a_parent_class(): void
    {
        $billing = $this->user('billing@example.com');
        auth()->guard('billing')->setUser($billing);

        // The hook lives on the parent class. If the trait declared
        // getModelLogUserId() itself, PHP's "trait beats inherited" rule
        // would silently record the default guard (null) instead.
        InheritsUserIdModel::create(['name' => 'C'])->log('inherited hook');

        $this->assertSame($billing->getKey(), ModelLog::first()->user_id);
    }

    public function test_a_null_from_the_hook_is_recorded_as_null(): void
    {
        // The hook's answer is final: a model that wants the default guard
        // as a fallback says so itself (`?? auth()->id()`).
        auth()->guard()->setUser($this->user('default@example.com'));

        BillingGuardModel::create(['name' => 'D'])->log('nobody on billing');

        $this->assertNull(ModelLog::first()->user_id);
    }

    private function user(string $email): TestUser
    {
        return TestUser::create([
            'name' => $email,
            'email' => $email,
            'password' => 'password',
        ]);
    }
}

class BillingGuardModel extends Model
{
    use HasModelLogs;

    protected $table = 'test_models';

    protected $fillable = ['name'];

    public function getModelLogUserId(): int|string|null
    {
        return auth('billing')->id();
    }
}

abstract class UserIdByInheritanceBase extends Model
{
    protected $table = 'test_models';

    protected $fillable = ['name'];

    public function getModelLogUserId(): int|string|null
    {
        return auth('billing')->id();
    }
}

class InheritsUserIdModel extends UserIdByInheritanceBase
{
    use HasModelLogs;
}
