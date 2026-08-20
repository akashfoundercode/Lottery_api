<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Game;
use App\Models\Result;
use App\Models\ResultPrize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ResultApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_result_with_image(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $game = Game::create([
            'game_name' => 'Premium Draw',
            'game_id' => 'GAME-RES-01',
            'ticket_price' => 100,
            'book_size' => 10,
            'total_books' => 1,
            'draw_date' => now()->addDay(),
            'draw_time' => '18:00:00',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->postJson('/api/v1/admin/results', [
                'game_id' => $game->id,
                'title' => 'Week 42 Result',
                'result_date' => now()->addDay()->format('Y-m-d'),
                'description' => 'Winner details for week 42.',
                'result_image' => UploadedFile::fake()->image('result.jpg'),
                'status' => 'active',
                'prizes' => [
                    [
                        'rank' => 1,
                        'prize_name' => 'First Prize',
                        'prize_type' => 'ticket_winner',
                        'prize_amount' => 5000,
                        'winner_name' => 'Ravi Kumar',
                        'winner_ticket_number' => 'TICKET-001',
                        'winner_book_number' => 'BOOK-001',
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Week 42 Result')
            ->assertJsonPath('data.description', 'Winner details for week 42.')
            ->assertJsonPath('data.prizes.0.prize_name', 'First Prize')
            ->assertJsonPath('data.prizes.0.winner_name', 'Ravi Kumar')
            ->assertJsonPath('data.prizes.0.winner_ticket_number', 'TICKET-001')
            ->assertJsonPath('data.prizes.0.winner_book_number', 'BOOK-001');

        $this->assertDatabaseHas('results', [
            'game_id' => $game->id,
            'title' => 'Week 42 Result',
            'description' => 'Winner details for week 42.',
        ]);

        $this->assertDatabaseHas('result_prizes', [
            'rank' => 1,
            'prize_name' => 'First Prize',
            'prize_type' => 'ticket_winner',
            'winner_name' => 'Ravi Kumar',
            'winner_ticket_number' => 'TICKET-001',
            'winner_book_number' => 'BOOK-001',
        ]);
    }

    public function test_user_can_fetch_active_results_with_pagination(): void
    {
        $game = Game::create([
            'game_name' => 'Public Draw',
            'game_id' => 'GAME-RES-02',
            'ticket_price' => 100,
            'book_size' => 10,
            'total_books' => 1,
            'draw_date' => now()->addDay(),
            'draw_time' => '18:00:00',
            'status' => 'completed',
        ]);

        Result::create([
            'game_id' => $game->id,
            'title' => 'Published Result',
            'result_date' => now()->subDay(),
            'status' => 'active',
            'result_image' => 'results/public-1.jpg',
        ]);

        Result::create([
            'game_id' => $game->id,
            'title' => 'Draft Result',
            'result_date' => now()->subDays(2),
            'status' => 'inactive',
            'result_image' => 'results/draft-1.jpg',
        ]);

        Result::create([
            'game_id' => $game->id,
            'title' => 'Older Published Result',
            'result_date' => now()->subDays(3),
            'status' => 'active',
            'result_image' => 'results/public-2.jpg',
        ]);

        $response = $this->getJson('/api/v1/user/results?offset=1&limit=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.results.0.title', 'Older Published Result')
            ->assertJsonCount(1, 'data.results');
    }

    public function test_admin_can_update_result_prize_name(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin-update@example.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $game = Game::create([
            'game_name' => 'Update Draw',
            'game_id' => 'GAME-RES-UPD',
            'ticket_price' => 100,
            'book_size' => 10,
            'total_books' => 1,
            'draw_date' => now()->addDay(),
            'draw_time' => '18:00:00',
            'status' => 'completed',
        ]);

        $result = Result::create([
            'game_id' => $game->id,
            'title' => 'Old Result',
            'result_date' => now()->subDay(),
            'status' => 'active',
        ]);

        ResultPrize::create([
            'result_id' => $result->id,
            'rank' => 1,
            'prize_name' => 'Old Prize',
            'prize_type' => 'ticket_winner',
            'prize_amount' => 1000,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->putJson('/api/v1/admin/results/'.$result->id, [
                'title' => 'Updated Result',
                'prizes' => [
                    [
                        'rank' => 1,
                        'prize_name' => 'Updated First Prize',
                        'prize_type' => 'book_winner',
                        'prize_amount' => 7500,
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Result')
            ->assertJsonPath('data.prizes.0.prize_name', 'Updated First Prize');

        $this->assertDatabaseHas('result_prizes', [
            'result_id' => $result->id,
            'rank' => 1,
            'prize_name' => 'Updated First Prize',
            'prize_type' => 'book_winner',
            'prize_amount' => 7500,
        ]);
    }

    public function test_admin_can_toggle_result_status(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $game = Game::create([
            'game_name' => 'Toggle Draw',
            'game_id' => 'GAME-RES-03',
            'ticket_price' => 100,
            'book_size' => 10,
            'total_books' => 1,
            'draw_date' => now()->addDay(),
            'draw_time' => '18:00:00',
            'status' => 'completed',
        ]);

        $result = Result::create([
            'game_id' => $game->id,
            'title' => 'Toggle Result',
            'result_date' => now()->subDay(),
            'status' => 'active',
            'result_image' => 'results/toggle-1.jpg',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->patchJson('/api/v1/admin/results/'.$result->id.'/toggle-status');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'inactive');
    }
}
