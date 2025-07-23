<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\PbcTemplateController;
use App\Http\Controllers\Admin\PbcRequestController as AdminPbcRequestController;
use App\Http\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\PbcRequestController as ClientPbcRequestController;
use App\Http\Controllers\Client\DocumentController as ClientDocumentController;
use App\Http\Controllers\FileUploadController;

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('client.dashboard');
    }
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // File download route (accessible by both admin and client with access control)
    Route::get('/documents/{document}/download', [FileUploadController::class, 'download'])
        ->name('documents.download');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // User Management
    Route::resource('users', UserController::class);

    // Client Management
    Route::resource('clients', ClientController::class);

    // Project Management
    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/assign-client', [ProjectController::class, 'assignClient'])
        ->name('projects.assign-client');
    Route::delete('projects/{project}/remove-client/{client}', [ProjectController::class, 'removeClient'])
        ->name('projects.remove-client');

    // PBC Template Management
    Route::resource('pbc-templates', PbcTemplateController::class);

    // PBC Request Management
    Route::resource('pbc-requests', AdminPbcRequestController::class);
    Route::post('pbc-requests/{pbcRequest}/send', [AdminPbcRequestController::class, 'send'])
        ->name('pbc-requests.send');
    Route::patch('pbc-requests/{pbcRequest}/items/{item}/review', [AdminPbcRequestController::class, 'reviewItem'])
        ->name('pbc-requests.review-item');

    // Document Management & Archive
    Route::get('documents', [AdminDocumentController::class, 'index'])->name('documents.index');
    Route::get('documents/{document}', [AdminDocumentController::class, 'show'])->name('documents.show');
    Route::patch('documents/{document}/approve', [AdminDocumentController::class, 'approve'])
        ->name('documents.approve');
    Route::patch('documents/{document}/reject', [AdminDocumentController::class, 'reject'])
        ->name('documents.reject');

    // Progress Tracking
    Route::get('progress', [AdminDashboardController::class, 'progress'])->name('progress');

    // API endpoints for dynamic loading
Route::get('api/templates/{template}/items', [PbcTemplateController::class, 'getTemplateItems'])
    ->name('api.templates.items');
Route::get('api/clients/{client}/projects', [ClientController::class, 'getClientProjects'])
    ->name('api.clients.projects');
Route::post('api/pbc-requests/items/{item}/upload', [FileUploadController::class, 'upload'])
    ->name('api.pbc-requests.upload');
Route::delete('api/documents/{document}', [FileUploadController::class, 'delete'])
    ->name('api.documents.delete');
});

// Client Routes
Route::middleware(['auth', 'client'])->prefix('client')->name('client.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');

    // PBC Requests (Main Feature for Client)
    Route::get('pbc-requests', [ClientPbcRequestController::class, 'index'])->name('pbc-requests.index');
    Route::get('pbc-requests/{pbcRequest}', [ClientPbcRequestController::class, 'show'])
        ->name('pbc-requests.show');
    Route::post('pbc-requests/{pbcRequest}/items/{item}/upload', [ClientPbcRequestController::class, 'upload'])
        ->name('pbc-requests.upload');

    // Document Archive
    Route::get('documents', [ClientDocumentController::class, 'index'])->name('documents.index');
    Route::get('documents/{document}', [ClientDocumentController::class, 'show'])->name('documents.show');

    // Progress Tracking
    Route::get('progress', [ClientDashboardController::class, 'progress'])->name('progress');
});

require __DIR__.'/auth.php';
