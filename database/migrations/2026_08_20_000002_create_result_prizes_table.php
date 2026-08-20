<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();

            // Prize rank: 1st, 2nd, 3rd...
            $table->unsignedTinyInteger('rank');

            // Prize type: book_winner or ticket_winner
            $table->enum('prize_type', ['book_winner', 'ticket_winner']);

            // Prize amount
            $table->decimal('prize_amount', 12, 2);

            // Prize image
            $table->string('prize_image')->nullable();

            // Auto-calculated fields (stored for quick access)
            $table->unsignedInteger('total_books_sold')->default(0);
            $table->unsignedInteger('total_tickets')->default(0); // books_sold * book_size
            $table->decimal('book_price', 10, 2)->default(0);
            $table->decimal('ticket_price', 10, 2)->default(0);

            $table->timestamps();

            $table->index(['result_id', 'rank', 'prize_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_prizes');
    }
};
