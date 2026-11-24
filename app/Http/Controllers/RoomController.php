<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ReadsSlotQuery;
use App\Services\BookingService;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    use ReadsSlotQuery;

    public function index(Request $request, BookingService $service)
    {
        $q = $this->slotQuery($request);
        $rows = $service->availability($request->user(), $q['date'], $q['start'], $q['end'], $q['minSeats']);

        return view('rooms.index', [
            'q' => $q,
            'rows' => $rows,
            'freeCount' => $rows->where('state', 'free')->count(),
        ]);
    }
}
