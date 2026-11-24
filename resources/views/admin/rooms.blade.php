@extends('layouts.app')
@section('title', 'Room setup')
@section('content')
@include('partials.header', ['title' => 'Room setup', 'sub' => $rooms->where('active', true)->count().' rooms people can book'])

<div class="stack">
    @foreach ($rooms as $room)
        <form method="POST" action="{{ route('admin.rooms.update', $room) }}" class="tile row wrap" style="gap:14px;{{ $room->active ? '' : 'opacity:.7' }}">
            @csrf @method('PATCH')
            <div class="field" style="flex:2 1 200px"><label class="lbl" for="n{{ $room->id }}">Name</label><input id="n{{ $room->id }}" name="name" value="{{ $room->name }}" required></div>
            <div class="field" style="flex:0 1 110px"><label class="lbl" for="c{{ $room->id }}">Seats</label><input id="c{{ $room->id }}" name="capacity" type="number" min="1" max="200" value="{{ $room->capacity }}" required></div>
            <div class="field" style="flex:2 1 180px"><label class="lbl" for="e{{ $room->id }}">Equipment</label><input id="e{{ $room->id }}" name="equipment" value="{{ $room->equipment }}"></div>
            <label class="row lbl" style="gap:8px;align-self:end;height:48px"><input type="checkbox" name="senior_only" value="1" @checked($room->senior_only) style="width:18px;height:18px;accent-color:#072A4A"> Seniors only</label>
            <label class="row lbl" style="gap:8px;align-self:end;height:48px"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked($room->active) style="width:18px;height:18px;accent-color:#072A4A"> Bookable</label>
            <button class="btn sm" style="align-self:end;margin-bottom:5px">Save</button>
        </form>
    @endforeach

    <form method="POST" action="{{ route('admin.rooms.store') }}" class="tile row wrap" style="gap:14px">
        @csrf
        <h2 style="flex-basis:100%">Add a room</h2>
        <div class="field" style="flex:2 1 200px"><label class="lbl" for="nn">Name</label><input id="nn" name="name" required></div>
        <div class="field" style="flex:0 1 110px"><label class="lbl" for="nc">Seats</label><input id="nc" name="capacity" type="number" min="1" max="200" required></div>
        <div class="field" style="flex:2 1 180px"><label class="lbl" for="ne">Equipment</label><input id="ne" name="equipment" value="TV, Video call"></div>
        <label class="row lbl" style="gap:8px;align-self:end;height:48px"><input type="checkbox" name="senior_only" value="1" style="width:18px;height:18px;accent-color:#072A4A"> Seniors only</label>
        <input type="hidden" name="active" value="1">
        <button class="btn sm blue" style="align-self:end;margin-bottom:5px">Add room</button>
    </form>
</div>
@endsection
