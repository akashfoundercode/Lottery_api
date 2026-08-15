<?php

use App\Enums\BookStatus;
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
        Schema::create('books', function (Blueprint $table) {
            $table->id();

            // Game
            $table->foreignId('game_id')
                ->constrained('games')
                ->cascadeOnDelete();

            // Book Details
            $table->string('book_id', 50)->unique();

            // Agent
            $table->unsignedBigInteger('agent_id')->nullable();

            // Book Details
            $table->unsignedInteger('total_tickets');

            // Draw Details
            $table->date('draw_date');
            $table->time('draw_time');

            // Book Status
            $table->enum(
                'status',
                array_column(BookStatus::cases(), 'value')
            )->default(BookStatus::AVAILABLE->value);

            // Assignment / Expiry
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('expiry_at')->nullable();

            // Sold / Unsold
            $table->timestamp('sold_at')->nullable();
            $table->timestamp('unsold_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('agent_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};