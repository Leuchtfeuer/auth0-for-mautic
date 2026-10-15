<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAuth0Bundle\Helper;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LoginFailure
{
    private const MAX_REASON_LENGTH = 140;

    private const ELLIPSIS = '...';

    /**
     * Log the failure and throw a user-visible authentication error.
     *
     * Mautic's login SecurityController flashes AuthenticationException::getMessage()
     * with empty translation vars, so getMessageData() is never applied. The exception
     * message must already be the final plain-text string shown in the growl.
     *
     * @throws CustomUserMessageAuthenticationException
     */
    public static function report(
        LoggerInterface $logger,
        string $reason,
        ?\Throwable $previous = null,
        ?string $userMessage = null,
        ?TranslatorInterface $translator = null,
    ): never {
        $logger->error('Auth0 login failed: '.$reason, null !== $previous ? ['exception' => $previous] : []);

        $safeReason = self::shortMessage($userMessage ?? $reason);
        $message    = null !== $translator
            ? $translator->trans('plugin.auth0.login_failed', ['%reason%' => $safeReason])
            : 'Auth0 login failed: '.$safeReason;

        throw new CustomUserMessageAuthenticationException($message, [], 0, $previous);
    }

    /**
     * Log an unexpected failure. Mautic validation errors keep their own translated message.
     * Everything else is logged in full and shown on the login notification (truncated).
     *
     * @throws AuthenticationException
     */
    public static function reportUnexpected(
        LoggerInterface $logger,
        \Throwable $exception,
        ?TranslatorInterface $translator = null,
    ): never {
        if ($exception instanceof AuthenticationException && !$exception instanceof CustomUserMessageAuthenticationException) {
            $logger->error('Auth0 login failed: '.$exception->getMessage(), ['exception' => $exception]);

            throw $exception;
        }

        self::report($logger, $exception->getMessage(), $exception, null, $translator);
    }

    /**
     * Text shown in the login growl. The log keeps the full reason.
     * HTML is stripped so Twig |purify cannot wipe the flash content.
     */
    public static function shortMessage(string $reason): string
    {
        $short = trim(html_entity_decode(strip_tags($reason), ENT_QUOTES | ENT_HTML5));
        if ('' === $short) {
            $short = 'Auth0 login failed.';
        }

        $short = preg_replace('/https?:\/\/\S+/', 'Auth0', $short) ?? $short;

        if (mb_strlen($short) > self::MAX_REASON_LENGTH) {
            $short = rtrim(mb_substr($short, 0, self::MAX_REASON_LENGTH - mb_strlen(self::ELLIPSIS))).self::ELLIPSIS;
        }

        return $short;
    }
}
