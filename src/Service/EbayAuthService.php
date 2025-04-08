<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class EbayAuthService
{
    private Client $client;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private array $scopes;
    private string $Environment;
    private LoggerInterface $logger;

    public function __construct(ParameterBagInterface $params, LoggerInterface $logger)
    {
        $this->client = new Client();
        $this->clientId = $params->get('ebay_client_id'); // Получаем client_id из параметров
        $this->clientSecret = $params->get('ebay_client_secret'); // Получаем client_secret
        $this->redirectUri = $params->get('ebay_redirect_uri');  // Получаем redirect_uri
        $this->scopes = explode(' ', $params->get('ebay_scoope')); // Преобразуем строку scopes в массив
        $this->Environment = $params->get('ebay_environment'); // Получаем среду выполнения (Sandbox или Production)
        $this->logger = $logger; // Инициализируем логгер
    }

    public function getAuthorizationUrl(): string  //Метод дляГенерирует ссылку для аутентификации пользователя.
    {
        if (empty($this->clientId)) {  //добавил проверку не пустпя ли переменая clientId
            $this->logger->error('Client ID is empty.');
        }

        if (empty($this->clientSecret)) { //проверка не пустпя ли переменая redirectUri
            $this->logger->error('Client Secret is empty.');
        }
    
        if (empty($this->redirectUri)) { //проверка не пустпя ли переменая redirectUri
            $this->logger->error('Redirect URI is empty.');
        }
    
        if (empty($this->scopes)) {  //проверка не пустпя ли переменая scopes
            $this->logger->error('Scopes are empty.');
        }
    
        $authUrl = match ($this->Environment) { //проверка в какой среде нужно работать и какое URL использовать 
            'Production' => 'https://auth.ebay.com/oauth2/authorize',
            'Sandbox' => 'https://auth.sandbox.ebay.com/oauth2/authorize',
            default => $this->logger->error('Invalid environment specified: ' . $this->Environment),
        };
    
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes),
        ];
    
        $url = $authUrl . '?' . http_build_query($params); //Формирую финальную URL для аутентификации.
        dump($url);
    
        return $url; 
    }

    public function getAccessToken(string $code): array //Получает токен доступа, отправляя код авторизации ($code) на eBay.
    {
        $authUrl = match ($this->Environment) { //проверка в какой среде нужно работать и какое URL использовать 
            'Production' => 'https://api.ebay.com/identity/v1/oauth2/token',
            'Sandbox' => 'https://api.sandbox.ebay.com/identity/v1/oauth2/token',
            default => throw new \InvalidArgumentException('Invalid environment specified.'),
        };

        try {

            $response = $this->client->post($authUrl, [ //Отправляем POST-запрос 
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                ],
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri,
                ],
            ]);

            $this->logger->info('Access token successfully refreshed.'); // Логируем успешное обновление токена.
            return json_decode($response->getBody()->getContents(), true); //Получаем и декодируем JSON-ответ, который содержит: access_token, expires_in, refresh_token

        } catch (\Exception $e) {
            $this->logger->error('Error refreshing access token: ' . $e->getMessage());// Логируем ошибку, если что-то пошло не так при обновлении токена.
        }
    }

    public function refreshAccessToken(string $refreshToken): array //Обновляет токен доступа, используя refresh_token
    {
        $authUrl = match ($this->Environment) { //проверка в какой среде нужно работать и какое URL использовать 
            'Production' => 'https://api.ebay.com/identity/v1/oauth2/token',
            'Sandbox' => 'https://api.sandbox.ebay.com/identity/v1/oauth2/token',
            default => throw new \InvalidArgumentException('Invalid environment specified.'),
        };

        try {

            $response = $this->client->post($authUrl, [ //Отправляем запрос на обновление токена
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                ],
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'scope' => 'https://api.ebay.com/oauth/api_scope/sell.inventory',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true); //возвращаем новый токен с обновлённым access_token
            $this->logger->info('Access token successfully refreshed.'); // Логируем успешное обновление токена.

        } catch (\Exception $e) {
            $this->logger->error('Error refreshing access token: ' . $e->getMessage()); // Логируем ошибку, если что-то пошло не так при обновлении токена.
        }
    }
}

