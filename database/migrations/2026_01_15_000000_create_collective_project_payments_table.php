<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collective_project_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('collective_project_id')
                ->constrained('collective_projects')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Identificador do "período" pago
            $table->unsignedSmallInteger('period_year');               // ex: 2026
            $table->unsignedTinyInteger('period_month')->default(0);   // 0 (não aplica) ou 1..12
            $table->unsignedTinyInteger('period_week_of_month')->default(0); // 0 (não aplica) ou 1..6

            // Quando payments_per_interval > 1 (ex.: 2x no mesmo mês/semana/ano)
            $table->unsignedTinyInteger('sequence_in_period')->default(1); // 1..N

            // snapshot do valor no momento do pagamento
            $table->decimal('amount', 12, 2);

            $table->timestamp('paid_at')->useCurrent();

            // Comprovante opcional
            $table->string('receipt_path', 500)->nullable();

            $table->timestamps();

            $table->unique([
                'collective_project_id',
                'user_id',
                'period_year',
                'period_month',
                'period_week_of_month',
                'sequence_in_period',
            ], 'uniq_project_user_period_sequence');

            $table->index(['collective_project_id', 'user_id', 'paid_at'], 'idx_project_user_paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collective_project_payments');
    }
};
