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
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->cascadeOnDelete();
            $table->string('key', 100);
            $table->mediumText('value')->nullable();
            $table->string('type', 20)->default('text'); // text, numeric, json, boolean, encrypted
            $table->string('group', 50)->default('general'); // general, localization, mail, custom, company
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'key']);
            $table->index('key');
            $table->index('group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
