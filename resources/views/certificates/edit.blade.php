@extends('layouts.app')

@section('title', 'Editar Certificado')

@section('content')
<div class="px-4 sm:px-0">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Editar Certificado</h1>

    <form action="{{ route('certificates.update', $certificate) }}" method="POST" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Usuario</label>
                <select name="user_id" id="user_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ $certificate->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                <input type="text" name="name" id="name" required value="{{ old('name', $certificate->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div class="sm:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('description', $certificate->description) }}</textarea>
            </div>

            <div>
                <label for="valid_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Válido desde</label>
                <input type="datetime-local" name="valid_from" id="valid_from" required value="{{ old('valid_from', $certificate->valid_from->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <label for="valid_until" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Válido hasta</label>
                <input type="datetime-local" name="valid_until" id="valid_until" value="{{ old('valid_until', $certificate->valid_until ? $certificate->valid_until->format('Y-m-d\TH:i') : '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $certificate->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Activo</span>
                </label>
            </div>

            <div class="sm:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" name="never_expires" id="never_expires" value="1" {{ old('never_expires', $certificate->never_expires) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Este certificado nunca expira</span>
                </label>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Servicios</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($services as $service)
                        <label class="flex items-center">
                            <input type="checkbox" name="services[]" value="{{ $service->id }}" {{ $certificate->services->contains($service->id) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $service->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Permisos</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($permissions as $permission)
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" {{ $certificate->permissions->contains($permission->id) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $permission->name }}</span>
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
                Actualizar Certificado
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const neverExpiresCheckbox = document.getElementById('never_expires');
    const validUntilInput = document.getElementById('valid_until');
    
    neverExpiresCheckbox.addEventListener('change', function() {
        if (this.checked) {
            validUntilInput.disabled = true;
            validUntilInput.removeAttribute('required');
        } else {
            validUntilInput.disabled = false;
            validUntilInput.setAttribute('required', 'required');
        }
    });
    
    // Inicializar estado
    if (neverExpiresCheckbox.checked) {
        validUntilInput.disabled = true;
        validUntilInput.removeAttribute('required');
    }
});
</script>
@endsection
