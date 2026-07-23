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
        Schema::create('approval_request_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('approval_request_id')->constrained()->cascadeOnDelete();
            $table->string('group_name');
            $table->string('step_label')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->string('resolver_type')->comment('N+1, N+2, specific_user, N+1_of_previous');
            $table->json('resolver_payload')->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('state')->default('pending')->comment('pending, approved, rejected');
            $table->timestamp('action_at')->nullable();
            $table->text('notes')->nullable()->comment('Approver comment when approving/rejecting');
            $table->foreignId('delegated_from_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['approval_request_id', 'state']);
            $table->index(['approver_id', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_request_steps');
    }
};
