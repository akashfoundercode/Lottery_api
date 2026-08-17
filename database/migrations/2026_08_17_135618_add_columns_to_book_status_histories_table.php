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
        Schema::table('book_status_histories', function (Blueprint $table) {
            $table->foreignId('book_id')
                ->after('id')
                ->constrained('books')
                ->cascadeOnDelete();

            $table->foreignId('agent_id')
                ->after('book_id')
                ->nullable()
                ->constrained('agents')
                ->nullOnDelete();

            $table->string('old_status', 50)
                ->after('agent_id');

            $table->string('new_status', 50)
                ->after('old_status');

            $table->timestamp('changed_at')
                ->after('new_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('book_status_histories', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropForeign(['book_id']);
            $table->dropColumn([
                'agent_id',
                'book_id',
                'old_status',
                'new_status',
                'changed_at',
            ]);
        });
    }
};
