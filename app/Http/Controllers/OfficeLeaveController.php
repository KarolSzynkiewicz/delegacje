<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class OfficeLeaveController extends Controller
{
    public function index(): View
    {
        return view('office-leave.index');
    }
}
