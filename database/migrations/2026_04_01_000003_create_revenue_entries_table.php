<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);  // YYYY-MM
            $table->enum('type', ['income', 'expense']);
            $table->enum('category', [
                'rental',
                'cleaning',
                'utilities',
                'maintenance',
                'platform_fee',
                'commission',
                'insurance',
                'assessment',
                'management_fee',
                'other',
            ]);
            $table->decimal('amount', 10, 2);
            $table->string('description')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_ref', 100)->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'property_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_entries');
    }
};
