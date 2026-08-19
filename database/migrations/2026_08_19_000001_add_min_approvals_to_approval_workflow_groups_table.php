<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('approval_workflow_groups', function (Blueprint $table): void {
            $table->unsignedSmallInteger('min_approvals')
                ->nullable()
                ->after('type')
                ->comment('Minimum number of approvals required for parallel groups. Null or step count means unanimous.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_workflow_groups', function (Blueprint $table): void {
            $table->dropColumn('min_approvals');
        });
    }
};
