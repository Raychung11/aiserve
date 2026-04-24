<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('tenant_name');
            $table->string('tenant_phone', 20)->nullable();
            $table->string('tenant_email')->nullable();
            $table->string('tenant_ic', 20)->nullable();
            $table->string('tenant_company')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('monthly_rent', 10, 2);
            $table->decimal('deposit', 10, 2)->default(0);
            $table->boolean('deposit_paid')->default(false);
            $table->enum('type', ['STR', 'MID_TERM', 'SUBLET', 'CORPORATE'])->default('MID_TERM');
            $table->enum('status', ['active', 'expired', 'terminated', 'pending'])->default('pending');
            $table->timestamp('renewal_notified_at')->nullable();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('agreement_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenancies');
    }
};
