<?php

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
        Schema::create('collective_project_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collective_project_id')
                ->constrained('collective_projects')
                ->cascadeOnDelete();

            $table->string('payment_method_type', 30); // 'pix' | 'bank_transfer'
            $table->text('payment_method_payload'); // JSON string (encrypted via cast)

            $table->string('label', 80)->nullable(); // ex: "Primary", "Secondary", "Nubank"
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(1);

            $table->timestamps();

            $table->index(['collective_project_id', 'is_active'], 'idx_cppm_proj_active');
            $table->index(['collective_project_id', 'sort_order'], 'idx_cppm_proj_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collective_project_payment_methods');
    }
};
