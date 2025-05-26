<?php

namespace App\Service;

use App\Config\EbayApiEndpoints;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

/**
 * Сервис для работы с OAuth-аутентификацией eBay.
 */
class EbayAuthService
{
    private Client $client;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private array $scopes;
    private string $environment;
    private LoggerInterface $logger;
    private string $authUrl;
    private string $tokenUrl;

    public function __construct(
        ParameterBagInterface $params, 
        LoggerInterface $logger,
        Client $client = null
    )
    {
        $this->client = $client ?? new Client();
        $this->clientId = $params->get('ebay_client_id');
        $this->clientSecret = $params->get('ebay_client_secret');
        $this->redirectUri = $params->get('ebay_redirect_uri');
        $this->scopes = explode(' ', $params->get('ebay_scoope'));
        $environment = $params->get('ebay_environment');
        $this->authUrl = EbayApiEndpoints::getAuthUrl($environment);
        $this->tokenUrl = EbayApiEndpoints::getTokenUrl($environment);
        $this->logger = $logger;
    }
    
    /**
     * Генерирует ссылку для аутентификации пользователя на eBay.
     */
    public function getAuthorizationUrl(): string
    {
        // Проверка на пустые параметры
        if (empty($this->clientId)) {
            $this->logger->error('Client ID is empty.');
        }

        if (empty($this->clientSecret)) {
            $this->logger->error('Client Secret is empty.');
        }

        if (empty($this->redirectUri)) {
            $this->logger->error('Redirect URI is empty.');
        }

        if (empty($this->scopes)) {
            $this->logger->error('Scopes are empty.');
        }

        // Определяем URL авторизации в зависимости от среды
        $authUrl = $this->authUrl;

        // Формируем параметры запроса
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes),
        ];

        // Возвращаем финальную ссылку
        return $authUrl . '?' . http_build_query($params);
    }

    /**
     * Получает access_token, отправляя код авторизации.
     *
     * @param string $code Код авторизации, полученный от eBay.
     * @return array|null Массив с access_token и refresh_token, или null в случае ошибки.
     */
    public function getAccessToken(string $code): ?array
    {
        // Определяем URL для получения токена
        $authUrl = $this->tokenUrl;

        try {
            // Отправляем POST-запрос на получение токена
            $response = $this->client->post($authUrl, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
                ],
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri,
                ],
            ]);

            // Логируем успех
            $this->logger->info('Access token successfully obtained.');

            // Возвращаем декодированный ответ
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Throwable $e) {
            // Логируем ошибку
            $this->logger->error('Error getting access token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Обновляет access_token, используя refresh_token.
     *
     * @param string $refreshToken Refresh token, полученный ранее.
     * @return array|null Массив с новым access_token и refresh_token, или null в случае ошибки.
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        // Определяем URL для обновления токена
        $authUrl = $this->tokenUrl;

        try {
            $response = $this->client->post($authUrl, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
                ],
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'scope' => implode(' ', $this->scopes),
                ],
            ]);

            $this->logger->info('Access token refreshed successfully.');
            return json_decode($response->getBody()->getContents(), true);

        } catch (\Throwable $e) {
            $this->logger->error('Error updating access token: ' . $e->getMessage());
            return null;
        }
    }
}

