<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('plan.index');
    }
}
