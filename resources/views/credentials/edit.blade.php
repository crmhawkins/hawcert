@extends('layouts.app')

@section('title', 'Editar Credencial')

@section('content')
<div class="px-4 sm:px-0">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Editar Credencial</h1>

    <form action="{{ route('credentials.update', $credential) }}" method="POST" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="website_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Sitio Web</label>
                <input type="text" name="website_name" id="website_name" required value="{{ old('website_name', $credential->website_name) }}" placeholder="Ej: IONOS" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <label for="website_url_pattern" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Patrón de URL</label>
                <input type="text" name="website_url_pattern" id="website_url_pattern" required value="{{ old('website_url_pattern', $credential->website_url_pattern) }}" placeholder="Ej: *ionos.com* o https://www.ionos.com/*" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Usa * como comodín (ej: *ionos.com*)</p>
            </div>

            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Usuario (opcional)</label>
                <select name="user_id" id="user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Ninguno</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id', $credential->user_id) == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="certificate_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Certificado (opcional)</label>
                <select name="certificate_id" id="certificate_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Ninguno</option>
                    @foreach($certificates as $certificate)
                        <option value="{{ $certificate->id }}" {{ old('certificate_id', $credential->certificate_id) == $certificate->id ? 'selected' : '' }}>{{ $certificate->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Debe seleccionar al menos un usuario o certificado</p>
            </div>

            <div>
                <label for="username_field_selector" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Selector CSS del Campo Usuario</label>
                <input type="text" name="username_field_selector" id="username_field_selector" required value="{{ old('username_field_selector', $credential->username_field_selector) }}" placeholder="Ej: #username, input[name='email']" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <label for="password_field_selector" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Selector CSS del Campo Contraseña</label>
                <input type="text" name="password_field_selector" id="password_field_selector" required value="{{ old('password_field_selector', $credential->password_field_selector) }}" placeholder="Ej: #password, input[type='password']" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Usuario</label>
                <input type="text" name="username" id="username" required value="{{ old('username', $credential->username) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña</label>
                <input type="password" name="password" id="password" required value="{{ old('password', $credential->password) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <label for="submit_button_selector" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Selector CSS del Botón de Envío (opcional)</label>
                <input type="text" name="submit_button_selector" id="submit_button_selector" value="{{ old('submit_button_selector', $credential->submit_button_selector) }}" placeholder="Ej: button[type='submit'], #login-button" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div class="sm:col-span-2">
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notas</label>
                <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('notes', $credential->notes) }}</textarea>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="auto_fill" value="1" {{ old('auto_fill', $credential->auto_fill) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Rellenar automáticamente</span>
                </label>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="auto_submit" value="1" {{ old('auto_submit', $credential->auto_submit) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enviar automáticamente después de rellenar</span>
                </label>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $credential->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Activa</span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
            <a href="{{ route('credentials.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                Cancelar
            </a>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                Actualizar Credencial
            </button>
        </div>
    </form>
</div>
@endsection
