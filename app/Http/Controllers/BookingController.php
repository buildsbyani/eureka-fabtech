<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingService;
use App\Support\Clock;
use App\Support\Ics;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        $room = Room::listed()->findOrFail($request->integer('room'));
        abort_if($room->senior_only && ! $user->canUseSeniorRooms(), 403, 'This room is for senior staff. Ask reception to book it.');

        $date = Clock::isValidDate($request->query('date')) ? $request->query('date') : Clock::today();
        $options = Clock::startOptions($date);
        $start = (int) $request->query('start', $options[0] ?? config('eureka.open'));
        $duration = (int) $request->query('dur', 60);

        return view('bookings.create', [
            'room' => $room,
            'date' => $date,
            'start' => in_array($start, $options, true) ? $start : ($options[0] ?? null),
            'duration' => in_array($duration, [30, 60, 90, 120, 180], true) ? $duration : 60,
            'startOptions' => $options,
            'people' => $user->isStaff() ? User::where('active', true)->orderBy('name')->get() : collect(),
            'directory' => User::where('active', true)->orderBy('name')->get(['name', 'email']),
            'dayBookings' => Booking::active()->onDate($date)->where('room_id', $room->id)->with('organizer')->orderBy('start_min')->get(),
        ]);
    }

    public function store(Request $request, BookingService $service)
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'date' => ['required', 'date_format:Y-m-d', function ($attr, $value, $fail) {
                if (! Clock::isValidDate($value)) {
                    $fail('Pick a date from today up to '.config('eureka.book_ahead_days').' days ahead.');
                }
            }],
            'start_min' => ['required', 'integer'],
            'duration' => ['required', 'integer', Rule::in([30, 60, 90, 120, 180])],
            'title' => ['required', 'string', 'max:120'],
            'organizer_id' => ['nullable', 'integer', 'exists:users,id'],
            'invitees' => ['nullable', 'string', 'max:2000'],
            'repeat_weeks' => ['nullable', 'integer', Rule::in([0, 2, 4, 8, 12])],
        ], ['title.required' => 'Give the meeting a title.']);

        $result = $service->book($request->user(), $data);
        $first = $result['created']->first();

        return redirect()->route('bookings.confirmed', $first)->with([
            'count' => $result['created']->count(),
            'skipped' => $result['skipped'],
        ]);
    }

    public function confirmed(Request $request, Booking $booking)
    {
        $this->authorizeView($request->user(), $booking);

        return view('bookings.confirmed', [
            'booking' => $booking->load('room', 'organizer'),
            'count' => session('count', 1),
            'skipped' => session('skipped', []),
        ]);
    }

    public function mine(Request $request)
    {
        $user = $request->user();
        $tab = in_array($request->query('tab'), ['upcoming', 'past', 'cancelled'], true) ? $request->query('tab') : 'upcoming';
        $today = Clock::today();
        $now = Clock::nowMinutes();
        $base = Booking::with('room', 'organizer', 'bookedBy')->involving($user);

        $list = match ($tab) {
            'upcoming' => $base->active()->where(fn ($w) => $w->whereDate('date', '>', $today)
                ->orWhere(fn ($x) => $x->whereDate('date', $today)->where('end_min', '>', $now)))
                ->orderBy('date')->orderBy('start_min')->limit(100)->get(),
            'past' => $base->active()->where(fn ($w) => $w->whereDate('date', '<', $today)
                ->orWhere(fn ($x) => $x->whereDate('date', $today)->where('end_min', '<=', $now)))
                ->orderByDesc('date')->orderByDesc('start_min')->limit(50)->get(),
            'cancelled' => $base->whereIn('status', ['cancelled', 'released'])
                ->orderByDesc('date')->limit(50)->get(),
        };

        // Recurring series that end within 7 days: prompt the organiser to extend.
        $ending = Booking::active()->where('organizer_id', $user->id)->whereNotNull('series_id')
            ->get()->groupBy('series_id')
            ->map(fn ($g) => $g->sortBy('date')->last())
            ->filter(fn ($b) => $b->dateString() >= $today && $b->dateString() <= Clock::now()->addDays(7)->format('Y-m-d'))
            ->values();

        return view('bookings.mine', compact('tab', 'list', 'ending'));
    }

    public function cancel(Request $request, Booking $booking, BookingService $service)
    {
        $user = $request->user();
        $this->authorizeChange($user, $booking);
        $count = $service->cancel($booking, $user, $request->boolean('series'));

        return back()->with('ok', $count > 1 ? $count.' meetings cancelled. Everyone invited has been told.' : 'Booking cancelled. Everyone invited has been told.');
    }

    public function extend(Request $request, Booking $booking, BookingService $service)
    {
        $user = $request->user();
        $this->authorizeChange($user, $booking);
        abort_unless($booking->series_id, 422);
        $last = Booking::active()->where('series_id', $booking->series_id)->orderByDesc('date')->first();
        $result = $service->extendSeries($last, $user);
        $msg = $result['created']->count().' more weeks booked.';
        if ($result['skipped']) {
            $msg .= ' Skipped '.implode(', ', $result['skipped']).' because the room was taken.';
        }

        return back()->with('ok', $msg);
    }

    public function checkIn(Request $request, Booking $booking)
    {
        $this->authorizeChange($request->user(), $booking);
        if (! $booking->canCheckIn()) {
            return back()->withErrors(['booking' => 'Check-in opens 15 minutes before the start.']);
        }
        $booking->update(['checked_in_at' => now()]);

        return back()->with('ok', 'Thanks. The room stays yours.');
    }

    public function ics(Request $request, Booking $booking)
    {
        $this->authorizeView($request->user(), $booking);

        return response(Ics::forBooking($booking), 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="eureka-booking.ics"',
        ]);
    }

    private function authorizeView(User $user, Booking $booking): void
    {
        abort_unless($user->isStaff() || in_array($user->id, [$booking->organizer_id, $booking->booked_by_id], true), 403);
    }

    private function authorizeChange(User $user, Booking $booking): void
    {
        $this->authorizeView($user, $booking);
        abort_if($booking->status !== 'active', 422, 'This booking is no longer active.');
    }
}
