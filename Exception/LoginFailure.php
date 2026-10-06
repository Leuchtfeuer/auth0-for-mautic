<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAuth0Bundle\Exception;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final class LoginFailure
{
    public static function report(LoggerInterface $logger, string $reason, ?\Throwable $previous = null): never
    {
        $logger->error('Auth0 login failed: '.$reason, null !== $previous ? ['exception' => $previous] : []);

        // Login page shows getMessageKey(), not the exception message. The short reason is a parameter so Auth0 text is not parsed as a translation placeholder.
        throw new CustomUserMessageAuthenticationException('plugin.auth0.login_failed', ['%reason%' => self::shortMessage($reason)], 0, $previous);
    }

    /**
     * Text shown in the login growl. The log keeps the full reason.
     */
    public static function shortMessage(string $reason): string
    {
        $parts = preg_split('/: /', $reason);
        if (false === $parts) {
            $parts = [$reason];
        }

        $parts = array_values(array_filter(
            $parts,
            static fn (string $part): bool => 1 !== preg_match('/^(HTTP \d+|request to Auth0 failed|Auth0 .+ failed)$/', $part),
        ));

        $short = trim(implode(': ', $parts));
        if ('' === $short) {
            $short = 'Auth0 login failed.';
        }

        $short = preg_replace('/https?:\/\/\S+/', 'Auth0', $short) ?? $short;

        if (mb_strlen($short) > 140) {
            $short = rtrim(mb_substr($short, 0, 137)).'...';
        }

        return $short;
    }
}
