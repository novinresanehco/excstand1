<?php

namespace App\Http\Controllers; // فقط یک بک اسلش، بدون آکولاد اضافی

// use Illuminate\Http\Request; // نیازی نیست
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{ // آکولاد باز کلاس

    /**
     * Display the user's dashboard with their conversion jobs.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $user = Auth::user();

        $jobs = $user->conversionJobs()
                     ->latest()
                     ->paginate(10);

        return view('dashboard', compact('jobs'));
    }

} // آکولاد بسته کلاس