<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('type', 50); // book_assigned, book_expired, book_sold, book_unsold, etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // extra info like book_id, game_name etc.
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_notifications');
    }
};
