<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // Book
            $table->foreignId('book_id')
                ->constrained('books')
                ->cascadeOnDelete();

            // Game
            $table->foreignId('game_id')
                ->constrained('games')
                ->cascadeOnDelete();

            // Ticket Number
            $table->string('ticket_number', 50)->unique();

            $table->timestamps();

            // Indexes
            $table->index('book_id');
            $table->index('game_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};