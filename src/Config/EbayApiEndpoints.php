<?php

namespace App\Config;

/**
 * Место где проверяется в какой среде работаем и подбираекм нужный endpoint
 */
class EbayApiEndpoints
{
    public const ENV_PRODUCTION = 'Production';
    public const ENV_SANDBOX = 'Sandbox';

    public const BASE_API_URLS = [
        self::ENV_PRODUCTION => 'https://api.ebay.com',
        self::ENV_SANDBOX => 'https://api.sandbox.ebay.com',
    ];

    public const AUTH_URLS = [
        self::ENV_PRODUCTION => 'https://auth.ebay.com/oauth2/authorize',
        self::ENV_SANDBOX => 'https://auth.sandbox.ebay.com/oauth2/authorize',
    ];

    public const TOKEN_URLS = [
        self::ENV_PRODUCTION => 'https://api.ebay.com/identity/v1/oauth2/token',
        self::ENV_SANDBOX => 'https://api.sandbox.ebay.com/identity/v1/oauth2/token',
    ];

    public static function getAuthUrl(string $environment): string
    {
        return self::getUrlFromMap(self::AUTH_URLS, $environment, 'AUTH');
    }

    public static function getTokenUrl(string $environment): string
    {
        return self::getUrlFromMap(self::TOKEN_URLS, $environment, 'TOKEN');
    }

    public static function getBaseApiUrl(string $environment): string
    {
        return self::getUrlFromMap(self::BASE_API_URLS, $environment, 'BASE API');
    }

    private static function getUrlFromMap(array $map, string $environment, string $type): string
    {
        if (!isset($map[$environment])) {
            throw new \InvalidArgumentException("Неверная среда для {$type} URL: {$environment}");
        }
        return $map[$environment];
    }
}