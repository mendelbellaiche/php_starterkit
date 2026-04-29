<?php

namespace Core;

class CsrfHelper
{
    private const SESSION_KEY = '_csrf_token';

    /**     * Retourne le token CSRF existant ou en génère un nouveau     */
    public static function getToken(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**     * Valide le token soumis contre celui en session     */
    public static function validate(?string $submittedToken): bool
    {
        if (empty($submittedToken) || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::SESSION_KEY], $submittedToken);
    }

    /**     * Regénère un nouveau token (après usage si tu veux one-time tokens)     */
    public static function regenerate(): void
    {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
    }
}