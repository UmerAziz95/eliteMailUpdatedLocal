<?php

namespace App\Exceptions;

use App\Models\ErrorLog;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            try {
                ErrorLog::logException($e);
            } catch (\Exception $logException) {
                // Use app logger if available, otherwise fallback to error_log
                try {
                    app('log')->error('Failed to log exception to DB: ' . $logException->getMessage());
                } catch (\Throwable $t) {
                    error_log('Failed to log exception to DB: ' . $logException->getMessage());
                }
            }
        });
    }
}
