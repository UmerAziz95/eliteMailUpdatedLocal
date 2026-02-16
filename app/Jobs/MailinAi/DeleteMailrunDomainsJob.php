<?php

namespace App\Jobs\MailinAi;

use App\Services\Providers\MailrunProviderService;
use App\Models\SmtpProviderSplit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class DeleteMailrunDomainsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $domains;
    protected $providerSlug;

    /**
     * Create a new job instance.
     *
     * @param array $domains List of domains to delete
     * @param string $providerSlug Provider slug to get credentials
     */
    public function __construct(array $domains, string $providerSlug = 'mailrun')
    {
        $this->domains = $domains;
        $this->providerSlug = $providerSlug;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->domains)) {
            return;
        }

        // Rate Limit: 10 requests per hour
        // Key is global for the provider to ensure we don't exceed API limits across multiple jobs
        $limiterKey = 'mailrun-api-delete-limit';

        if (RateLimiter::tooManyAttempts($limiterKey, 10)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            Log::channel('mailin-ai')->info('Mailrun: Delete job rate limited. Releasing.', [
                'seconds_wait' => $seconds,
                'domains_pending' => count($this->domains)
            ]);
            $this->release($seconds + 30); // Release with sufficient delay
            return;
        }

        // Batch processing: max 20 domains per request
        $batch = array_slice($this->domains, 0, 20);
        $remaining = array_slice($this->domains, 20);

        try {
            $providerConfig = SmtpProviderSplit::getBySlug($this->providerSlug);
            $credentials = $providerConfig ? $providerConfig->getCredentials() : [];

            // Fallback for valid credentials check
            if (empty($credentials) || empty($credentials['api_key'] ?? $credentials['api_token'] ?? $credentials['password'] ?? '')) {
                // Try config if DB fails
                $credentials = [
                    'api_key' => config('services.mailrun.api_key'),
                    'base_url' => config('services.mailrun.base_url', 'https://api.mailrun.ai/api'),
                ];
            }

            if (empty($credentials) || empty($credentials['api_key'] ?? $credentials['api_token'] ?? $credentials['password'] ?? '')) {
                Log::channel('mailin-ai')->error('Mailrun: Credentials missing for delete job');
                return;
            }

            $service = new MailrunProviderService($credentials);

            Log::channel('mailin-ai')->info('Mailrun: Job executing deleteDomains', ['count' => count($batch)]);

            $result = $service->deleteDomains($batch);

            // Record the API hit against the limiter
            RateLimiter::hit($limiterKey, 3600); // 3600 seconds = 1 hour decay

            if (!$result['success']) {
                Log::channel('mailin-ai')->error('Mailrun: Job delete failed', ['result' => $result]);
            } else {
                Log::channel('mailin-ai')->info('Mailrun: Job delete success', ['result' => $result]);
            }

            // Dispatch remaining domains
            if (!empty($remaining)) {
                self::dispatch($remaining, $this->providerSlug);
            }

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailrun: Delete job exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->release(300); // Retry in 5 minutes on exception
        }
    }
}
