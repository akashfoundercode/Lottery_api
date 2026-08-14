<?php

use App\Enums\GameStatus;
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
        Schema::create('games', function (Blueprint $table) {
            $table->id();

            // Game Details
            $table->string('game_name', 100);
            $table->string('game_id', 50)->unique();
            $table->string('game_image')->nullable();

            // Ticket Details
            $table->decimal('ticket_price', 10, 2);

            // Book Details
            $table->unsignedInteger('book_size');
            $table->unsignedInteger('total_books');

            // Draw Details
            $table->date('draw_date');
            $table->time('draw_time');

            // Live Streaming
            $table->string('youtube_live_url')->nullable();
            $table->string('facebook_live_url')->nullable();

            // Game Status
            $table->enum(
                'status',
                array_column(GameStatus::cases(), 'value')
            )->default(GameStatus::ACTIVE->value);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};