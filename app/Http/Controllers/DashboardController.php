<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\NoShowService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        app(NoShowService::class)->markExpired();
        return view('dashboard', [
            'reservations' => Reservation::latest('check_in')->take(5)->get(),
            'currentTime' => now(),
            'stats' => [
                'arriving' => Reservation::whereIn('status', ['Pending', 'Confirmed'])->count(),
                'checkIns' => Reservation::whereDate('check_in', today())->whereIn('status', ['Pending', 'Confirmed'])->count(),
                'checkOuts' => Reservation::where('status', 'Checked-Out')->count(),
            ],
        ]);
    }
}
