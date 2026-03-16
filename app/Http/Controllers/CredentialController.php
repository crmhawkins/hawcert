<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Http\Request;

class CredentialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $credentials = Credential::with(['user', 'certificate'])
            ->latest()
            ->paginate(15);
        
        return view('credentials.index', compact('credentials'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::all();
        $certificates = Certificate::where('is_active', true)->get();
        
        return view('credentials.create', compact('users', 'certificates'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'certificate_id' => 'nullable|exists:certificates,id',
            'website_name' => 'required|string|max:255',
            'website_url_pattern' => 'required|string|max:500',
            'username_field_selector' => 'required|string|max:255',
            'password_field_selector' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'submit_button_selector' => 'nullable|string|max:255',
            'auto_fill' => 'boolean',
            'auto_submit' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // Validar que al menos user_id o certificate_id esté presente
        if (!$validated['user_id'] && !$validated['certificate_id']) {
            return back()->withErrors(['user_id' => 'Debe seleccionar un usuario o un certificado.'])->withInput();
        }

        $credential = Credential::create([
            'user_id' => $validated['user_id'],
            'certificate_id' => $validated['certificate_id'],
            'website_name' => $validated['website_name'],
            'website_url_pattern' => $validated['website_url_pattern'],
            'username_field_selector' => $validated['username_field_selector'],
            'password_field_selector' => $validated['password_field_selector'],
            'username' => $validated['username'], // Se cifrará automáticamente
            'password' => $validated['password'], // Se cifrará automáticamente
            'submit_button_selector' => $validated['submit_button_selector'] ?? null,
            'auto_fill' => $validated['auto_fill'] ?? true,
            'auto_submit' => $validated['auto_submit'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('credentials.index')
            ->with('success', 'Credencial creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Credential $credential)
    {
        $credential->load(['user', 'certificate']);
        return view('credentials.show', compact('credential'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Credential $credential)
    {
        $users = User::all();
        $certificates = Certificate::where('is_active', true)->get();
        
        return view('credentials.edit', compact('credential', 'users', 'certificates'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Credential $credential)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'certificate_id' => 'nullable|exists:certificates,id',
            'website_name' => 'required|string|max:255',
            'website_url_pattern' => 'required|string|max:500',
            'username_field_selector' => 'required|string|max:255',
            'password_field_selector' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'submit_button_selector' => 'nullable|string|max:255',
            'auto_fill' => 'boolean',
            'auto_submit' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // Validar que al menos user_id o certificate_id esté presente
        if (!$validated['user_id'] && !$validated['certificate_id']) {
            return back()->withErrors(['user_id' => 'Debe seleccionar un usuario o un certificado.'])->withInput();
        }

        $credential->update([
            'user_id' => $validated['user_id'],
            'certificate_id' => $validated['certificate_id'],
            'website_name' => $validated['website_name'],
            'website_url_pattern' => $validated['website_url_pattern'],
            'username_field_selector' => $validated['username_field_selector'],
            'password_field_selector' => $validated['password_field_selector'],
            'username' => $validated['username'], // Se cifrará automáticamente
            'password' => $validated['password'], // Se cifrará automáticamente
            'submit_button_selector' => $validated['submit_button_selector'] ?? null,
            'auto_fill' => $validated['auto_fill'] ?? true,
            'auto_submit' => $validated['auto_submit'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('credentials.index')
            ->with('success', 'Credencial actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Credential $credential)
    {
        $credential->delete();

        return redirect()->route('credentials.index')
            ->with('success', 'Credencial eliminada exitosamente.');
    }
}
