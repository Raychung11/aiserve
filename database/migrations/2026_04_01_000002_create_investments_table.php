<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('renovation_cost', 10, 2)->default(0);
            $table->decimal('setup_cost', 10, 2)->default(0);
            $table->decimal('furnishing_cost', 10, 2)->default(0);
            $table->decimal('deposit_paid', 10, 2)->default(0);
            $table->decimal('legal_fees', 10, 2)->default(0);
            $table->decimal('stamp_duty', 10, 2)->default(0);
            $table->decimal('other_costs', 10, 2)->default(0);
            $table->date('investment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
