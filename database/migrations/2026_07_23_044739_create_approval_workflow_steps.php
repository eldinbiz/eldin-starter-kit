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
        Schema::create('approval_workflow_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('approval_workflow_groups')->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->string('resolver_type')->comment('N+1, N+2, specific_user, N+1_of_previous');
            $table->json('resolver_payload')->nullable()->comment('e.g. {"user_id": 5} for specific_user');
            $table->timestamps();

            $table->index(['group_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_workflow_steps');
    }
};
