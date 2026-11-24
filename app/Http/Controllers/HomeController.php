<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ReadsSlotQuery;
use App\Models\Booking;
use App\Models\Room;
use App\Services\BookingService;
use App\Support\Clock;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use ReadsSlotQuery;

    public function index(Request $request, BookingService $service)
    {
        $user = $request->user();
        $q = $this->slotQuery($request);

        $fits = $service->availability($user, $q['date'], $q['start'], $q['end'], $q['minSeats'])
            ->filter(fn ($row) => $row['state'] === 'free' && ! $row['room']->senior_only)
            ->values();

        $now = Clock::nowMinutes();
        $today = Clock::today();
        $busyNow = Booking::active()->onDate($today)->where('start_min', '<=', $now)->where('end_min', '>', $now)->pluck('room_id')->unique();
        $freeNow = Room::listed()->get()
            ->filter(fn ($r) => ! $busyNow->contains($r->id) && (! $r->senior_only || $user->canUseSeniorRooms()))
            ->count();

        $next = Booking::active()->with('room')->where('organizer_id', $user->id)
            ->where(fn ($w) => $w->whereDate('date', '>', $today)
                ->orWhere(fn ($x) => $x->whereDate('date', $today)->where('end_min', '>', $now)))
            ->orderBy('date')->orderBy('start_min')->first();

        $hour = Clock::now()->hour;

        return view('home', [
            'q' => $q,
            'fits' => $fits,
            'best' => $fits->first(),
            'freeNow' => $freeNow,
            'roomCount' => Room::listed()->count(),
            'next' => $next,
            'greeting' => $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening'),
        ]);
    }
}
