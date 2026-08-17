<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Book;
use App\Models\Game;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_require_q_parameter(): void
    {
        $response = $this->getJson('/api/v1/user/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    public function test_search_returns_matching_results_for_game_name_and_ticket_number(): void
    {
        $game = Game::create([
            'game_name' => 'Summer Lottery Deluxe',
            'game_id' => 'LOT-1001',
            'ticket_price' => 50.00,
            'book_size' => 10,
            'total_books' => 1,
            'draw_date' => '2026-09-10',
            'draw_time' => '18:30:00',
            'status' => 'active',
        ]);

        $book = Book::create([
            'game_id' => $game->id,
            'book_id' => 'BK0001',
            'agent_id' => null,
            'total_tickets' => 1,
            'draw_date' => '2026-09-10',
            'draw_time' => '18:30:00',
            'status' => 'available',
        ]);

        Ticket::create([
            'book_id' => $book->id,
            'game_id' => $game->id,
            'ticket_number' => '123456',
        ]);

        $response = $this->getJson('/api/v1/user/search?q=lottery');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.results.0.ticket_number', '123456');
    }

    public function test_search_returns_no_results_message(): void
    {
        $response = $this->getJson('/api/v1/user/search?q=does-not-exist');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'No results found.')
            ->assertJsonPath('data.results', []);
    }

    public function test_my_tickets_validates_mobile_number(): void
    {
        $response = $this->postJson('/api/v1/user/my-tickets', [
            'mobile' => '12345',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mobile']);
    }

    public function test_my_tickets_returns_agent_related_tickets_for_valid_mobile_number(): void
    {
        $agent = Agent::create([
            'agent_name' => 'Test Agent',
            'agent_id' => 'AG-1001',
            'mobile_number' => '9876543210',
            'whatsapp_number' => '9876543210',
            'address' => 'Test Address',
            'agent_type' => 'first_party',
            'email' => 'agent@example.com',
            'password' => 'secret1234',
            'status' => 'active',
        ]);

        $game = Game::create([
            'game_name' => 'Vip Draw',
            'game_id' => 'LOT-2002',
            'ticket_price' => 50.00,
            'book_size' => 2,
            'total_books' => 1,
            'draw_date' => '2026-09-12',
            'draw_time' => '18:30:00',
            'status' => 'active',
        ]);

        $book = Book::create([
            'game_id' => $game->id,
            'book_id' => 'BK0002',
            'agent_id' => $agent->id,
            'total_tickets' => 2,
            'draw_date' => '2026-09-12',
            'draw_time' => '18:30:00',
            'status' => 'assigned',
        ]);

        Ticket::create([
            'book_id' => $book->id,
            'game_id' => $game->id,
            'ticket_number' => '777777',
        ]);

        $response = $this->postJson('/api/v1/user/my-tickets', [
            'mobile' => '9876543210',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tickets.0.ticket_number', '777777');
    }
}
