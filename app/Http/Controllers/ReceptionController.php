<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Support\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/** The all-rooms timeline for reception and admins. */
class ReceptionController extends Controller
{
    public function today(Request $request)
    {
        $date = Clock::isValidDate($request->query('date')) ? $request->query('date') : Clock::today();
        $rooms = Room::listed()->get();
        $bookings = Booking::active()->onDate($date)->with('organizer')->orderBy('start_min')->get()->groupBy('room_id');

        $open = config('eureka.open');
        $close = config('eureka.close');
        $slot = config('eureka.slot');
        $isToday = $date === Clock::today();
        $nowMin = Clock::nowMinutes();

        $grid = $rooms->map(function (Room $room) use ($bookings, $open, $close, $slot, $isToday, $nowMin) {
            $list = $bookings->get($room->id, collect());
            $cells = [];
            for ($m = $open; $m < $close; $m += $slot) {
                $b = $list->first(fn ($x) => $x->start_min <= $m && $x->end_min > $m);
                if ($b && $b->start_min !== $m && $m !== $open) {
                    continue; // covered by a block that started earlier
                }
                if ($b) {
                    $from = max($b->start_min, $open);
                    $span = (int) ceil((min($b->end_min, $close) - $from) / $slot);
                    $cells[] = ['type' => 'booking', 'booking' => $b, 'span' => $span];
                    $m = $from + ($span - 1) * $slot;

                    continue;
                }
                $cells[] = ['type' => ($isToday && $m + $slot <= $nowMin) ? 'past' : 'free', 'start' => $m, 'span' => 1];
            }

            return ['room' => $room, 'cells' => $cells];
        });

        $busyNow = $isToday ? $bookings->flatten(1)->filter(fn ($b) => $b->start_min <= $nowMin && $b->end_min > $nowMin)->pluck('room_id')->unique()->count() : null;

        return view('reception.today', [
            'date' => $date,
            'grid' => $grid,
            'hours' => range($open, $close - 60, 60),
            'total' => $bookings->flatten(1)->count(),
            'freeNow' => $isToday ? $rooms->count() - $busyNow : null,
            'prev' => $date > Clock::today() ? CarbonImmutable::parse($date)->subDay()->format('Y-m-d') : null,
            'next' => CarbonImmutable::parse($date)->addDay()->format('Y-m-d'),
            'peek' => $request->filled('booking') ? Booking::with('room', 'organizer')->find($request->integer('booking')) : null,
        ]);
    }
}
