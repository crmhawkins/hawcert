<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_certificates' => Certificate::count(),
            'active_certificates' => Certificate::where('is_active', true)
                ->where('valid_until', '>=', now())
                ->count(),
            'expired_certificates' => Certificate::where('valid_until', '<', now())->count(),
            'total_services' => Service::where('is_active', true)->count(),
            'total_users' => User::count(),
        ];

        $recent_certificates = Certificate::with(['user', 'services'])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact('stats', 'recent_certificates'));
    }
}
