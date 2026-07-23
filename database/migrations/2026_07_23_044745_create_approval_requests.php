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
        Schema::create('approval_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requestor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('workflow_template_id')->nullable()->constrained('approval_workflow_templates')->nullOnDelete();
            $table->nullableMorphs('approvable');
            $table->json('workflow_snapshot')->comment('Frozen copy of the workflow template at submission time');
            $table->string('status')->default('pending')->comment('pending, approved, rejected, canceled');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['requestor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
