<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PetersDevelopment\ModelLogger\Schema\ModelLogsBlueprint;

return new class extends Migration
{
    /**
     * Creates the table for the package's `default` channel.
     *
     * Apps that use additional channels (e.g. routing logs to a separate
     * connection or table) write their own migration that calls
     * ModelLogsBlueprint::columns() on the target connection/table.
     */
    protected function channel(): array
    {
        return config('model-logger.channels.default', [
            'table' => 'model_logs',
            'connection' => null,
            'morph_key_type' => 'numeric',
        ]);
    }

    public function up(): void
    {
        $channel = $this->channel();
        $schema = $channel['connection']
            ? Schema::connection($channel['connection'])
            : Schema::connection(null);

        $schema->create($channel['table'], function (Blueprint $table) use ($channel) {
            ModelLogsBlueprint::columns($table, $channel['morph_key_type'] ?? 'numeric');
        });
    }

    public function down(): void
    {
        $channel = $this->channel();
        $schema = $channel['connection']
            ? Schema::connection($channel['connection'])
            : Schema::connection(null);

        $schema->dropIfExists($channel['table']);
    }
};
