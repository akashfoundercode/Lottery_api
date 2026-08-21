<?php

namespace App\Jobs;

use App\Enums\BookStatus;
use App\Enums\GameStatus;
use App\Models\Book;
use App\Models\BookStatusHistory;
use App\Models\Game;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class MarkExpiredBooksUnsoldByAdmin implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Active games jinhe live hue 60+ minute ho gaye
        $games = Game::where('status', GameStatus::ACTIVE)
            ->whereNotNull('went_live_at')
            ->where('went_live_at', '<=', now()->subMinutes(60))
            ->get();

        foreach ($games as $game) {
            $books = Book::where('game_id', $game->id)
                ->where('status', BookStatus::ASSIGNED)
                ->get();

            if ($books->isEmpty()) {
                continue;
            }

            DB::transaction(function () use ($books) {
                foreach ($books as $book) {
                    $book->update([
                        'status'    => BookStatus::UNSOLD_BY_ADMIN,
                        'unsold_at' => now(),
                    ]);
                    BookStatusHistory::create([
                        'book_id'    => $book->id,
                        'agent_id'   => $book->agent_id,
                        'old_status' => BookStatus::ASSIGNED->value,
                        'new_status' => BookStatus::UNSOLD_BY_ADMIN->value,
                        'changed_at' => now(),
                    ]);
                }
            });
        }
    }
}
