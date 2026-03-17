@extends('layouts.app')

@section('title', 'Ver Credencial')

@section('content')
<div class="px-4 sm:px-0">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Credencial: {{ $credential->website_name }}</h1>
        <div class="space-x-3">
            <a href="{{ route('credentials.edit', $credential) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                Editar
            </a>
            <a href="{{ route('credentials.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                Volver
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sitio Web</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $credential->website_name }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Patrón de URL</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono break-all">{{ $credential->website_url_pattern }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Usuario Asociado</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $credential->user->name ?? 'Ninguno' }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Certificado Asociado</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $credential->certificate->name ?? 'Ninguno' }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Usuario</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $credential->username }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Contraseña</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">••••••••</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Selector Campo Usuario (opcional)</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $credential->username_field_selector ?: 'Detección automática' }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Selector Campo Contraseña (opcional)</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $credential->password_field_selector ?: 'Detección automática' }}</dd>
            </div>

            @if($credential->submit_button_selector)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Selector Botón de Envío</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $credential->submit_button_selector }}</dd>
            </div>
            @endif

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rellenar Automáticamente</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    @if($credential->auto_fill)
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                            Sí
                        </span>
                    @else
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                            No
                        </span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Enviar Automáticamente</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    @if($credential->auto_submit)
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                            Sí
                        </span>
                    @else
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                            No
                        </span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    @if($credential->is_active)
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                            Activa
                        </span>
                    @else
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                            Inactiva
                        </span>
                    @endif
                </dd>
            </div>

            @if($credential->notes)
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Notas</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $credential->notes }}</dd>
            </div>
            @endif

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Creado</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $credential->created_at->format('d/m/Y H:i') }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Actualizado</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $credential->updated_at->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
