<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('collective_project_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('collective_project_id')
                ->constrained('collective_projects')
                ->cascadeOnDelete();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users');

            // ex: payment_status
            $table->string('type', 50);

            // pending | ready | failed
            $table->string('status', 20)->default('pending');

            // filtros usados (year/month/week/status_scope/etc)
            $table->json('filters')->nullable();

            // onde foi salvo
            $table->string('disk', 50)->default('local');
            $table->string('path', 600)->nullable();

            $table->string('file_name', 255)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->timestamp('generated_at')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['collective_project_id', 'type', 'status'], 'idx_cpr_proj_type_status');
            $table->index(['collective_project_id', 'created_at'], 'idx_cpr_proj_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collective_project_reports');
    }
};
