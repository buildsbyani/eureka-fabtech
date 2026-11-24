<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomAdminController extends Controller
{
    public function index()
    {
        return view('admin.rooms', ['rooms' => Room::orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] = (int) Room::max('sort_order') + 1;
        Room::create($data);

        return back()->with('ok', $data['name'].' added.');
    }

    public function update(Request $request, Room $room)
    {
        $room->update($this->validated($request, $room));

        return back()->with('ok', $room->name.' saved.');
    }

    private function validated(Request $request, ?Room $room = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('rooms', 'name')->ignore($room?->id)],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'equipment' => ['nullable', 'string', 'max:120'],
        ]);
        $data['senior_only'] = $request->boolean('senior_only');
        $data['active'] = $request->boolean('active', true);

        return $data;
    }
}
