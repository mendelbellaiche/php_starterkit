<?php

declare(strict_types=1);

const CONTROLLER_NAMESPACE = "\\Controllers\\";

/**
 * Retourne une variable d'environnement trimmee, ou null si absente/vide.
 */
function envValue(string $key): ?string
{
    $value = getenv($key);
    if ($value === false) {
        return null;
    }

    $value = trim($value);
    return $value === '' ? null : $value;
}

/**
 * Exige une variable d'environnement non vide.
 */
function envOrThrow(string $key): string
{
    $value = envValue($key);
    if ($value === null) {
        throw new RuntimeException($key . ' manquant');
    }
    return $value;
}

// Environnement
$rawAppEnv = strtolower(envValue('APP_ENV') ?? 'dev');
$allowedEnvs = ['dev', 'test', 'staging', 'prod'];

if (!in_array($rawAppEnv, $allowedEnvs, true)) {
    throw new RuntimeException(
        "APP_ENV invalide: '{$rawAppEnv}'. Valeurs autorisees: " . implode(', ', $allowedEnvs)
    );
}

define('APP_ENV', $rawAppEnv);
define('APP_DEBUG', (envValue('APP_DEBUG') ?? '0') === '1');

if (in_array(APP_ENV, ['prod', 'staging'], true) && APP_DEBUG) {
    throw new RuntimeException('APP_DEBUG ne doit pas etre active en production/staging');
}

// URL de base
define('SITE_URL', envValue('SITE_URL') ?? 'http://localhost');

// Validation de format URL (dev + prod)
if (filter_var(SITE_URL, FILTER_VALIDATE_URL) === false) {
    throw new RuntimeException('SITE_URL invalide (format URL attendu)');
}

$siteUser = parse_url(SITE_URL, PHP_URL_USER);
$sitePass = parse_url(SITE_URL, PHP_URL_PASS);
if ($siteUser !== null || $sitePass !== null) {
    throw new RuntimeException('SITE_URL ne doit pas contenir de credentials');
}

// Base de donnees: stricte en prod, defaults en dev/test
if (in_array(APP_ENV, ['prod', 'staging'], true)) {
    define('DB_HOST', envOrThrow('DB_HOST'));
    define('DB_NAME', envOrThrow('DB_NAME'));
    define('DB_USER', envOrThrow('DB_USER'));
    define('DB_PASS', envOrThrow('DB_PASS'));

    $siteScheme = parse_url(SITE_URL, PHP_URL_SCHEME);
    if ($siteScheme !== 'https') {
        throw new RuntimeException('SITE_URL doit utiliser https en production/staging');
    }
} else {
    define('DB_HOST', envValue('DB_HOST') ?? '127.0.0.1');
    define('DB_NAME', envValue('DB_NAME') ?? 'starterkit');
    define('DB_USER', envValue('DB_USER') ?? '');
    define('DB_PASS', envValue('DB_PASS') ?? '');
}