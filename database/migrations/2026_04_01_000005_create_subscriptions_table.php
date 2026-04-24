<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('plan', ['starter', 'growth', 'enterprise']);
            $table->enum('status', ['active', 'pending', 'expired', 'cancelled'])->default('pending');
            $table->decimal('amount', 10, 2);
            $table->enum('billing_cycle', ['monthly', 'annually'])->default('monthly');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('billplz_bill_id')->nullable();
            $table->string('billplz_collection_id')->nullable();
            $table->string('billplz_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
