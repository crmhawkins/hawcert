@extends('layouts.app')

@section('title', 'Crear Certificado')

@section('content')
<div class="px-4 sm:px-0">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Crear Certificado</h1>

    <form action="{{ route('certificates.store') }}" method="POST" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        @csrf

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Usuario</label>
                <select name="user_id" id="user_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Seleccionar usuario</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                <input type="text" name="name" id="name" required value="{{ old('name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" name="email" id="email" required value="{{ old('email') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Email asociado al certificado (puede ser diferente al del usuario)</p>
            </div>

            <div>
                <label for="organization" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Organización</label>
                <input type="text" name="organization" id="organization" value="{{ old('organization', 'HawCert') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <label for="organizational_unit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unidad Organizacional</label>
                <input type="text" name="organizational_unit" id="organizational_unit" value="{{ old('organizational_unit', 'IT Department') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div class="sm:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('description') }}</textarea>
            </div>

            <div>
                <label for="valid_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Válido desde</label>
                <input type="datetime-local" name="valid_from" id="valid_from" value="{{ old('valid_from', now()->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Si no se especifica, se usará la fecha/hora actual</p>
            </div>

            <div>
                <label for="valid_until" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Válido hasta</label>
                <input type="datetime-local" name="valid_until" id="valid_until" value="{{ old('valid_until') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div class="sm:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" name="is_becario" id="is_becario" value="1" {{ old('is_becario') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Becario</span>
                </label>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Los certificados Becario se muestran en otro color y deben tener siempre fecha de expiración. Se puede desactivar editando el certificado.</p>
            </div>

            <div class="sm:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" name="never_expires" id="never_expires" value="1" {{ old('never_expires') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Este certificado nunca expira</span>
                </label>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Servicios</label>
                <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">Por defecto se usará el email del certificado para autenticarse en cada servicio. Opcionalmente puede indicar un usuario concreto (ej: user1921) que se enviará en lugar del email.</p>
                <div class="space-y-3">
                    @foreach($services as $service)
                        <div class="flex flex-wrap items-center gap-2 p-2 rounded border border-gray-200 dark:border-gray-600">
                            <label class="flex items-center shrink-0">
                                <input type="checkbox" name="services[]" value="{{ $service->id }}" {{ in_array($service->id, old('services', [])) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $service->name }}</span>
                            </label>
                            <input type="text" name="service_auth_username[{{ $service->id }}]" value="{{ old('service_auth_username.'.$service->id) }}" placeholder="Usuario opcional (si no, se usa el email)" class="flex-1 min-w-[180px] text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Permisos</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($permissions as $permission)
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $permission->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Acceso a credenciales</label>
                <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">Solo podrá usar las credenciales (servicios con usuario/contraseña o solo certificado) que marque aquí. Si no marca ninguna, no tendrá acceso a credenciales desde la extensión.</p>
                <input type="text" id="credential-search" placeholder="Buscar por nombre o URL…" class="mb-3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <div id="credential-list" class="max-h-64 overflow-y-auto space-y-2 rounded border border-gray-200 dark:border-gray-600 p-3">
                    @foreach($credentials as $cred)
                        <label class="credential-row flex items-center gap-2 py-1.5 text-sm" data-name="{{ Str::lower($cred->website_name ?? '') }}" data-pattern="{{ Str::lower($cred->website_url_pattern ?? '') }}">
                            <input type="checkbox" name="credential_ids[]" value="{{ $cred->id }}" {{ in_array($cred->id, old('credential_ids', [])) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $cred->website_name }}</span>
                            <span class="text-gray-500 dark:text-gray-400 truncate">— {{ $cred->website_url_pattern }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
            <a href="{{ route('certificates.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                Cancelar
            </a>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                Crear Certificado
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const neverExpiresCheckbox = document.getElementById('never_expires');
    const validUntilInput = document.getElementById('valid_until');
    const isBecarioCheckbox = document.getElementById('is_becario');

    function updateValidUntilState() {
        const becario = isBecarioCheckbox && isBecarioCheckbox.checked;
        if (becario) {
            neverExpiresCheckbox.checked = false;
            neverExpiresCheckbox.disabled = true;
            validUntilInput.disabled = false;
            validUntilInput.required = true;
        } else {
            neverExpiresCheckbox.disabled = false;
            if (neverExpiresCheckbox.checked) {
                validUntilInput.disabled = true;
                validUntilInput.removeAttribute('required');
            } else {
                validUntilInput.disabled = false;
                validUntilInput.removeAttribute('required');
            }
        }
    }

    if (neverExpiresCheckbox) {
        neverExpiresCheckbox.addEventListener('change', function() {
            if (isBecarioCheckbox && isBecarioCheckbox.checked) return;
            if (this.checked) {
                validUntilInput.disabled = true;
                validUntilInput.removeAttribute('required');
            } else {
                validUntilInput.disabled = false;
                validUntilInput.setAttribute('required', 'required');
            }
        });
    }
    if (isBecarioCheckbox) {
        isBecarioCheckbox.addEventListener('change', updateValidUntilState);
    }
    updateValidUntilState();

    var credentialSearch = document.getElementById('credential-search');
    var credentialRows = document.querySelectorAll('.credential-row');
    if (credentialSearch && credentialRows.length) {
        credentialSearch.addEventListener('input', function() {
            var q = this.value.trim().toLowerCase();
            credentialRows.forEach(function(row) {
                var show = !q || (row.getAttribute('data-name') && row.getAttribute('data-name').indexOf(q) !== -1) || (row.getAttribute('data-pattern') && row.getAttribute('data-pattern').indexOf(q) !== -1);
                row.style.display = show ? '' : 'none';
            });
        });
    }
});
</script>
@endsection
