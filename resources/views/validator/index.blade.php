@extends('layouts.app')

@section('title', 'Validador de Certificados')

@section('content')
<div class="px-4 sm:px-0">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Validador de Certificados</h1>

    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
            Sube un certificado X.509 para verificar su validez, usuario asociado y permisos en el sistema.
        </p>

        <form action="{{ route('validator.validate') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <label for="certificate_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Archivo del Certificado
                </label>
                <input type="file" 
                       name="certificate_file" 
                       id="certificate_file" 
                       accept=".pem,.crt,.cer,.p12,.pfx"
                       required 
                       class="block w-full text-sm text-gray-500 dark:text-gray-400
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-md file:border-0
                              file:text-sm file:font-semibold
                              file:bg-indigo-50 file:text-indigo-700
                              hover:file:bg-indigo-100
                              dark:file:bg-indigo-900 dark:file:text-indigo-300">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Formatos soportados: PEM, CRT, CER, P12, PFX (máx. 10MB)
                </p>
            </div>

            <div>
                <label for="certificate_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Clave del Certificado (Opcional)
                </label>
                <input type="text" 
                       name="certificate_key" 
                       id="certificate_key" 
                       placeholder="cert_xxxxx..."
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Si conoces la clave del certificado, puedes ingresarla para una búsqueda más rápida
                </p>
            </div>

            <div class="flex justify-end">
                <button type="submit" 
                        class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Validar Certificado
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
