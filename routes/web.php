<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StaffSessionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.login')->name('login');
Route::post('/login', [StaffSessionController::class, 'login'])->name('login.store');
Route::post('/logout', [StaffSessionController::class, 'logout'])->name('logout');
Route::middleware('staff')->group(function () {
	Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
	Route::post('/reservations/{reservation}/no-show', [ReservationController::class, 'markNoShow'])->name('reservations.no-show');
	Route::post('/reservations/{reservation}/payments', [ReservationController::class, 'addPayment'])->name('reservations.payments.store');
	Route::post('/reservations/{reservation}/check-out', [ReservationController::class, 'checkOut'])->name('reservations.check-out');
	Route::put('/reservations/policy', [ReservationController::class, 'updatePolicy'])->name('reservations.policy.update');
	Route::resource('reservations', ReservationController::class)->only(['index', 'store', 'update']);
	Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
	Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
	Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
	Route::get('/reports/print', [ReportController::class, 'print'])->name('reports.print');
});
