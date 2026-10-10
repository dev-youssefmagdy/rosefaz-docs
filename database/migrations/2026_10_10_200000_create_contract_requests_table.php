<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no')->unique();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->date('request_date');
            $table->string('full_name');
            $table->string('id_number', 10);
            $table->date('birth_date');
            $table->string('mobile', 10);
            $table->boolean('needs_electricity_bill')->default(false);
            $table->string('apartment_no')->nullable();
            $table->decimal('annual_rent', 12, 2);
            $table->decimal('insurance_amount', 12, 2);
            $table->decimal('authentication_amount', 12, 2);
            $table->string('rental_type');
            $table->unsignedSmallInteger('contract_duration_months')->default(12);
            $table->date('rental_date');
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('remaining_amount', 12, 2);
            $table->date('start_date');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_requests');
    }
};
