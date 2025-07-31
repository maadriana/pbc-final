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
use App\Http\Controllers\Admin\ReminderController;
use App\Http\Controllers\Admin\ImportController;
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
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // User Management (System Admin only)
    Route::resource('users', UserController::class);

    // Client Management (System Admin, Engagement Partner, Manager)
    Route::resource('clients', ClientController::class);

    // Project Management
    Route::resource('projects', ProjectController::class);

    // Team Management Routes
    Route::post('projects/{project}/assign-team-member', [ProjectController::class, 'assignTeamMember'])
        ->name('projects.assign-team-member');
    Route::delete('projects/{project}/remove-team-member', [ProjectController::class, 'removeTeamMember'])
        ->name('projects.remove-team-member');

    // Client Assignment Routes
    Route::post('projects/{project}/assign-client', [ProjectController::class, 'assignClient'])
        ->name('projects.assign-client');
    Route::delete('projects/{project}/remove-client/{client}', [ProjectController::class, 'removeClient'])
        ->name('projects.remove-client');

    // PBC Template Management
    Route::resource('pbc-templates', PbcTemplateController::class);

    // PBC Request Management (existing)
    Route::resource('pbc-requests', AdminPbcRequestController::class);
    Route::post('pbc-requests/{pbcRequest}/send', [AdminPbcRequestController::class, 'send'])
        ->name('pbc-requests.send');
    Route::patch('pbc-requests/{pbcRequest}/items/{item}/review', [AdminPbcRequestController::class, 'reviewItem'])
        ->name('pbc-requests.review-item');

    // Global Import System Routes
    Route::prefix('pbc-requests')->name('pbc-requests.')->group(function () {
        // Import form and preview
        Route::get('import', [ImportController::class, 'showImportForm'])->name('import');
        Route::post('import/preview', [ImportController::class, 'preview'])->name('import.preview');
        Route::post('import/execute', [ImportController::class, 'import'])->name('import.execute');

        // Template download
        Route::get('import/template', [ImportController::class, 'downloadTemplate'])->name('import.template');

        // Bulk import (direct without preview)
        Route::post('import/bulk', [ImportController::class, 'bulkImport'])->name('import.bulk');

        // Import statistics API
        Route::get('import/stats', [ImportController::class, 'getImportStats'])->name('import.stats');
    });

    // Document Management & Archive (All admin roles)
    Route::get('documents', [AdminDocumentController::class, 'index'])->name('documents.index');
    Route::get('documents/{document}', [AdminDocumentController::class, 'show'])->name('documents.show');
    Route::patch('documents/{document}/approve', [AdminDocumentController::class, 'approve'])
        ->name('documents.approve');
    Route::patch('documents/{document}/reject', [AdminDocumentController::class, 'reject'])
        ->name('documents.reject');

    // Progress Tracking
    Route::get('progress', [AdminDashboardController::class, 'progress'])->name('progress');

    // Reminder routes
    Route::post('reminders/send', [ReminderController::class, 'send'])->name('reminders.send');
    Route::post('reminders/quick-send', [ReminderController::class, 'quickSend'])->name('reminders.quick-send');
    Route::post('reminders/bulk-send', [ReminderController::class, 'bulkSend'])->name('reminders.bulk-send');

    // AJAX endpoints for dynamic loading
    Route::get('api/templates/{template}/items', [PbcTemplateController::class, 'getTemplateItems'])
        ->name('api.templates.items');
    Route::get('api/clients/{client}/projects', [ClientController::class, 'getClientProjects'])
        ->name('api.clients.projects');
    Route::post('api/pbc-requests/items/{item}/upload', [FileUploadController::class, 'upload'])
        ->name('api.pbc-requests.upload');
    Route::delete('api/documents/{document}', [FileUploadController::class, 'delete'])
        ->name('api.documents.delete');

    // Project-specific PBC Request Management Routes
    Route::prefix('clients/{client}/projects/{project}')->name('clients.projects.')->group(function () {
        // Project-specific PBC request management
        Route::get('pbc-requests', [AdminPbcRequestController::class, 'projectIndex'])
            ->name('pbc-requests.index');
        Route::get('pbc-requests/create', [AdminPbcRequestController::class, 'projectCreate'])
            ->name('pbc-requests.create');
        Route::post('pbc-requests', [AdminPbcRequestController::class, 'projectStore'])
            ->name('pbc-requests.store');

        // Project-specific Import Routes - CONSOLIDATED
        Route::prefix('pbc-requests')->name('pbc-requests.')->group(function () {
            // Import form
            Route::get('import', [ImportController::class, 'showProjectImportForm'])
                ->name('import');

            // Import preview
            Route::post('import/preview', [ImportController::class, 'projectPreview'])
                ->name('import.preview');

            // Import execute
            Route::post('import/execute', [ImportController::class, 'projectImport'])
                ->name('import.execute');

            // Template download for project
            Route::get('import/template', [ImportController::class, 'downloadProjectTemplate'])
                ->name('import.template');
        });
    });

    // API endpoint for getting project details
    Route::get('api/projects/{project}/details', function(\App\Models\Project $project) {
        return response()->json([
            'client_id' => $project->client_id,
            'client_name' => $project->client->company_name ?? '',
            'engagement_type' => $project->engagement_type,
            'engagement_name' => $project->engagement_name,
            'job_id' => $project->job_id,
        ]);
    })->name('api.projects.details');

    // Template items endpoint
    Route::get('pbc-templates/{templateId}/items', function($templateId) {
        $items = \DB::table('pbc_template_items')
            ->where('pbc_template_id', $templateId)
            ->orderBy('order_index')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $items->count(),
            'data' => $items
        ]);
    })->name('pbc-templates.items');
});

// Client Routes
Route::middleware(['auth', 'client'])->prefix('client')->name('client.')->group(function () {
    // Dashboard
    Route::get('dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');

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

    // Simple Reminder Route (for future use)
    Route::patch('reminders/{reminder}/read', function(\App\Models\Reminder $reminder) {
        // Check if reminder belongs to current client
        if ($reminder->pbcRequest->client_id !== auth()->user()->client->id) {
            abort(403);
        }

        $reminder->markAsRead();

        return response()->json(['success' => true]);
    })->name('reminders.read');

    // Placeholder reminders page
    Route::get('reminders', function() {
        return view('client.reminders-placeholder');
    })->name('reminders.index');
});

require __DIR__.'/auth.php';
