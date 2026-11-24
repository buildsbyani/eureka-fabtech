<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    private Room $boardroom;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        // Book on a future weekday so start times are never in the past.
        $this->date = CarbonImmutable::now('Asia/Kolkata')->addDays(2)->format('Y-m-d');
        $this->room = Room::create(['name' => 'Vikram Sarabhai', 'capacity' => 8]);
        $this->boardroom = Room::create(['name' => 'Boardroom', 'capacity' => 14, 'senior_only' => true]);
    }

    private function user(array $attrs = []): User
    {
        static $n = 0;
        $n++;

        return User::create(array_merge([
            'name' => 'Person '.$n, 'emp_id' => 'person.'.$n, 'email' => "p{$n}@example.com",
            'role' => 'employee', 'senior' => false, 'active' => true, 'must_change_password' => false, 'password' => 'Secret123',
        ], $attrs));
    }

    private function book(User $user, array $overrides = [])
    {
        return $this->actingAs($user)->post('/bookings', array_merge([
            'room_id' => $this->room->id, 'date' => $this->date, 'start_min' => 600, 'duration' => 60, 'title' => 'Sync',
        ], $overrides));
    }

    public function test_employee_can_book_a_free_room(): void
    {
        $this->book($this->user())->assertRedirect();
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_overlapping_booking_is_refused(): void
    {
        $this->book($this->user());
        $this->book($this->user(), ['start_min' => 630])->assertSessionHasErrors('start_min');
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_back_to_back_bookings_are_allowed(): void
    {
        $this->book($this->user());
        $this->book($this->user(), ['start_min' => 660])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_non_senior_cannot_book_the_boardroom(): void
    {
        $this->book($this->user(), ['room_id' => $this->boardroom->id])->assertSessionHasErrors('room_id');
        $this->book($this->user(['senior' => true]), ['room_id' => $this->boardroom->id])->assertSessionHasNoErrors();
    }

    public function test_reception_can_book_the_boardroom_for_a_senior_only(): void
    {
        $reception = $this->user(['role' => 'reception']);
        $this->book($reception, ['room_id' => $this->boardroom->id, 'organizer_id' => $this->user()->id])->assertSessionHasErrors('room_id');
        $senior = $this->user(['senior' => true]);
        $this->book($reception, ['room_id' => $this->boardroom->id, 'organizer_id' => $senior->id])->assertSessionHasNoErrors();
        $this->assertSame($senior->id, Booking::first()->organizer_id);
    }

    public function test_weekly_repeat_skips_taken_weeks(): void
    {
        $next = CarbonImmutable::parse($this->date)->addWeek()->format('Y-m-d');
        $this->book($this->user(), ['date' => $next]);
        $this->book($this->user(), ['repeat_weeks' => 4])->assertSessionHasNoErrors();
        $this->assertSame(4, Booking::count()); // 1 existing + 3 of the 4 weeks
    }

    public function test_cancelling_frees_the_slot(): void
    {
        $owner = $this->user();
        $this->book($owner);
        $this->actingAs($owner)->post('/bookings/'.Booking::first()->id.'/cancel')->assertRedirect();
        $this->book($this->user())->assertSessionHasNoErrors();
    }

    public function test_temporary_password_forces_a_change(): void
    {
        $user = $this->user(['must_change_password' => true]);
        $this->actingAs($user)->get('/')->assertRedirect('/password');
    }
}
