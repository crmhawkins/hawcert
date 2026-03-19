<?php

namespace App\Http\Controllers;

use App\Models\ExtensionDownloadToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ExtensionDownloadController extends Controller
{
    /**
     * @var list<string>
     */
    private const EXTENSION_FILES = [
        'manifest.json',
        'background.js',
        'content.js',
        'popup.js',
        'options.js',
        'popup.html',
        'options.html',
    ];

    /** Minutos de validez del enlace desde que se genera */
    private const TOKEN_TTL_MINUTES = 60;

    /**
     * Panel: generar enlace temporal de un solo uso (requiere sesión).
     */
    public function panel(): View
    {
        return view('extension-download.index');
    }

    /**
     * Crea un token y redirige al panel mostrando el enlace (una vez).
     */
    public function createToken(Request $request): RedirectResponse
    {
        $plainToken = Str::random(64);

        ExtensionDownloadToken::create([
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(self::TOKEN_TTL_MINUTES),
            'user_id' => $request->user()->id,
        ]);

        $url = url()->route('extension.zip', ['token' => $plainToken]);

        return redirect()
            ->route('extension.download.panel')
            ->with('extension_download_url', $url)
            ->with('extension_download_expires_minutes', self::TOKEN_TTL_MINUTES);
    }

    /**
     * Descarga pública con token de un solo uso (sin autenticación).
     */
    public function downloadByToken(string $token): BinaryFileResponse|\Illuminate\Http\Response
    {
        if (strlen($token) < 32 || strlen($token) > 128) {
            abort(404);
        }

        $tokenHash = hash('sha256', $token);

        $consumed = DB::transaction(function () use ($tokenHash) {
            $row = ExtensionDownloadToken::query()
                ->where('token', $tokenHash)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (!$row) {
                return false;
            }

            $row->update(['used_at' => now()]);

            return true;
        });

        if (!$consumed) {
            abort(404, 'Enlace inválido, caducado o ya utilizado.');
        }

        $zipPath = $this->buildExtensionZipPath();
        if ($zipPath === null) {
            abort(500, 'No se pudo generar el archivo.');
        }

        return response()->download($zipPath, 'hawcert-chrome-extension.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @return string|null Ruta absoluta al zip temporal
     */
    private function buildExtensionZipPath(): ?string
    {
        $dir = base_path('chrome-extension');
        if (!is_dir($dir)) {
            return null;
        }

        $tmpZip = tempnam(sys_get_temp_dir(), 'hawcert_ext_');
        if ($tmpZip === false) {
            return null;
        }

        @unlink($tmpZip);
        $zipPath = $tmpZip . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $folderInZip = 'hawcert-chrome-extension/';
        $added = 0;
        foreach (self::EXTENSION_FILES as $file) {
            $full = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_file($full)) {
                $zip->addFile($full, $folderInZip . $file);
                $added++;
            }
        }

        if ($added === 0) {
            $zip->close();
            @unlink($zipPath);

            return null;
        }

        $zip->close();

        return $zipPath;
    }
}
