<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateUsageLogController;
use App\Http\Controllers\CertificateValidatorController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExtensionDownloadController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/login/certificate', [AuthController::class, 'loginWithCertificate'])->name('login.certificate')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/** Descarga pública de la extensión con token de un solo uso (sin auth) */
Route::get('/e/{token}', [ExtensionDownloadController::class, 'downloadByToken'])
    ->name('extension.zip')
    ->where('token', '[A-Za-z0-9]+');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::resource('certificates', CertificateController::class);
    Route::get('certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');
    Route::resource('services', ServiceController::class);
    Route::resource('credentials', CredentialController::class);

    Route::get('/logs', [CertificateUsageLogController::class, 'index'])->name('logs.index');

    Route::get('/validator', [CertificateValidatorController::class, 'index'])->name('validator.index');
    Route::post('/validator/validate', [CertificateValidatorController::class, 'validate'])->name('validator.validate');

    Route::get('/extension-download', [ExtensionDownloadController::class, 'panel'])->name('extension.download.panel');
    Route::post('/extension-download/token', [ExtensionDownloadController::class, 'createToken'])->name('extension.download.token');
});
