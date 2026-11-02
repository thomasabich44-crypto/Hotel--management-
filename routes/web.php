<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HotelController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [HotelController::class, 'dashboard'])->name('dashboard');
    Route::get('/guests', [HotelController::class, 'guests'])->name('guests.index');
    Route::post('/guests', [HotelController::class, 'storeGuest'])->name('guests.store');
    Route::put('/guests/{guest}', [HotelController::class, 'updateGuest'])->name('guests.update');
    Route::delete('/guests/{guest}', [HotelController::class, 'destroyGuest'])->name('guests.destroy');
    Route::get('/rooms', [HotelController::class, 'rooms'])->name('rooms.index');
    Route::post('/rooms', [HotelController::class, 'storeRoom'])->middleware('can:manage-inventory')->name('rooms.store');
    Route::put('/rooms/{room}', [HotelController::class, 'updateRoom'])->middleware('can:manage-inventory')->name('rooms.update');
    Route::delete('/rooms/{room}', [HotelController::class, 'destroyRoom'])->middleware('can:manage-inventory')->name('rooms.destroy');
    Route::post('/room-types', [HotelController::class, 'storeRoomType'])->middleware('can:manage-inventory')->name('room-types.store');
    Route::put('/room-types/{roomType}', [HotelController::class, 'updateRoomType'])->middleware('can:manage-inventory')->name('room-types.update');
    Route::delete('/room-types/{roomType}', [HotelController::class, 'destroyRoomType'])->middleware('can:manage-inventory')->name('room-types.destroy');
    Route::get('/reservations', [HotelController::class, 'reservations'])->name('reservations.index');
    Route::post('/reservations', [HotelController::class, 'storeReservation'])->name('reservations.store');
    Route::get('/reservations/{reservation}/edit', [HotelController::class, 'editReservation'])->name('reservations.edit');
    Route::put('/reservations/{reservation}', [HotelController::class, 'updateReservation'])->name('reservations.update');
    Route::delete('/reservations/{reservation}', [HotelController::class, 'destroyReservation'])->name('reservations.destroy');
    Route::patch('/reservations/{reservation}/status', [HotelController::class, 'updateReservationStatus'])->name('reservations.status');
    Route::post('/reservations/{reservation}/payments', [HotelController::class, 'storePayment'])->middleware('can:record-payments')->name('payments.store');
    Route::get('/reports', [HotelController::class, 'reports'])->name('reports.index');
});
