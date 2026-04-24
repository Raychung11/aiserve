<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address');
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('postcode', 10);
            $table->enum('property_type', ['condo', 'serviced_apartment', 'landed', 'commercial', 'soho', 'sofo'])->default('condo');
            $table->string('strata_building')->nullable();
            $table->boolean('is_strata')->default(true);
            $table->integer('bedrooms')->default(1);
            $table->integer('bathrooms')->default(1);
            $table->decimal('area_sqft', 8, 2)->nullable();
            $table->enum('compliance_status', ['green', 'amber', 'red'])->default('amber');
            $table->text('compliance_notes')->nullable();
            $table->enum('strategy_mode', ['STR', 'MID_TERM', 'SUBLET', 'CORPORATE'])->default('STR');
            $table->enum('listing_status', ['active', 'inactive', 'pending', 'maintenance'])->default('pending');
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('owner_name')->nullable();
            $table->string('owner_phone', 20)->nullable();
            $table->string('owner_email')->nullable();
            $table->decimal('monthly_target', 10, 2)->default(0);
            $table->string('airbnb_url')->nullable();
            $table->string('booking_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
