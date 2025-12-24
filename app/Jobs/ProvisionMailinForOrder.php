<?php

namespace App\Jobs;

use App\Services\Mailin\MailinProvisioningService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProvisionMailinForOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public int $orderId)
    {
    }

    public function handle(MailinProvisioningService $provisioningService): void
    {
        $provisioningService->provisionOrder($this->orderId);
    }

    public function failed(Exception $exception): void
    {
        Log::error('Mailin provisioning job failed', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);
    }
}
