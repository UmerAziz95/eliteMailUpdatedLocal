<?php

namespace App\Services\Mailin;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailinClient
{
    private const TOKEN_CACHE_KEY = 'mailin_api_token';

    private ?string $token = null;

    public function authenticate(bool $forceRefresh = false): string
    {
        if (!$forceRefresh && Cache::has(self::TOKEN_CACHE_KEY)) {
            $this->token = Cache::get(self::TOKEN_CACHE_KEY);
            return $this->token;
        }

        $response = $this->httpClient()
            ->asJson()
            ->post($this->buildUrl($this->authEndpoint()), [
                'email' => config('services.mailin.email'),
                'password' => config('services.mailin.password'),
            ]);

        $response->throw();

        $payload = $response->json();
        $token = data_get($payload, 'token');

        if (empty($token)) {
            throw new Exception('Mailin authentication failed: token missing in response.');
        }

        $this->token = $token;

        $expiresIn = (int) data_get($payload, 'expires_in', 3600);
        $expiry = now()->addSeconds(max(60, $expiresIn - 60));
        Cache::put(self::TOKEN_CACHE_KEY, $token, $expiry);

        return $token;
    }

    public function buyDomains(array $domains): string
    {
        $response = $this->request('POST', $this->buyDomainsEndpoint(), [
            'domains' => array_values($domains),
        ]);

        return (string) data_get($response, 'job_id');
    }

    public function getDomainJobStatus(string $jobId): array
    {
        return $this->request('GET', $this->domainStatusEndpoint($jobId));
    }

    public function listDomains(): array
    {
        $response = $this->request('GET', $this->listDomainsEndpoint());

        return (array) data_get($response, 'data', $response);
    }

    public function createMailboxes(array $mailboxes): string
    {
        $response = $this->request('POST', $this->createMailboxesEndpoint(), [
            'mailboxes' => array_values($mailboxes),
        ]);

        return (string) data_get($response, 'job_id');
    }

    public function getMailboxJobStatus(string $jobId): array
    {
        return $this->request('GET', $this->mailboxStatusEndpoint($jobId));
    }

    public function listMailboxes(): array
    {
        $response = $this->request('GET', $this->listMailboxesEndpoint());

        return (array) data_get($response, 'data', $response);
    }

    public function waitForDomainJob(string $jobId, int $timeoutSeconds = 180, int $pollSeconds = 5): array
    {
        return $this->waitForJob(fn () => $this->getDomainJobStatus($jobId), $timeoutSeconds, $pollSeconds);
    }

    public function waitForMailboxJob(string $jobId, int $timeoutSeconds = 180, int $pollSeconds = 5): array
    {
        return $this->waitForJob(fn () => $this->getMailboxJobStatus($jobId), $timeoutSeconds, $pollSeconds);
    }

    private function waitForJob(callable $statusResolver, int $timeoutSeconds, int $pollSeconds): array
    {
        $startedAt = now();

        do {
            $statusPayload = $statusResolver();
            $status = strtolower((string) data_get($statusPayload, 'status', ''));

            if (in_array($status, ['completed', 'completed_with_errors', 'done', 'success'], true)) {
                return $statusPayload;
            }

            if (in_array($status, ['failed', 'error'], true)) {
                throw new Exception('Mailin job failed: ' . json_encode($statusPayload));
            }

            sleep($pollSeconds);
        } while ($startedAt->diffInSeconds(now()) < $timeoutSeconds);

        throw new Exception('Mailin job status check timed out.');
    }

    private function request(string $method, string $path, array $data = [], bool $hasRetriedAuth = false): array
    {
        $token = $this->token ?? $this->authenticate();

        $response = $this->sendRequest($method, $path, $data, $token);

        if ($response->status() === 401 && !$hasRetriedAuth) {
            $token = $this->authenticate(true);
            $response = $this->sendRequest($method, $path, $data, $token);
        }

        $response->throw();

        return (array) $response->json();
    }

    private function sendRequest(string $method, string $path, array $data, string $token): Response
    {
        $maxAttempts = 3;
        $delayBaseMs = 1000;
        $url = $this->buildUrl($path);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = $this->httpClient()
                ->withToken($token)
                ->acceptJson()
                ->asJson()
                ->send($method, $url, $this->buildRequestOptions($method, $data));

            if ($response->status() === 401) {
                return $response;
            }

            if (in_array($response->status(), [429, 500, 502, 503, 504], true) && $attempt < $maxAttempts) {
                $delayMs = $delayBaseMs * $attempt;
                Log::warning('Mailin request retrying due to transient error', [
                    'status' => $response->status(),
                    'attempt' => $attempt,
                    'delay_ms' => $delayMs,
                    'url' => $url,
                ]);
                usleep($delayMs * 1000);
                continue;
            }

            return $response;
        }

        return $response;
    }

    private function buildRequestOptions(string $method, array $data): array
    {
        $method = strtoupper($method);

        if ($method === 'GET') {
            return ['query' => $data];
        }

        return ['json' => $data];
    }

    private function buildUrl(string $path): string
    {
        return rtrim((string) config('services.mailin.base_url'), '/') . '/' . ltrim($path, '/');
    }

    private function httpClient()
    {
        return Http::timeout((int) config('services.mailin.timeout', 30))
            ->withOptions([
                'verify' => filter_var(config('services.mailin.verify_ssl', true), FILTER_VALIDATE_BOOLEAN),
            ]);
    }

    private function authEndpoint(): string
    {
        return (string) config('services.mailin.auth_endpoint', 'auth/login');
    }

    private function buyDomainsEndpoint(): string
    {
        return (string) config('services.mailin.buy_domains_endpoint', 'domains/buy');
    }

    private function domainStatusEndpoint(string $jobId): string
    {
        $base = (string) config('services.mailin.domain_status_endpoint', 'domains/jobs');

        return rtrim($base, '/') . '/' . $jobId;
    }

    private function listDomainsEndpoint(): string
    {
        return (string) config('services.mailin.list_domains_endpoint', 'domains');
    }

    private function createMailboxesEndpoint(): string
    {
        return (string) config('services.mailin.create_mailboxes_endpoint', 'mailboxes/bulk');
    }

    private function mailboxStatusEndpoint(string $jobId): string
    {
        $base = (string) config('services.mailin.mailbox_status_endpoint', 'mailboxes/jobs');

        return rtrim($base, '/') . '/' . $jobId;
    }

    private function listMailboxesEndpoint(): string
    {
        return (string) config('services.mailin.list_mailboxes_endpoint', 'mailboxes');
    }
}
