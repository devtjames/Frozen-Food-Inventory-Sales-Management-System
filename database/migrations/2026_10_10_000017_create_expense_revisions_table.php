<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_revisions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('idempotency_key')->unique();
            $table->foreignId('expense_id')->constrained()->restrictOnDelete();
            $table->json('previous_values');
            $table->json('updated_values');
            $table->text('reason');
            $table->timestamp('changed_at')->index();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['expense_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_revisions');
    }
};
