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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();

            // Agent Details
            $table->string('agent_name', 100);
            $table->string('agent_id', 50)->unique();

            // Contact Details
            $table->string('mobile_number', 20);
            $table->string('whatsapp_number', 20)->nullable();

            // Address
            $table->text('address')->nullable();

            // Agent Type
            $table->enum('agent_type', [
                'first_party',
                'third_party',
            ]);

            // Login Details
            $table->string('email')->unique();
            $table->string('password');

            // Agent Status
            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
