<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Service;
use App\Models\Permission;
use App\Services\CertificateGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    public function index()
    {
        $certificates = Certificate::with(['user', 'services', 'permissions'])
            ->latest()
            ->paginate(15);
        
        return view('certificates.index', compact('certificates'));
    }

    public function create()
    {
        $users = \App\Models\User::all();
        $services = Service::where('is_active', true)->get();
        $permissions = Permission::all();
        
        return view('certificates.create', compact('users', 'services', 'permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'description' => 'nullable|string',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'never_expires' => 'boolean',
            'organization' => 'nullable|string|max:255',
            'organizational_unit' => 'nullable|string|max:255',
            'services' => 'nullable|array',
            'services.*' => 'exists:services,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $user = \App\Models\User::find($validated['user_id']);
        $validFrom = $validated['valid_from'] ? \Carbon\Carbon::parse($validated['valid_from']) : now();
        $validUntil = ($validated['never_expires'] ?? false) ? null : ($validated['valid_until'] ? \Carbon\Carbon::parse($validated['valid_until']) : null);

        // Generar certificado X.509 real
        $certGenerator = new CertificateGeneratorService();
        $certData = $certGenerator->generateX509Certificate([
            'name' => $validated['name'],
            'common_name' => $validated['name'],
            'email' => $validated['email'],
            'organization' => $validated['organization'] ?? null,
            'organizational_unit' => $validated['organizational_unit'] ?? null,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'never_expires' => $validated['never_expires'] ?? false,
        ]);

        $certificate = Certificate::create([
            'user_id' => $validated['user_id'],
            'certificate_key' => 'cert_' . Str::random(32),
            'x509_certificate' => $certData['x509_certificate'],
            'private_key' => $certData['private_key'],
            'common_name' => $certData['common_name'],
            'organization' => $certData['organization'],
            'organizational_unit' => $certData['organizational_unit'],
            'email' => $validated['email'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'never_expires' => $validated['never_expires'] ?? false,
            'is_active' => true,
        ]);

        if (isset($validated['services'])) {
            $certificate->services()->attach($validated['services']);
        }

        if (isset($validated['permissions'])) {
            $certificate->permissions()->attach($validated['permissions']);
        }

        return redirect()->route('certificates.index')
            ->with('success', 'Certificado X.509 creado exitosamente.');
    }

    public function show(Certificate $certificate)
    {
        $certificate->load(['user', 'services', 'permissions']);
        return view('certificates.show', compact('certificate'));
    }

    public function edit(Certificate $certificate)
    {
        $users = \App\Models\User::all();
        $services = Service::where('is_active', true)->get();
        $permissions = Permission::all();
        
        return view('certificates.edit', compact('certificate', 'users', 'services', 'permissions'));
    }

    public function update(Request $request, Certificate $certificate)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'description' => 'nullable|string',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'never_expires' => 'boolean',
            'is_active' => 'boolean',
            'services' => 'nullable|array',
            'services.*' => 'exists:services,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $certificate->update([
            'user_id' => $validated['user_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'description' => $validated['description'] ?? null,
            'valid_from' => $validated['valid_from'] ?? $certificate->valid_from,
            'valid_until' => ($validated['never_expires'] ?? false) ? null : ($validated['valid_until'] ?? $certificate->valid_until),
            'never_expires' => $validated['never_expires'] ?? false,
            'is_active' => $validated['is_active'] ?? $certificate->is_active,
        ]);

        if (isset($validated['services'])) {
            $certificate->services()->sync($validated['services']);
        } else {
            $certificate->services()->detach();
        }

        if (isset($validated['permissions'])) {
            $certificate->permissions()->sync($validated['permissions']);
        } else {
            $certificate->permissions()->detach();
        }

        return redirect()->route('certificates.index')
            ->with('success', 'Certificado actualizado exitosamente.');
    }

    public function destroy(Certificate $certificate)
    {
        $certificate->delete();
        return redirect()->route('certificates.index')
            ->with('success', 'Certificado eliminado exitosamente.');
    }

    public function download(Request $request, Certificate $certificate)
    {
        $format = $request->get('format', $certificate->x509_certificate ? 'pem' : 'json'); // pem, p12, or json

        if (!$certificate->x509_certificate && ($format === 'pem' || $format === 'p12')) {
            return redirect()->back()->with('error', 'Este certificado no tiene un certificado X.509 asociado. Solo está disponible en formato JSON.');
        }

        if ($format === 'json') {
            // Descarga en formato JSON (legacy)
            $certificate->load(['user', 'services', 'permissions']);
            
            $data = [
                'certificate_key' => $certificate->certificate_key,
                'name' => $certificate->name,
                'description' => $certificate->description,
                'user' => [
                    'id' => $certificate->user->id,
                    'name' => $certificate->user->name,
                    'email' => $certificate->user->email,
                ],
                'valid_from' => $certificate->valid_from->toIso8601String(),
                'valid_until' => $certificate->never_expires ? null : ($certificate->valid_until ? $certificate->valid_until->toIso8601String() : null),
                'never_expires' => $certificate->never_expires,
                'is_active' => $certificate->is_active,
                'services' => $certificate->services->map(function ($service) {
                    return [
                        'name' => $service->name,
                        'slug' => $service->slug,
                        'endpoint' => $service->endpoint,
                    ];
                })->toArray(),
                'permissions' => $certificate->permissions->map(function ($permission) {
                    return [
                        'name' => $permission->name,
                        'slug' => $permission->slug,
                    ];
                })->toArray(),
                'created_at' => $certificate->created_at->toIso8601String(),
                'updated_at' => $certificate->updated_at->toIso8601String(),
            ];

            $filename = 'certificate_' . $certificate->certificate_key . '_' . now()->format('Y-m-d') . '.json';
            
            return response()->json($data, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        if ($format === 'p12' || $format === 'pfx') {
            // Descarga en formato PKCS#12 (.p12/.pfx)
            try {
                $privateKeyPem = Crypt::decryptString($certificate->private_key);
                $certGenerator = new CertificateGeneratorService();
                $p12Content = $certGenerator->generateP12($certificate->x509_certificate, $privateKeyPem);
                
                $filename = $certificate->common_name . '_' . now()->format('Y-m-d') . '.p12';
                
                return response($p12Content, 200, [
                    'Content-Type' => 'application/x-pkcs12',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Error al generar archivo P12: ' . $e->getMessage());
            }
        }

        // Descarga en formato PEM (por defecto)
        $privateKeyPem = Crypt::decryptString($certificate->private_key);
        $certContent = $certificate->x509_certificate . "\n" . $privateKeyPem;
        
        $filename = $certificate->common_name . '_' . now()->format('Y-m-d') . '.pem';
        
        return response($certContent, 200, [
            'Content-Type' => 'application/x-pem-file',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
