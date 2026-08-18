<?php

namespace Tests\Feature;

use App\Enums\BookStatus;
use App\Models\Admin;
use App\Models\Agent;
use App\Models\Book;
use App\Models\BookAssignment;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BookAssignmentExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_assigned_books_are_marked_unsold_by_admin(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $agent = Agent::create([
            'agent_name' => 'Test Agent',
            'agent_id' => 'AGT-1001',
            'mobile_number' => '9999999999',
            'whatsapp_number' => '9999999999',
            'address' => 'Test Address',
            'agent_type' => 'first_party',
            'email' => 'agent@example.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $game = Game::create([
            'game_name' => 'Test Game',
            'game_id' => 'GAME-1001',
            'ticket_price' => 100,
            'book_size' => 10,
            'total_books' => 1,
            'draw_date' => now()->addDay(),
            'draw_time' => '18:00:00',
            'status' => 'active',
        ]);

        $book = Book::create([
            'game_id' => $game->id,
            'book_id' => 'BK-1001',
            'agent_id' => $agent->id,
            'total_tickets' => 10,
            'draw_date' => now()->addDay(),
            'draw_time' => '18:00:00',
            'status' => BookStatus::ASSIGNED,
            'assigned_at' => now()->subMinutes(90),
            'expiry_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->postJson('/api/v1/admin/book-assignments/expire');

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'status' => BookStatus::UNSOLD_BY_ADMIN->value,
            'agent_id' => null,
        ]);
    }

    public function test_admin_can_import_multiple_books_with_variable_ticket_counts_per_row(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $game = Game::create([
            'game_name' => 'Variable Book Game',
            'game_id' => 'GAME-2001',
            'ticket_price' => 100,
            'book_size' => 10,
            'total_books' => 3,
            'draw_date' => now()->addDay(),
            'draw_time' => '18:00:00',
            'status' => 'active',
        ]);

        $csv = "book_number,serial_number,ticket_1,ticket_2,ticket_3,ticket_4,ticket_5,ticket_6,ticket_7,ticket_8,ticket_9,ticket_10,ticket_11,ticket_12,status\n";
        $csv .= "B-1001,1001,101,102,103,104,105,106,107,108,109,110,,,active\n";
        $csv .= "B-1002,1002,201,202,203,204,205,206,207,208,,,,,active\n";
        $csv .= "B-1003,1003,301,302,303,304,305,306,307,308,309,310,311,312,active\n";

        $response = $this->actingAs($admin, 'admin')
            ->postJson('/api/v1/admin/books/import', [
                'game_id' => $game->id,
                'draw_date' => $game->draw_date->format('Y-m-d'),
                'draw_time' => $game->draw_time,
                'file' => UploadedFile::fake()->createWithContent('books.csv', $csv),
            ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('imported_book_count', 3);
        $response->assertJsonPath('imported_ticket_count', 30);
        $this->assertDatabaseCount('books', 3);
        $this->assertDatabaseCount('tickets', 30);
    }

    public function test_admin_can_fetch_assignment_history(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $agent = Agent::create([
            'agent_name' => 'Assignment Agent',
            'agent_id' => 'AGT-9001',
            'mobile_number' => '9000000001',
            'whatsapp_number' => '9000000001',
            'address' => 'History Address',
            'agent_type' => 'first_party',
            'email' => 'history-agent@example.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $game = Game::create([
            'game_name' => 'History Game',
            'game_id' => 'GAME-9001',
            'ticket_price' => 100,
            'book_size' => 10,
            'total_books' => 1,
            'draw_date' => now()->addDay(),
            'draw_time' => '18:00:00',
            'status' => 'active',
        ]);

        $book = Book::create([
            'game_id' => $game->id,
            'book_id' => 'BK-9001',
            'agent_id' => $agent->id,
            'total_tickets' => 10,
            'draw_date' => now()->addDay(),
            'draw_time' => '18:00:00',
            'status' => BookStatus::ASSIGNED,
            'assigned_at' => now()->subMinutes(30),
            'expiry_at' => now()->addMinutes(30),
        ]);

        BookAssignment::create([
            'book_id' => $book->id,
            'agent_id' => $agent->id,
            'assigned_at' => now()->subMinutes(30),
            'expiry_at' => now()->addMinutes(30),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->getJson('/api/v1/admin/book-assignments/history');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.0.agent.agent_id', 'AGT-9001');
        $response->assertJsonPath('data.0.book.book_id', 'BK-9001');
    }

    public function test_import_accepts_book_id_column_and_rejects_missing_book_number_rows(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $game = Game::create([
            'game_name' => 'Book ID Validation',
            'game_id' => 'GAME-3001',
            'ticket_price' => 100,
            'book_size' => 10,
            'total_books' => 1,
            'draw_date' => now()->addDay(),
            'draw_time' => '18:00:00',
            'status' => 'active',
        ]);

        $validCsv = "Book ID,serial_number,ticket_1,ticket_2,ticket_3,ticket_4,status\n";
        $validCsv .= "100001,3001,401,402,403,404,active\n";

        $validResponse = $this->actingAs($admin, 'admin')
            ->postJson('/api/v1/admin/books/import', [
                'game_id' => $game->id,
                'file' => UploadedFile::fake()->createWithContent('book-id-valid.csv', $validCsv),
            ]);

        $validResponse->assertCreated();
        $validResponse->assertJsonPath('success', true);
        $validResponse->assertJsonPath('imported_ticket_count', 4);

        $invalidCsv = "Book ID,serial_number,ticket_1,ticket_2,ticket_3,status\n";
        $invalidCsv .= ",3002,501,502,503,active\n";

        $invalidResponse = $this->actingAs($admin, 'admin')
            ->postJson('/api/v1/admin/books/import', [
                'game_id' => $game->id,
                'file' => UploadedFile::fake()->createWithContent('book-id-invalid.csv', $invalidCsv),
            ]);

        $invalidResponse->assertStatus(422);
        $invalidResponse->assertJsonPath('success', false);
    }

    public function test_import_rejects_duplicate_ticket_numbers_across_rows(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $game = Game::create([
            'game_name' => 'Duplicate Ticket Validation',
            'game_id' => 'GAME-4001',
            'ticket_price' => 100,
            'book_size' => 10,
            'total_books' => 2,
            'draw_date' => now()->addDay(),
            'draw_time' => '18:00:00',
            'status' => 'active',
        ]);

        $csv = "book_number,serial_number,ticket_1,ticket_2,ticket_3,ticket_4,status\n";
        $csv .= "B-4001,4001,501,502,503,504,active\n";
        $csv .= "B-4002,4002,503,505,506,507,active\n";

        $response = $this->actingAs($admin, 'admin')
            ->postJson('/api/v1/admin/books/import', [
                'game_id' => $game->id,
                'file' => UploadedFile::fake()->createWithContent('duplicate.csv', $csv),
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }
}
