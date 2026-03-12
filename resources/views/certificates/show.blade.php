@extends('layouts.app')

@section('title', 'Ver Certificado')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Certificado: {{ $certificate->name }}</h1>
        <div class="space-x-3">
            @if($certificate->x509_certificate)
            <div class="inline-flex rounded-md shadow-sm" role="group">
                <a href="{{ route('certificates.download', ['certificate' => $certificate, 'format' => 'pem']) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-l-md text-sm font-medium text-white bg-green-600 hover:bg-green-700" title="Descargar certificado en formato PEM">
                    PEM
                </a>
                <a href="{{ route('certificates.download', ['certificate' => $certificate, 'format' => 'p12']) }}" class="inline-flex items-center px-4 py-2 border border-transparent border-l-0 rounded-r-md text-sm font-medium text-white bg-green-600 hover:bg-green-700" title="Descargar certificado en formato PKCS#12 (.p12)">
                    P12
                </a>
            </div>
            @else
            <a href="{{ route('certificates.download', ['certificate' => $certificate, 'format' => 'json']) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                Descargar JSON
            </a>
            @endif
                <a href="{{ route('certificates.edit', $certificate) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    Editar
                </a>
                <a href="{{ route('certificates.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    Volver
                </a>
            </div>
        </div>

    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Usuario</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $certificate->user->name }} ({{ $certificate->user->email }})</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Clave del Certificado</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono break-all">{{ $certificate->certificate_key }}</dd>
            </div>

            @if($certificate->x509_certificate)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo</dt>
                <dd class="mt-1">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                        Certificado X.509 Real
                    </span>
                </dd>
            </div>

            @if($certificate->common_name)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Common Name (CN)</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $certificate->common_name }}</dd>
            </div>
            @endif

            @if($certificate->organization)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Organización</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $certificate->organization }}</dd>
            </div>
            @endif

            @if($certificate->organizational_unit)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Unidad Organizacional</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $certificate->organizational_unit }}</dd>
            </div>
            @endif
            @endif

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Válido desde</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $certificate->valid_from->format('d/m/Y H:i') }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Válido hasta</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    @if($certificate->never_expires)
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                            Nunca expira
                        </span>
                    @elseif($certificate->valid_until)
                        {{ $certificate->valid_until->format('d/m/Y H:i') }}
                    @else
                        -
                    @endif
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</dt>
                <dd class="mt-1">
                    @if($certificate->isValid())
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                            Activo
                        </span>
                    @elseif($certificate->isNotYetValid())
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                            No válido aún
                        </span>
                    @elseif($certificate->isExpired())
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                            Expirado
                        </span>
                    @else
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                            Inactivo
                        </span>
                    @endif
                </dd>
            </div>

            @if($certificate->description)
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Descripción</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $certificate->description }}</dd>
            </div>
            @endif

            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Servicios</dt>
                <dd class="mt-1">
                    @if($certificate->services->count() > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($certificate->services as $service)
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                    {{ $service->name }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <span class="text-sm text-gray-500 dark:text-gray-400">No hay servicios asignados</span>
                    @endif
                </dd>
            </div>

            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Permisos</dt>
                <dd class="mt-1">
                    @if($certificate->permissions->count() > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($certificate->permissions as $permission)
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                                    {{ $permission->name }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <span class="text-sm text-gray-500 dark:text-gray-400">No hay permisos asignados</span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>
</div>
@endsection
