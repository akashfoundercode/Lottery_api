<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_assignments', function (Blueprint $table) {
            $table->id();

            // Book
            $table->foreignId('book_id')
                ->constrained('books')
                ->cascadeOnDelete();

            // Agent
            $table->foreignId('agent_id')
                ->constrained('agents')
                ->cascadeOnDelete();

            // Assignment
            $table->timestamp('assigned_at')->useCurrent();

            // Optional expiry
            $table->timestamp('expiry_at')->nullable();

            $table->timestamps();

            // Prevent duplicate active assignment records for same book/agent
            $table->index(['book_id', 'agent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_assignments');
    }
};
