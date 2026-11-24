@extends('layouts.app')
@section('title', 'Users')
@section('content')
@include('partials.header', ['title' => 'Users', 'sub' => $counts['all'].' people can sign in'])

<div class="stack">
    <div class="between wrap">
        <div class="chips">
            @foreach (['all' => 'All', 'senior' => 'Senior staff', 'reception' => 'Reception', 'admin' => 'Admins', 'off' => 'Deactivated'] as $key => $label)
                <a class="chip {{ $filter === $key ? 'on' : '' }}" href="{{ route('admin.users.index', ['show' => $key]) }}">{{ $label }} · {{ $counts[$key] }}</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('admin.users.index') }}" class="field" style="width:260px">
            <input type="hidden" name="show" value="{{ $filter }}">
            <label class="sr" for="q">Search users</label>
            <input id="q" name="q" value="{{ $search }}" placeholder="Search by name, ID or email">
        </form>
    </div>

    <div class="tile scroll" style="padding:6px 8px">
        <table class="users">
            <thead><tr><th>Name</th><th>Employee ID</th><th>Role</th><th>Last signed in</th><th><span class="sr">Actions</span></th></tr></thead>
            <tbody>
                @forelse ($users as $u)
                    <tr style="{{ $u->active ? '' : 'opacity:.7' }}">
                        <td><b>{{ $u->name }}</b><div class="small">{{ $u->email }}{{ $u->department ? ' · '.$u->department : '' }}</div></td>
                        <td>{{ $u->emp_id }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.update', $u) }}" class="row" style="gap:6px">
                                @csrf @method('PATCH')
                                <label class="sr" for="role-{{ $u->id }}">Role for {{ $u->name }}</label>
                                <select id="role-{{ $u->id }}" name="role" onchange="this.form.submit()" style="height:36px;border-radius:10px;border:1.5px solid var(--line);background:#fff;padding:0 8px">
                                    @foreach (['employee' => 'Employee', 'reception' => 'Reception', 'admin' => 'Admin'] as $val => $label)
                                        <option value="{{ $val }}" @selected($u->role === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <form method="POST" action="{{ route('admin.users.update', $u) }}" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="senior" value="{{ $u->senior ? 0 : 1 }}">
                                <button class="chip {{ $u->senior ? 'on' : '' }}" style="height:32px;margin-top:6px" title="Seniors can book the senior-only rooms">{{ $u->senior ? 'Senior' : 'Not senior' }}</button>
                            </form>
                        </td>
                        <td class="small">{{ $u->last_login_at ? $u->last_login_at->format('j M, g:i A') : ($u->must_change_password ? 'Not signed in yet' : '') }}</td>
                        <td style="white-space:nowrap;text-align:right">
                            <form method="POST" action="{{ route('admin.users.reset', $u) }}" class="inline">@csrf<button class="btn sm ghost">Reset password</button></form>
                            @if ($u->active)
                                @unless ($u->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.deactivate', $u) }}" class="inline">@csrf<button class="btn sm ghost">Deactivate</button></form>
                                @endunless
                            @else
                                <form method="POST" action="{{ route('admin.users.reactivate', $u) }}" class="inline">@csrf<button class="btn sm ghost">Reactivate</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="small">Nobody matches that.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links('pagination::simple-default') }}

    <form method="POST" action="{{ route('admin.users.store') }}" class="tile stack" style="max-width:640px">
        @csrf
        <h2>Add a user</h2>
        <div class="grid2">
            <div class="field"><label class="lbl" for="name">Full name</label><input id="name" name="name" value="{{ old('name') }}" required></div>
            <div class="field"><label class="lbl" for="email">Work email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required></div>
        </div>
        <div class="grid2">
            <div class="field"><label class="lbl" for="emp_id">Employee ID <span style="font-weight:400">(optional)</span></label><input id="emp_id" name="emp_id" value="{{ old('emp_id') }}" placeholder="Made from the name if empty"></div>
            <div class="field"><label class="lbl" for="department">Department</label><input id="department" name="department" value="{{ old('department') }}"></div>
        </div>
        <div class="grid2">
            <div class="field"><label class="lbl" for="role">Role</label>
                <select id="role" name="role"><option value="employee">Employee</option><option value="reception">Reception</option><option value="admin">Admin</option></select>
            </div>
            <label class="row lbl" style="gap:10px;align-self:end;height:48px"><input type="checkbox" name="senior" value="1" style="width:20px;height:20px;accent-color:#072A4A"> Senior staff (can book the Boardroom)</label>
        </div>
        <button class="btn">Create user and send welcome email</button>
    </form>
</div>
@endsection
