<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $oldFqcn = 'Modules\\Core\\Infrastructure\\Eloquent\\Models\\StaffUser';
        $morphKey = 'staff-user';

        DB::transaction(function () use ($oldFqcn, $morphKey): void {
            $rolesCount = DB::table('model_has_roles')
                ->where('model_type', $oldFqcn)
                ->update(['model_type' => $morphKey]);

            $permissionsCount = DB::table('model_has_permissions')
                ->where('model_type', $oldFqcn)
                ->update(['model_type' => $morphKey]);

            if ($rolesCount === 0 && $permissionsCount === 0) {
                // No rows to update — either already migrated or no data yet.
                return;
            }

            // Verify no rows remain with the old FQCN.
            $remainingRoles = DB::table('model_has_roles')
                ->where('model_type', $oldFqcn)
                ->count();
            $remainingPermissions = DB::table('model_has_permissions')
                ->where('model_type', $oldFqcn)
                ->count();

            throw_if(
                $remainingRoles > 0 || $remainingPermissions > 0,
                RuntimeException::class,
                'Migration failed: some model_type rows were not updated.'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $oldFqcn = 'Modules\\Core\\Infrastructure\\Eloquent\\Models\\StaffUser';
        $morphKey = 'staff-user';

        DB::table('model_has_roles')
            ->where('model_type', $morphKey)
            ->update(['model_type' => $oldFqcn]);

        DB::table('model_has_permissions')
            ->where('model_type', $morphKey)
            ->update(['model_type' => $oldFqcn]);
    }
};
