<?php

namespace App\Http\Controllers;

use App\Support\RoomCatalog;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(): View
    {
        return view('rooms.index', ['rooms' => RoomCatalog::withAvailability()]);
    }
}