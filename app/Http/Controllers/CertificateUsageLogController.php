<?php

namespace App\Http\Controllers;

use App\Models\CertificateUsageLog;
use Illuminate\Http\Request;

class CertificateUsageLogController extends Controller
{
    public function index(Request $request)
    {
        $query = CertificateUsageLog::with('certificate.user')
            ->latest();

        if ($request->filled('certificate_id')) {
            $query->where('certificate_id', $request->certificate_id);
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        $logs = $query->paginate(25);

        return view('logs.index', compact('logs'));
    }
}
