<?php

namespace App\Services;

use App\Mail\NoticeMail;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Support\Clock;
use App\Support\Ics;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Every listed room with its state for a slot: free, busy or restricted.
     *
     * @return Collection<int, array{room: Room, state: string, note: string}>
     */
    public function availability(User $user, string $date, int $start, int $end, int $minSeats = 1): Collection
    {
        $rooms = Room::listed()->where('capacity', '>=', $minSeats)->get();
        $day = Booking::active()->onDate($date)->get()->groupBy('room_id');

        return $rooms->map(function (Room $room) use ($user, $day, $start, $end) {
            if ($room->senior_only && ! $user->canUseSeniorRooms()) {
                return ['room' => $room, 'state' => 'restricted', 'note' => 'Senior staff only. Ask reception to book it.'];
            }
            if ($end > config('eureka.close')) {
                return ['room' => $room, 'state' => 'busy', 'note' => 'Bookings end by '.Clock::time(config('eureka.close'))];
            }
            $list = $day->get($room->id, collect());
            $clash = $list->filter(fn ($b) => $b->start_min < $end && $b->end_min > $start);
            if ($clash->isNotEmpty()) {
                return ['room' => $room, 'state' => 'busy', 'note' => 'Busy until '.Clock::time($clash->max('end_min'))];
            }
            $next = $list->filter(fn ($b) => $b->start_min >= $end)->sortBy('start_min')->first();

            return ['room' => $room, 'state' => 'free', 'note' => $next ? 'Free until '.Clock::time($next->start_min) : 'Free for the rest of the day'];
        })->sortBy(fn ($row) => [['free' => 0, 'busy' => 1, 'restricted' => 2][$row['state']], $row['room']->capacity])->values();
    }

    /**
     * Book a room once, or weekly for a number of weeks. Weeks where the room is taken are skipped.
     *
     * @return array{created: Collection<int, Booking>, skipped: array<int, string>}
     */
    public function book(User $actor, array $input): array
    {
        $room = Room::listed()->findOrFail($input['room_id']);
        $organizer = $actor->isStaff() && ! empty($input['organizer_id'])
            ? User::where('active', true)->findOrFail($input['organizer_id'])
            : $actor;

        $start = (int) $input['start_min'];
        $end = $start + (int) $input['duration'];
        $date = $input['date'];
        $weeks = min((int) ($input['repeat_weeks'] ?? 0), config('eureka.max_repeat_weeks'));
        $invitees = $this->parseEmails($input['invitees'] ?? '');

        if (! in_array($start, Clock::startOptions($date), true) || $end > config('eureka.close')) {
            throw ValidationException::withMessages(['start_min' => 'Pick a start time between '.Clock::time(config('eureka.open')).' and '.Clock::time(config('eureka.close')).' that has not passed.']);
        }
        if ($room->senior_only && ! $organizer->senior) {
            throw ValidationException::withMessages(['room_id' => 'The '.$room->name.' is for senior staff. '.$organizer->name.' is not marked as senior.']);
        }

        $dates = [$date];
        for ($w = 1; $w < max($weeks, 1); $w++) {
            $dates[] = CarbonImmutable::parse($date)->addWeeks($w)->format('Y-m-d');
        }
        $seriesId = $weeks > 1 ? (string) Str::uuid() : null;

        [$created, $skipped] = DB::transaction(function () use ($room, $dates, $start, $end, $input, $organizer, $actor, $invitees, $seriesId) {
            // Lock the room row so two people cannot book the same slot at the same moment.
            Room::whereKey($room->id)->lockForUpdate()->first();
            $created = collect();
            $skipped = [];
            foreach ($dates as $d) {
                if (Booking::overlapping($room->id, $d, $start, $end)->exists()) {
                    $skipped[] = Clock::dayLabel($d);

                    continue;
                }
                $created->push(Booking::create([
                    'uid' => (string) Str::uuid(),
                    'room_id' => $room->id,
                    'date' => $d,
                    'start_min' => $start,
                    'end_min' => $end,
                    'title' => trim($input['title']),
                    'organizer_id' => $organizer->id,
                    'booked_by_id' => $actor->id,
                    'invitees' => $invitees,
                    'status' => 'active',
                    'series_id' => $seriesId,
                ]));
            }

            return [$created, $skipped];
        });

        if ($created->isEmpty()) {
            throw ValidationException::withMessages(['start_min' => $room->name.' is already booked at that time. Pick another time or room.']);
        }

        $this->notifyBooked($created, $skipped, $actor);

        return ['created' => $created, 'skipped' => $skipped];
    }

    /** Add up to 12 more weekly meetings after the last one in a series. */
    public function extendSeries(Booking $last, User $actor): array
    {
        $result = $this->book($actor, [
            'room_id' => $last->room_id,
            'date' => CarbonImmutable::parse($last->dateString())->addWeek()->format('Y-m-d'),
            'start_min' => $last->start_min,
            'duration' => $last->end_min - $last->start_min,
            'title' => $last->title,
            'organizer_id' => $last->organizer_id,
            'invitees' => implode(',', $last->invitees()),
            'repeat_weeks' => config('eureka.max_repeat_weeks'),
        ]);
        Booking::whereIn('id', $result['created']->pluck('id'))->update(['series_id' => $last->series_id]);

        return $result;
    }

    public function cancel(Booking $booking, User $actor, bool $wholeSeries = false): int
    {
        $targets = $wholeSeries && $booking->series_id
            ? Booking::active()->where('series_id', $booking->series_id)->whereDate('date', '>=', Clock::today())->get()
            : collect([$booking])->filter(fn ($b) => $b->status === 'active');

        foreach ($targets as $b) {
            $b->update(['status' => 'cancelled', 'cancelled_at' => now(), 'sequence' => $b->sequence + 1]);
        }
        if ($targets->isNotEmpty()) {
            $first = $targets->sortBy('date')->first()->load('room', 'organizer');
            $data = ['booking' => $first, 'count' => $targets->count(), 'by' => $actor];
            $ics = $targets->count() === 1 ? Ics::forBooking($first, 'CANCEL') : null;
            foreach (array_unique(array_merge([$first->organizer->email], $first->invitees())) as $email) {
                Mail::to($email)->send(new NoticeMail('Cancelled: '.$first->title, 'cancelled', $data, $ics, 'CANCEL'));
            }
        }

        return $targets->count();
    }

    public function release(Booking $booking, string $reason): void
    {
        if ($booking->status !== 'active') {
            return;
        }
        $booking->update(['status' => 'released', 'cancelled_at' => now(), 'sequence' => $booking->sequence + 1]);
        $booking->load('room', 'organizer');
        Mail::to($booking->organizer->email)->send(new NoticeMail(
            'Released: '.$booking->room->name.' at '.Clock::time($booking->start_min),
            'released',
            ['booking' => $booking, 'reason' => $reason],
        ));
    }

    private function notifyBooked(Collection $created, array $skipped, User $actor): void
    {
        $first = $created->first()->load('room', 'organizer');
        $data = ['booking' => $first, 'count' => $created->count(), 'skipped' => $skipped, 'by' => $actor];
        $ics = Ics::forBooking($first);

        Mail::to($first->organizer->email)->send(new NoticeMail(
            'Booked: '.$first->room->name.', '.$first->dayLabel().' '.Clock::time($first->start_min),
            'booked', $data, $ics,
        ));
        if ($actor->id !== $first->organizer_id) {
            Mail::to($actor->email)->send(new NoticeMail('Copy: '.$first->title.' for '.$first->organizer->name, 'booked', $data));
        }
        foreach ($first->invitees() as $email) {
            Mail::to($email)->send(new NoticeMail('Invitation: '.$first->title, 'invitation', $data, $ics));
        }
    }

    /** @return array<int, string> */
    public function parseEmails(string $raw): array
    {
        return collect(preg_split('/[\s,;]+/', strtolower($raw)) ?: [])
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()->take(30)->values()->all();
    }
}
