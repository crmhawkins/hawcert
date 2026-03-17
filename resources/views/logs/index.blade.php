@extends('layouts.app')

@section('title', 'Logs de uso de certificados')

@section('content')
<div class="px-4 sm:px-0">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Logs de uso de certificados</h1>

    <form method="GET" action="{{ route('logs.index') }}" class="mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label for="certificate_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Certificado</label>
            <select name="certificate_id" id="certificate_id" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Todos</option>
                @foreach(\App\Models\Certificate::orderBy('name')->get() as $cert)
                    <option value="{{ $cert->id }}" {{ request('certificate_id') == $cert->id ? 'selected' : '' }}>{{ $cert->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="event_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo</label>
            <select name="event_type" id="event_type" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Todos</option>
                <option value="validation" {{ request('event_type') === 'validation' ? 'selected' : '' }}>Validación de certificado</option>
                <option value="key_validation" {{ request('event_type') === 'key_validation' ? 'selected' : '' }}>Validación de key</option>
                <option value="credentials" {{ request('event_type') === 'credentials' ? 'selected' : '' }}>Obtención de credenciales</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium">Filtrar</button>
    </form>

    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Certificado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sitio / Servicio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($logs as $log)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        {{ $log->certificate->name ?? '-' }}
                        @if($log->certificate && ($log->certificate->user ?? null))
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $log->certificate->user->name }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        @if($log->event_type === 'validation')
                            <span class="px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">Validación</span>
                        @elseif($log->event_type === 'key_validation')
                            <span class="px-2 py-0.5 text-xs rounded bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">Key</span>
                        @elseif($log->event_type === 'credentials')
                            <span class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">Credenciales</span>
                        @else
                            {{ $log->event_type }}
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono max-w-xs truncate" title="{{ $log->site }}">
                        {{ $log->site ?: '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $log->ip_address ?: '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No hay registros de uso. Los usos se registran al validar certificados, usar keys o obtener credenciales desde la extensión.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->withQueryString()->links() }}
    </div>
</div>
@endsection
