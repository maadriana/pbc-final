<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ReminderService;
use App\Services\JobGenerationService;
use App\Services\ExcelImportService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Existing service registrations
        $this->app->singleton(ReminderService::class, function ($app) {
            return new ReminderService();
        });

        // NEW: Register JobGenerationService
        $this->app->singleton(JobGenerationService::class, function ($app) {
            return new JobGenerationService();
        });

        // NEW: Register ExcelImportService
        $this->app->singleton(ExcelImportService::class, function ($app) {
            return new ExcelImportService();
        });
    }

    public function boot(): void
    {
        //
    }
}
