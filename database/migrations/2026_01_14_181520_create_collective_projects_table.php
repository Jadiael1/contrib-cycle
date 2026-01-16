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
        Schema::create('collective_projects', function (Blueprint $table) {
            $table->id();

            $table->string('title', 150);
            $table->string('slug', 180)->unique();

            $table->text('description')->nullable();

            $table->unsignedInteger('participant_limit');
            $table->decimal('amount_per_participant', 12, 2);

            // ex: week/month/year
            $table->string('payment_interval', 10); // 'week' | 'month' | 'year'
            $table->unsignedSmallInteger('payments_per_interval')->default(1); // 1x, 2x, etc

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by_user_id')->constrained('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collective_projects');
    }
};
