<?php

use App\Http\Controllers\Admin\RoomAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CheckInLinkController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');
});

// Links in the check-in reminder email. Signed, so they work without signing in.
Route::middleware('signed')->group(function () {
    Route::get('/b/{booking}/keep', [CheckInLinkController::class, 'keep'])->name('checkin.keep');
    Route::get('/b/{booking}/release', [CheckInLinkController::class, 'release'])->name('checkin.release');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('password.changed')->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');

        Route::get('/book', [BookingController::class, 'create'])->name('bookings.create');
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::get('/bookings/{booking}/done', [BookingController::class, 'confirmed'])->name('bookings.confirmed');
        Route::get('/my-bookings', [BookingController::class, 'mine'])->name('bookings.mine');
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/bookings/{booking}/extend', [BookingController::class, 'extend'])->name('bookings.extend');
        Route::post('/bookings/{booking}/check-in', [BookingController::class, 'checkIn'])->name('bookings.checkin');
        Route::get('/bookings/{booking}/calendar.ics', [BookingController::class, 'ics'])->name('bookings.ics');

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');

        Route::middleware('role:reception,admin')->group(function () {
            Route::get('/today', [ReceptionController::class, 'today'])->name('reception.today');
        });

        Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/users', [UserAdminController::class, 'index'])->name('users.index');
            Route::post('/users', [UserAdminController::class, 'store'])->name('users.store');
            Route::patch('/users/{user}', [UserAdminController::class, 'update'])->name('users.update');
            Route::post('/users/{user}/reset-password', [UserAdminController::class, 'resetPassword'])->name('users.reset');
            Route::post('/users/{user}/deactivate', [UserAdminController::class, 'deactivate'])->name('users.deactivate');
            Route::post('/users/{user}/reactivate', [UserAdminController::class, 'reactivate'])->name('users.reactivate');

            Route::get('/rooms', [RoomAdminController::class, 'index'])->name('rooms.index');
            Route::post('/rooms', [RoomAdminController::class, 'store'])->name('rooms.store');
            Route::patch('/rooms/{room}', [RoomAdminController::class, 'update'])->name('rooms.update');
        });
    });
});
