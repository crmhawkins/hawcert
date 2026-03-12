@extends('layouts.app')

@section('title', 'Resultado de Validación')

@section('content')
<div class="px-4 sm:px-0">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Resultado de Validación</h1>
        <a href="{{ route('validator.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
            Validar Otro
        </a>
    </div>

    @if($result['valid'])
        <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-6 mb-6">
            <div class="flex items-center">
                <svg class="h-6 w-6 text-green-600 dark:text-green-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2 class="text-xl font-semibold text-green-800 dark:text-green-200">Certificado Válido</h2>
            </div>
        </div>
    @else
        <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-6 mb-6">
            <div class="flex items-center">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2 class="text-xl font-semibold text-red-800 dark:text-red-200">Certificado Inválido</h2>
            </div>
            @if(!empty($result['errors']))
                <ul class="mt-3 list-disc list-inside text-red-700 dark:text-red-300">
                    @foreach($result['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    @if($result['certificate_info'])
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Información del Certificado</h3>
            <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                @if(isset($result['certificate_info']['subject']['CN']))
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Common Name (CN)</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $result['certificate_info']['subject']['CN'] }}</dd>
                </div>
                @endif

                @if(isset($result['certificate_info']['subject']['O']))
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Organización</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $result['certificate_info']['subject']['O'] }}</dd>
                </div>
                @endif

                @if(isset($result['certificate_info']['subject']['OU']))
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Unidad Organizacional</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $result['certificate_info']['subject']['OU'] }}</dd>
                </div>
                @endif

                @if(isset($result['certificate_info']['validFrom']))
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Válido desde</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ date('d/m/Y H:i:s', $result['certificate_info']['validFrom_time_t']) }}</dd>
                </div>
                @endif

                @if(isset($result['certificate_info']['validTo']))
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Válido hasta</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ date('d/m/Y H:i:s', $result['certificate_info']['validTo_time_t']) }}</dd>
                </div>
                @endif

                @if(isset($result['certificate_info']['serialNumber']))
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Número de Serie</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $result['certificate_info']['serialNumber'] }}</dd>
                </div>
                @endif
            </dl>
        </div>
    @endif

    @if($result['certificate'])
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Información del Sistema</h3>
            <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre del Certificado</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $result['certificate']->name }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Clave del Certificado</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono break-all">{{ $result['certificate']->certificate_key }}</dd>
                </div>

                @if($result['user'])
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Usuario Asociado</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $result['user']->name }} ({{ $result['user']->email }})
                    </dd>
                </div>
                @endif

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</dt>
                    <dd class="mt-1">
                        @if($result['certificate']->isValid())
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                Activo
                            </span>
                        @elseif($result['certificate']->isNotYetValid())
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                No válido aún
                            </span>
                        @elseif($result['certificate']->isExpired())
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
            </dl>
        </div>

        @if($result['services']->count() > 0)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Servicios Autorizados</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($result['services'] as $service)
                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                        {{ $service->name }}
                        @if($service->slug)
                            <span class="text-xs opacity-75">({{ $service->slug }})</span>
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
        @endif

        @if($result['permissions']->count() > 0)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Permisos</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($result['permissions'] as $permission)
                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                        {{ $permission->name }}
                        @if($permission->slug)
                            <span class="text-xs opacity-75">({{ $permission->slug }})</span>
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
        @endif
    @else
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                El certificado no se encontró en la base de datos del sistema.
            </p>
        </div>
    @endif
</div>
@endsection
