<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar Sesión - HawCert</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                HawCert
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Sistema de Gestión de Certificados Electrónicos
            </p>
        </div>

        {{-- Login con certificado (método por defecto) --}}
        <form class="mt-8 space-y-6" method="POST" action="{{ route('login.certificate') }}" enctype="multipart/form-data">
            @csrf
            <div>
                <label for="certificate_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Certificado Electrónico
                </label>
                <input id="certificate_file" name="certificate_file" type="file" required
                       accept=".pem,.crt,.cer,.p12,.pfx"
                       class="block w-full text-sm text-gray-500 dark:text-gray-400
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-md file:border-0
                              file:text-sm file:font-semibold
                              file:bg-indigo-50 file:text-indigo-700
                              hover:file:bg-indigo-100
                              dark:file:bg-indigo-900 dark:file:text-indigo-200
                              dark:hover:file:bg-indigo-800
                              file:cursor-pointer
                              border border-gray-300 dark:border-gray-600 rounded-md
                              dark:bg-gray-700">
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Sube tu certificado X.509 emitido por HawCert (PEM, CRT, CER, P12, PFX)
                </p>
                @error('certificate_file')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Iniciar sesión con certificado
                </button>
            </div>
        </form>

        {{-- Botón para mostrar login de administrador --}}
        <div class="text-center">
            <button type="button" id="toggleAdminLogin" 
                    class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 underline">
                Inicio de administrador
            </button>
        </div>

        {{-- Login clásico con usuario/contraseña (oculto por defecto) --}}
        <div id="adminLoginForm" class="hidden mt-6">
            <div class="flex items-center mb-4">
                <div class="flex-grow border-t border-gray-300 dark:border-gray-700"></div>
                <span class="mx-3 text-xs uppercase text-gray-400">o</span>
                <div class="flex-grow border-t border-gray-300 dark:border-gray-700"></div>
            </div>
            <form class="space-y-4" method="POST" action="{{ route('login') }}">
                @csrf
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input id="email" name="email" type="email" required 
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm dark:bg-gray-700"
                               placeholder="tu@email.com" value="{{ old('email') }}">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña</label>
                        <input id="password" name="password" type="password" required 
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm dark:bg-gray-700"
                               placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" 
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                            Recordarme
                        </label>
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Iniciar sesión
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('toggleAdminLogin').addEventListener('click', function() {
            const adminForm = document.getElementById('adminLoginForm');
            const toggleButton = document.getElementById('toggleAdminLogin');
            
            if (adminForm.classList.contains('hidden')) {
                adminForm.classList.remove('hidden');
                toggleButton.textContent = 'Ocultar inicio de administrador';
            } else {
                adminForm.classList.add('hidden');
                toggleButton.textContent = 'Inicio de administrador';
            }
        });
    </script>
</body>
</html>
