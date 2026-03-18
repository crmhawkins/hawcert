<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Credential;
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
        $credentials = Credential::where('is_active', true)->orderBy('website_name')->get();

        return view('certificates.create', compact('users', 'services', 'permissions', 'credentials'));
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
            'service_auth_username' => 'nullable|array',
            'service_auth_username.*' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'credential_ids' => 'nullable|array',
            'credential_ids.*' => 'exists:credentials,id',
            'is_becario' => 'boolean',
        ]);

        $isBecario = (bool) ($validated['is_becario'] ?? false);
        if ($isBecario) {
            $request->validate([
                'valid_until' => 'required|date|after:valid_from',
            ], [
                'valid_until.required' => 'Los certificados de tipo Becario deben tener fecha de expiración obligatoria.',
            ]);
        }

        $user = \App\Models\User::find($validated['user_id']);
        $validFrom = $validated['valid_from'] ? \Carbon\Carbon::parse($validated['valid_from']) : now();
        $neverExpires = $isBecario ? false : ($validated['never_expires'] ?? false);
        $validUntil = $neverExpires ? null : ($validated['valid_until'] ? \Carbon\Carbon::parse($validated['valid_until']) : null);

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
            'never_expires' => $neverExpires,
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
            'never_expires' => $neverExpires,
            'is_active' => true,
            'is_becario' => $isBecario,
        ]);

        if (isset($validated['services'])) {
            $syncData = [];
            foreach ($validated['services'] as $serviceId) {
                $authUsername = isset($validated['service_auth_username'][$serviceId])
                    ? trim((string) $validated['service_auth_username'][$serviceId])
                    : '';
                $syncData[$serviceId] = ['auth_username' => $authUsername !== '' ? $authUsername : null];
            }
            $certificate->services()->sync($syncData);
        }

        if (isset($validated['permissions'])) {
            $certificate->permissions()->attach($validated['permissions']);
        }

        if (isset($validated['credential_ids'])) {
            $certificate->credentials()->sync($validated['credential_ids']);
        } else {
            $certificate->credentials()->sync([]);
        }

        return redirect()->route('certificates.index')
            ->with('success', 'Certificado X.509 creado exitosamente.');
    }

    public function show(Certificate $certificate)
    {
        $certificate->load(['user', 'services', 'permissions', 'credentials']);
        return view('certificates.show', compact('certificate'));
    }

    public function edit(Certificate $certificate)
    {
        $certificate->load('credentials');
        $users = \App\Models\User::all();
        $services = Service::where('is_active', true)->get();
        $permissions = Permission::all();
        $credentials = Credential::where('is_active', true)->orderBy('website_name')->get();

        return view('certificates.edit', compact('certificate', 'users', 'services', 'permissions', 'credentials'));
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
            'organization' => 'nullable|string|max:255',
            'organizational_unit' => 'nullable|string|max:255',
            'services' => 'nullable|array',
            'services.*' => 'exists:services,id',
            'service_auth_username' => 'nullable|array',
            'service_auth_username.*' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'credential_ids' => 'nullable|array',
            'credential_ids.*' => 'exists:credentials,id',
            'is_becario' => 'boolean',
        ]);

        $isBecario = (bool) ($validated['is_becario'] ?? false);
        if ($isBecario) {
            $request->validate([
                'valid_until' => 'required|date|after:valid_from',
            ], [
                'valid_until.required' => 'Los certificados de tipo Becario deben tener fecha de expiración obligatoria.',
            ]);
        }

        $validFrom = $validated['valid_from'] ?? $certificate->valid_from;
        $neverExpires = $isBecario ? false : ($validated['never_expires'] ?? false);
        $validUntil = $neverExpires ? null : ($validated['valid_until'] ?? $certificate->valid_until);
        if ($validUntil && !$validUntil instanceof \Carbon\Carbon) {
            $validUntil = \Carbon\Carbon::parse($validUntil);
        }

        // Verificar si hay cambios que requieren regenerar el certificado X.509
        $needsRegeneration = $certificate->x509_certificate && (
            $certificate->name !== $validated['name'] ||
            $certificate->email !== $validated['email'] ||
            ($certificate->organization ?? '') !== ($validated['organization'] ?? '') ||
            ($certificate->organizational_unit ?? '') !== ($validated['organizational_unit'] ?? '') ||
            $certificate->valid_from->format('Y-m-d H:i:s') !== ($validFrom instanceof \Carbon\Carbon ? $validFrom->format('Y-m-d H:i:s') : $validFrom) ||
            ($certificate->valid_until ? $certificate->valid_until->format('Y-m-d H:i:s') : null) !== ($validUntil ? ($validUntil instanceof \Carbon\Carbon ? $validUntil->format('Y-m-d H:i:s') : $validUntil) : null) ||
            $certificate->never_expires !== $neverExpires
        );

        if ($needsRegeneration) {
            $certGenerator = new CertificateGeneratorService();
            $certData = $certGenerator->generateX509Certificate([
                'name' => $validated['name'],
                'common_name' => $validated['name'],
                'email' => $validated['email'],
                'organization' => $validated['organization'] ?? null,
                'organizational_unit' => $validated['organizational_unit'] ?? null,
                'valid_from' => $validFrom instanceof \Carbon\Carbon ? $validFrom : \Carbon\Carbon::parse($validFrom),
                'valid_until' => $validUntil ? ($validUntil instanceof \Carbon\Carbon ? $validUntil : \Carbon\Carbon::parse($validUntil)) : null,
                'never_expires' => $neverExpires,
            ]);

            $certificate->update([
                'user_id' => $validated['user_id'],
                'x509_certificate' => $certData['x509_certificate'],
                'private_key' => $certData['private_key'],
                'common_name' => $certData['common_name'],
                'organization' => $certData['organization'],
                'organizational_unit' => $certData['organizational_unit'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'description' => $validated['description'] ?? null,
                'valid_from' => $validFrom instanceof \Carbon\Carbon ? $validFrom : \Carbon\Carbon::parse($validFrom),
                'valid_until' => $validUntil,
                'never_expires' => $neverExpires,
                'is_active' => $validated['is_active'] ?? $certificate->is_active,
                'is_becario' => $isBecario,
            ]);
        } else {
            $certificate->update([
                'user_id' => $validated['user_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'description' => $validated['description'] ?? null,
                'valid_from' => $validFrom instanceof \Carbon\Carbon ? $validFrom : \Carbon\Carbon::parse($validFrom),
                'valid_until' => $validUntil,
                'never_expires' => $neverExpires,
                'is_active' => $validated['is_active'] ?? $certificate->is_active,
                'is_becario' => $isBecario,
            ]);
        }

        if (isset($validated['services'])) {
            $syncData = [];
            foreach ($validated['services'] as $serviceId) {
                $authUsername = isset($validated['service_auth_username'][$serviceId])
                    ? trim((string) $validated['service_auth_username'][$serviceId])
                    : '';
                $syncData[$serviceId] = ['auth_username' => $authUsername !== '' ? $authUsername : null];
            }
            $certificate->services()->sync($syncData);
        } else {
            $certificate->services()->detach();
        }

        if (isset($validated['permissions'])) {
            $certificate->permissions()->sync($validated['permissions']);
        } else {
            $certificate->permissions()->detach();
        }

        if (array_key_exists('credential_ids', $validated)) {
            $certificate->credentials()->sync($validated['credential_ids'] ?? []);
        }

        return redirect()->route('certificates.index')
            ->with('success', 'Certificado actualizado exitosamente' . ($needsRegeneration ? ' (certificado X.509 regenerado)' : '') . '.');
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
