<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NoticeMail;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;
use App\Support\Clock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        $filter = in_array($request->query('show'), ['all', 'senior', 'reception', 'admin', 'off'], true) ? $request->query('show') : 'all';
        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->when($filter === 'off', fn ($q) => $q->where('active', false), fn ($q) => $q->where('active', true))
            ->when($filter === 'senior', fn ($q) => $q->where('senior', true))
            ->when(in_array($filter, ['reception', 'admin'], true), fn ($q) => $q->where('role', $filter))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('emp_id', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name')->paginate(50)->withQueryString();

        $counts = [
            'all' => User::where('active', true)->count(),
            'senior' => User::where('active', true)->where('senior', true)->count(),
            'reception' => User::where('active', true)->where('role', 'reception')->count(),
            'admin' => User::where('active', true)->where('role', 'admin')->count(),
            'off' => User::where('active', false)->count(),
        ];

        return view('admin.users', compact('users', 'filter', 'counts', 'search'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'emp_id' => ['nullable', 'string', 'max:100', 'unique:users,emp_id'],
            'department' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::in(['employee', 'reception', 'admin'])],
            'senior' => ['nullable', 'boolean'],
        ], ['email.unique' => 'Someone already uses that email.', 'emp_id.unique' => 'That employee ID is taken.']);

        $empId = $data['emp_id'] ?: Str::of($data['name'])->lower()->replaceMatches('/[^a-z ]/', '')->squish()->replace(' ', '.')->value();
        if (User::where('emp_id', $empId)->exists()) {
            $empId .= '.'.random_int(10, 99);
        }
        $temp = $this->tempPassword();

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'emp_id' => $empId,
            'department' => $data['department'] ?? null,
            'role' => $data['role'],
            'senior' => $request->boolean('senior'),
            'active' => true,
            'must_change_password' => true,
            'password' => $temp,
        ]);

        Mail::to($user->email)->send(new NoticeMail('Welcome to Eureka', 'welcome', ['user' => $user, 'temp' => $temp]));

        return back()->with('ok', $user->name.' added. Their welcome email has the ID '.$user->emp_id.' and a temporary password.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['sometimes', Rule::in(['employee', 'reception', 'admin'])],
            'senior' => ['sometimes', 'boolean'],
        ]);
        if ($user->is($request->user()) && isset($data['role']) && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'You cannot remove your own admin access.']);
        }
        $user->update($data);

        return back()->with('ok', $user->name.' updated.');
    }

    public function resetPassword(User $user)
    {
        $temp = $this->tempPassword();
        $user->forceFill(['password' => $temp, 'must_change_password' => true])->save();
        Mail::to($user->email)->send(new NoticeMail('Your Eureka password was reset', 'reset', ['user' => $user, 'temp' => $temp]));

        return back()->with('ok', 'A temporary password was emailed to '.$user->email.'.');
    }

    public function deactivate(Request $request, User $user, BookingService $service)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot deactivate your own account.']);
        }
        $user->update(['active' => false]);
        $future = Booking::active()->where('organizer_id', $user->id)->whereDate('date', '>=', Clock::today())->get();
        foreach ($future as $b) {
            $service->cancel($b, $request->user());
        }

        return back()->with('ok', $user->name.' deactivated'.($future->count() ? ' and '.$future->count().' future bookings cancelled.' : '.'));
    }

    public function reactivate(User $user)
    {
        $user->update(['active' => true]);

        return back()->with('ok', $user->name.' can sign in again.');
    }

    private function tempPassword(): string
    {
        return 'Eu'.Str::lower(Str::random(6)).random_int(10, 99);
    }
}
