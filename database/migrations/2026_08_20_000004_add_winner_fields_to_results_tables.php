<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->text('description')->nullable()->after('result_date');
        });

        Schema::table('result_prizes', function (Blueprint $table) {
            $table->string('winner_name')->nullable()->after('prize_image');
            $table->string('winner_ticket_number', 50)->nullable()->after('winner_name');
            $table->string('winner_book_number', 50)->nullable()->after('winner_ticket_number');
        });
    }

    public function down(): void
    {
        Schema::table('result_prizes', function (Blueprint $table) {
            $table->dropColumn([
                'winner_name',
                'winner_ticket_number',
                'winner_book_number',
            ]);
        });

        Schema::table('results', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
