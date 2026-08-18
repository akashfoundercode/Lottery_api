<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Game;
use App\Models\Result;
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
                'result_image' => UploadedFile::fake()->image('result.jpg'),
                'status' => 'active',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Week 42 Result');

        $this->assertDatabaseHas('results', [
            'game_id' => $game->id,
            'title' => 'Week 42 Result',
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

        $response = $this->getJson('/api/v1/user/results?page=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.results.0.title', 'Published Result')
            ->assertJsonCount(1, 'data.results');
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
