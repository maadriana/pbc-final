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

// Admin Routes with Enhanced Upload Configuration Middleware
Route::middleware(['auth', 'admin', 'check.upload.config'])->prefix('admin')->name('admin.')->group(function () {
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

    // Enhanced Upload Configuration Routes
    Route::get('upload/limits', [FileUploadController::class, 'getUploadLimits'])
        ->name('upload.limits');
    Route::get('upload/stats', [FileUploadController::class, 'getUploadStats'])
        ->name('upload.stats');
    Route::get('upload/test', [FileUploadController::class, 'testUpload'])
        ->name('upload.test');
    Route::post('files/validate', [FileUploadController::class, 'validateFile'])
        ->name('files.validate');

    // FIXED: PBC Request Item Actions (Global Routes) - Updated routes
    Route::post('pbc-requests/items/{item}/upload', [FileUploadController::class, 'uploadForItem'])
        ->name('pbc-requests.items.upload');
    Route::post('pbc-requests/items/{item}/approve', [AdminPbcRequestController::class, 'approveItem'])
        ->name('pbc-requests.items.approve');
    Route::post('pbc-requests/items/{item}/reject', [AdminPbcRequestController::class, 'rejectItemGlobal'])
        ->name('pbc-requests.items.reject');
    Route::get('pbc-requests/items/{item}/files', [AdminPbcRequestController::class, 'getItemFiles'])
        ->name('pbc-requests.items.files');

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

        // Enhanced reminder routes for global index
        Route::post('reminders/send', [ReminderController::class, 'sendGlobalReminder'])
            ->name('reminders.send');
    });

    // FIXED: Document Management & Archive Routes - Proper ordering and clear naming
    Route::prefix('documents')->name('documents.')->group(function () {
        // Index and show routes
        Route::get('/', [AdminDocumentController::class, 'index'])->name('index');
        Route::get('/deleted', [AdminDocumentController::class, 'deletedIndex'])->name('deleted'); // ADDED: Deleted documents archive
        Route::get('/{document}', [AdminDocumentController::class, 'show'])->name('show');

        // Document approval routes
        Route::patch('/{document}/approve', [AdminDocumentController::class, 'approve'])->name('approve');
        Route::patch('/{document}/reject', [AdminDocumentController::class, 'reject'])->name('reject');

        // FIXED: Document delete routes with proper naming
        Route::delete('/{document}', [AdminDocumentController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [AdminDocumentController::class, 'bulkDelete'])->name('bulk-delete');
    });

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

    // FIXED: Project-specific PBC Request Management Routes
    Route::prefix('clients/{client}/projects/{project}')->name('clients.projects.')->group(function () {
        // Project-specific PBC request management
        Route::get('pbc-requests', [AdminPbcRequestController::class, 'projectIndex'])
            ->name('pbc-requests.index');
        Route::get('pbc-requests/create', [AdminPbcRequestController::class, 'projectCreate'])
            ->name('pbc-requests.create');
        Route::post('pbc-requests', [AdminPbcRequestController::class, 'projectStore'])
            ->name('pbc-requests.store');

        // Edit and Update routes for PBC requests
        Route::get('pbc-requests/edit', [AdminPbcRequestController::class, 'projectEdit'])
            ->name('pbc-requests.edit');
        Route::put('pbc-requests', [AdminPbcRequestController::class, 'projectUpdate'])
            ->name('pbc-requests.update');

        // FIXED: Updated reject route with proper parameter binding
        Route::post('pbc-requests/{pbcRequest}/items/{item}/reject', [AdminPbcRequestController::class, 'rejectItem'])
            ->name('pbc-requests.items.reject');

        // Project-specific Import Routes
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
