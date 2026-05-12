<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;

        return view('dashboard', compact('company'));
    }
}