<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['is_admin' => false]);
    }

    /** @test */
    public function user_can_create_booking()
    {
        Sanctum::actingAs($this->user);

        $data = [
            'date' => '2026-01-20',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ];

        $response = $this->postJson('/api/bookings', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'booking' => ['id', 'date', 'start_time', 'end_time']
            ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->user->id,
            'date' => '2026-01-20',
        ]);
    }

    /** @test */
    public function user_can_update_their_own_booking()
    {
        Sanctum::actingAs($this->user);

        $booking = Booking::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/bookings/{$booking->id}", [
            'date' => '2026-01-25',
            'start_time' => '11:00',
            'end_time' => '12:00',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Booking updated successfully'
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'date' => '2026-01-25',
        ]);
    }

    /** @test */
    public function user_can_delete_their_own_booking()
    {
        Sanctum::actingAs($this->user);

        $booking = Booking::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Booking deleted successfully'
            ]);

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }
}
