<?php

namespace App\Services\Providers;

use App\Contracts\Providers\SmtpProviderInterface;
use InvalidArgumentException;

/**
 * Trait for creating provider instances by slug
 */
trait CreatesProviders
{
    /**
     * Create provider instance by slug
     * 
     * @param string $slug Provider slug (mailin, premiuminboxes, mailrun)
     * @param array $credentials Provider credentials
     * @return SmtpProviderInterface
     * @throws InvalidArgumentException
     */
    protected function createProvider(string $slug, array $credentials): SmtpProviderInterface
    {
        return match ($slug) {
            'mailin' => new MailinProviderService($credentials),
            'premiuminboxes' => new PremiuminboxesProviderService($credentials),
            'mailrun' => new MailrunProviderService($credentials),
            default => throw new InvalidArgumentException("Unknown provider: {$slug}")
        };
    }
}
