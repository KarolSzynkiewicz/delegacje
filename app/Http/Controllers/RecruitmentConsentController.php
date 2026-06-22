<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class RecruitmentConsentController extends Controller
{
    public function rodo(): View
    {
        return view('recruitment.legal.rodo');
    }

    public function recruitmentProcessing(): View
    {
        return view('recruitment.legal.recruitment-processing');
    }

    public function marketing(): View
    {
        return view('recruitment.legal.marketing');
    }
}
