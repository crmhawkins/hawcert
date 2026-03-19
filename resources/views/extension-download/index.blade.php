@extends('layouts.app')

@section('title', 'Descarga de extensión')

@section('content')
<div class="px-4 sm:px-0 max-w-3xl">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Extensión Chrome (ZIP)</h1>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
        Genera un enlace temporal de <strong>un solo uso</strong>. Cualquiera con el enlace puede descargar el ZIP <strong>sin iniciar sesión</strong> hasta que caduque o se use una vez.
    </p>

    @if(session('extension_download_url'))
        <div class="mb-6 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
            <p class="text-sm font-medium text-amber-900 dark:text-amber-100 mb-2">Copia este enlace ahora (no se volverá a mostrar completo):</p>
            <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" readonly id="ext-url" value="{{ session('extension_download_url') }}" class="flex-1 font-mono text-xs sm:text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2">
                <button type="button" id="copy-url" class="shrink-0 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Copiar</button>
            </div>
            <p class="mt-2 text-xs text-amber-800 dark:text-amber-200">
                Caduca en {{ session('extension_download_expires_minutes', 60) }} minutos. Tras la primera descarga, el enlace deja de ser válido.
            </p>
        </div>
        <script>
            document.getElementById('copy-url')?.addEventListener('click', function() {
                var el = document.getElementById('ext-url');
                el.select();
                el.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(el.value).then(function() {
                    this.textContent = 'Copiado';
                    var btn = this;
                    setTimeout(function() { btn.textContent = 'Copiar'; }, 2000);
                }.bind(this));
            });
        </script>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <form method="POST" action="{{ route('extension.download.token') }}">
            @csrf
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                Generar nuevo enlace de descarga
            </button>
        </form>
        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
            En Chrome: descomprime el ZIP, abre <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">chrome://extensions</code>, activa «Modo de desarrollador» y «Cargar descomprimida» en la carpeta <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">hawcert-chrome-extension</code>.
        </p>
    </div>
</div>
@endsection
