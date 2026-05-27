<?php

namespace PetersDevelopment\ModelLogger\Schema;

use Illuminate\Database\Schema\Blueprint;
use InvalidArgumentException;

/**
 * Schema helper for migration writers.
 *
 * Migration authors call ModelLogsBlueprint::columns($table, $morphKeyType)
 * inside a Schema::create() closure to add the standard model-logs columns +
 * indexes without copying the column list into every migration.
 *
 * The package's own auto-loaded migration uses this helper to build the
 * `default` channel's table. Channel consumers (e.g. PDEV2's auth_logs,
 * LarsaSub's larsasub_model_logs) write their own migration referencing
 * this helper.
 *
 * Example:
 *
 *     use PetersDevelopment\ModelLogger\Schema\ModelLogsBlueprint;
 *
 *     return new class extends Migration {
 *         public function up(): void {
 *             Schema::connection('landlord')->create('auth_logs', function (Blueprint $table) {
 *                 ModelLogsBlueprint::columns($table, morphKeyType: 'numeric');
 *             });
 *         }
 *     };
 */
class ModelLogsBlueprint
{
    public const MORPH_KEY_TYPES = ['numeric', 'uuid', 'ulid'];

    /**
     * Add the standard model-logs columns + indexes to the given blueprint.
     *
     * Idempotent on the blueprint object — call once inside Schema::create().
     */
    public static function columns(Blueprint $table, string $morphKeyType = 'numeric'): void
    {
        if (! in_array($morphKeyType, self::MORPH_KEY_TYPES, true)) {
            throw new InvalidArgumentException(
                "Unknown morph_key_type '{$morphKeyType}'. Allowed: ".implode(', ', self::MORPH_KEY_TYPES)
            );
        }

        $table->id();
        $table->unsignedBigInteger('user_id')->nullable()->index();
        $table->text('message');

        match ($morphKeyType) {
            'uuid' => $table->uuidMorphs('loggable'),
            'ulid' => $table->ulidMorphs('loggable'),
            'numeric' => $table->numericMorphs('loggable'),
        };

        $table->json('meta')->nullable();
        $table->timestamp('created_at')->nullable();
    }
}
