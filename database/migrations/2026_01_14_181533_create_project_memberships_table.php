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
        Schema::create('project_memberships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('collective_project_id')->constrained('collective_projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // pending (antes do "aceito participar"), accepted (participando), removed (removido pelo admin)
            $table->string('status', 20)->default('pending');

            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('removed_by_user_id')->nullable()->constrained('users');

            $table->unique(['collective_project_id', 'user_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_memberships');
    }
};
