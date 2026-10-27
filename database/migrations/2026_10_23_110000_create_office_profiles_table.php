<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('contract_type', 32); // uop | zlecenie | b2b | other
            $table->string('pay_mode', 16)->default('hourly'); // hourly | salary
            $table->decimal('fte', 3, 2)->default(1); // 0.00–1.00
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->string('currency', 3)->default('PLN');
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'ended_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_profiles');
    }
};
