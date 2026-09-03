<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_active');
        });

        // Protect core system roles
        DB::table('roles')
            ->whereIn('name', ['sysadmin', 'user'])
            ->update(['is_system' => true]);

        // Clean up legacy platform-admin role if present
        $sysAdmin = DB::table('roles')->where('name', 'sysadmin')->first();
        $platformAdmin = DB::table('roles')->where('name', 'platform-admin')->first();

        if ($platformAdmin && $sysAdmin) {
            // Reassign any existing company_user_role assignments to sysadmin
            DB::table('company_user_role')
                ->where('role_id', $platformAdmin->id)
                ->update(['role_id' => $sysAdmin->id]);

            // Delete legacy platform-admin role
            DB::table('roles')->where('id', $platformAdmin->id)->delete();
        } elseif ($platformAdmin && ! $sysAdmin) {
            // Rename to sysadmin
            DB::table('roles')->where('id', $platformAdmin->id)->update([
                'name' => 'sysadmin',
                'is_system' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
