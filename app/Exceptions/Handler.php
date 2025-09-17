<?php

namespace App\Exceptions;

use App\Models\ErrorLog;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log exception to database
            try {
                ErrorLog::logException($e);
            } catch (\Exception $logException) {
                // If logging fails, just continue with the original exception
                // This prevents infinite loops if there's an issue with the database
                \Log::error('Failed to log exception to database: ' . $logException->getMessage());
            }
        });
    }
}