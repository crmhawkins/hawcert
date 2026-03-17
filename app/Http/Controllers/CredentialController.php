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
            'auth_type' => 'required|in:form,certificate_only',
            'username_field_selector' => 'nullable|string|max:255',
            'password_field_selector' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'submit_button_selector' => 'nullable|string|max:255',
            'auto_fill' => 'boolean',
            'auto_submit' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if (($validated['auth_type'] ?? 'form') === 'form') {
            $request->validate([
                'username' => 'required|string|max:255',
                'password' => 'required|string|max:255',
            ]);
        }

        $credential = Credential::create([
            'user_id' => $validated['user_id'],
            'certificate_id' => $validated['certificate_id'],
            'website_name' => $validated['website_name'],
            'website_url_pattern' => $validated['website_url_pattern'],
            'auth_type' => $validated['auth_type'] ?? Credential::AUTH_TYPE_FORM,
            'username_field_selector' => !empty(trim($validated['username_field_selector'] ?? '')) ? trim($validated['username_field_selector']) : null,
            'password_field_selector' => !empty(trim($validated['password_field_selector'] ?? '')) ? trim($validated['password_field_selector']) : null,
            'username' => $validated['username'] ?? '',
            'password' => $validated['password'] ?? '',
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
            'auth_type' => 'required|in:form,certificate_only',
            'username_field_selector' => 'nullable|string|max:255',
            'password_field_selector' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'submit_button_selector' => 'nullable|string|max:255',
            'auto_fill' => 'boolean',
            'auto_submit' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if (($validated['auth_type'] ?? 'form') === 'form') {
            $request->validate([
                'username' => 'required|string|max:255',
                'password' => 'required|string|max:255',
            ]);
        }

        $update = [
            'user_id' => $validated['user_id'],
            'certificate_id' => $validated['certificate_id'],
            'website_name' => $validated['website_name'],
            'website_url_pattern' => $validated['website_url_pattern'],
            'auth_type' => $validated['auth_type'] ?? Credential::AUTH_TYPE_FORM,
            'username_field_selector' => !empty(trim($validated['username_field_selector'] ?? '')) ? trim($validated['username_field_selector']) : null,
            'password_field_selector' => !empty(trim($validated['password_field_selector'] ?? '')) ? trim($validated['password_field_selector']) : null,
            'submit_button_selector' => $validated['submit_button_selector'] ?? null,
            'auto_fill' => $validated['auto_fill'] ?? true,
            'auto_submit' => $validated['auto_submit'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ];
        $update['username'] = $validated['username'] ?? '';
        if (($validated['auth_type'] ?? 'form') === 'form') {
            $update['password'] = $validated['password'] ?? '';
        } else {
            if (!empty(trim($validated['password'] ?? ''))) {
                $update['password'] = $validated['password'];
            }
        }
        $credential->update($update);

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
