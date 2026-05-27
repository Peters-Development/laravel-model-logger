<?php

namespace PetersDevelopment\ModelLogger\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PetersDevelopment\ModelLogger\HasModelLogs;
use PetersDevelopment\ModelLogger\ModelLog;
use PetersDevelopment\ModelLogger\Schema\ModelLogsBlueprint;

class ChannelsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // A second SQLite connection so we can prove that channels honour
        // their own `connection` setting independently of the default DB.
        config()->set('database.connections.secondary', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // The default connection already has `model_logs` from the package
        // migration; create our extra channels here.
        Schema::connection('secondary')->create('audit_logs', function (Blueprint $table) {
            ModelLogsBlueprint::columns($table, morphKeyType: 'numeric');
        });

        Schema::create('shadow_logs', function (Blueprint $table) {
            ModelLogsBlueprint::columns($table, morphKeyType: 'numeric');
        });

        config()->set('model-logger.channels.audit', [
            'table' => 'audit_logs',
            'connection' => 'secondary',
            'morph_key_type' => 'numeric',
            'user_model' => null,
        ]);

        config()->set('model-logger.channels.shadow', [
            'table' => 'shadow_logs',
            'connection' => null,
            'morph_key_type' => 'numeric',
            'user_model' => null,
        ]);

        // The shadow table sits alongside the default `test_models` table, so
        // the existing TestModel from the base test case is reusable.
    }

    public function test_log_writes_to_default_channel_table(): void
    {
        $model = TestModel::create(['name' => 'A']);
        $model->log('hello default');

        $this->assertDatabaseHas('model_logs', ['message' => 'hello default']);
        $this->assertDatabaseMissing('shadow_logs', ['message' => 'hello default']);
    }

    public function test_model_overriding_channel_writes_to_that_channel_only(): void
    {
        $model = ShadowChannelModel::create(['name' => 'B']);
        $model->log('hello shadow');

        $this->assertDatabaseHas('shadow_logs', ['message' => 'hello shadow']);
        $this->assertDatabaseMissing('model_logs', ['message' => 'hello shadow']);
    }

    public function test_channel_with_separate_connection_writes_to_that_connection(): void
    {
        $model = AuditChannelModel::create(['name' => 'C']);
        $model->log('hello audit');

        // Default connection must NOT contain this log.
        $this->assertDatabaseMissing('model_logs', ['message' => 'hello audit']);

        // Secondary connection MUST contain it.
        $found = DB::connection('secondary')->table('audit_logs')->where('message', 'hello audit')->exists();
        $this->assertTrue($found, 'Audit log should land on secondary connection');
    }

    public function test_logs_relation_reads_from_the_configured_channel(): void
    {
        $model = ShadowChannelModel::create(['name' => 'D']);
        $model->log('first shadow');
        $model->log('second shadow');

        // A different model on the default channel should not pollute the read.
        TestModel::create(['name' => 'unrelated'])->log('default noise');

        $logs = $model->logs;

        $this->assertCount(2, $logs);
        $this->assertEqualsCanonicalizing(
            ['first shadow', 'second shadow'],
            $logs->pluck('message')->all()
        );
    }

    public function test_logs_relation_for_audit_channel_queries_secondary_connection(): void
    {
        $model = AuditChannelModel::create(['name' => 'E']);
        $model->log('isolated audit');

        $logs = $model->logs;

        $this->assertCount(1, $logs);
        $this->assertSame('isolated audit', $logs->first()->message);
    }

    public function test_blueprint_helper_supports_all_morph_key_types(): void
    {
        Schema::create('numeric_logs', function (Blueprint $table) {
            ModelLogsBlueprint::columns($table, morphKeyType: 'numeric');
        });
        Schema::create('uuid_logs', function (Blueprint $table) {
            ModelLogsBlueprint::columns($table, morphKeyType: 'uuid');
        });
        Schema::create('ulid_logs', function (Blueprint $table) {
            ModelLogsBlueprint::columns($table, morphKeyType: 'ulid');
        });

        $this->assertTrue(Schema::hasColumn('numeric_logs', 'loggable_id'));
        $this->assertTrue(Schema::hasColumn('uuid_logs', 'loggable_id'));
        $this->assertTrue(Schema::hasColumn('ulid_logs', 'loggable_id'));
    }

    public function test_blueprint_helper_rejects_unknown_morph_key_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Schema::create('bad_logs', function (Blueprint $table) {
            ModelLogsBlueprint::columns($table, morphKeyType: 'gibberish');
        });
    }
}

class ShadowChannelModel extends Model
{
    use HasModelLogs;

    protected $table = 'test_models';

    protected $fillable = ['name'];

    public function getModelLogChannel(): string
    {
        return 'shadow';
    }
}

class AuditChannelModel extends Model
{
    use HasModelLogs;

    protected $table = 'test_models';

    protected $fillable = ['name'];

    public function getModelLogChannel(): string
    {
        return 'audit';
    }
}
