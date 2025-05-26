<?php

namespace App\Tests\Service;

use App\Service\EbayAuthService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EbayAuthServiceTest extends TestCase
{
    private $params;
    private $logger;
    private $client;
    private $service;

     /**
     * Настройка окружения для тестов. Выполняется перед каждым тестовым методом.
     */
    protected function setUp(): void
    {

        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->client = $this->createMock(Client::class);

        $this->params->method('get')->willReturnMap([
            ['ebay_client_id', 'client_id_123'],
            ['ebay_client_secret', 'secret_456'],
            ['ebay_redirect_uri', 'https://redirect.url'],
            ['ebay_scoope', 'scope1 scope2'],
            ['ebay_environment', 'Sandbox'],
            ['ebay_auth_url', 'https://auth.sandbox.ebay.com/oauth2/authorize'],
            ['ebay_token_url', 'https://api.sandbox.ebay.com/identity/v1/oauth2/token'],
        ]);

        $this->service = new EbayAuthService($this->params, $this->logger, $this->client);
    }

    /**
     * Тестирует генерацию URL для авторизации пользователя на eBay.
     */
    public function testGetAuthorizationUrl()
    {
        $url = $this->service->getAuthorizationUrl();

        $this->assertStringStartsWith('https://auth.sandbox.ebay.com/oauth2/authorize?', $url);
        $this->assertStringContainsString('client_id=client_id_123', $url);
        $this->assertStringContainsString('redirect_uri=https%3A%2F%2Fredirect.url', $url);
        $this->assertStringContainsString('scope=scope1+scope2', $url);
        $this->assertStringContainsString('response_type=code', $url);
    }

    /**
     * Тестирует успешное получение access token.
     */
    public function testGetAccessTokenSuccess()
    {
        $responseBody = json_encode(['access_token' => 'abc123']);
        $response = new Response(200, [], $responseBody);

        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Access token successfully obtained.');

        $result = $this->service->getAccessToken('code123');

        $this->assertIsArray($result);
        $this->assertEquals('abc123', $result['access_token']);
    }

    /**
     * Тестирует неудачное получение access token (например, из-за сетевой ошибки).
     */
    public function testGetAccessTokenFailure()
    {
        $this->client->expects($this->once())
            ->method('post')
            ->willThrowException(new \Exception('Network error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error getting access token'));

        $result = $this->service->getAccessToken('code123');

        $this->assertNull($result);
    }

    /**
     * Тестирует успешное обновление access token.
     */
    public function testRefreshAccessTokenSuccess()
    {
        $responseBody = json_encode(['access_token' => 'refreshed_token_456']);
        $response = new Response(200, [], $responseBody);

        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Access token refreshed successfully.');

        $result = $this->service->refreshAccessToken('refresh_token_abc');

        $this->assertIsArray($result);
        $this->assertEquals('refreshed_token_456', $result['access_token']);
    }

    /**
     * Тестирует неудачное обновление access token.
     */
    public function testRefreshAccessTokenFailure()
    {
        $this->client->expects($this->once())
            ->method('post')
            ->willThrowException(new \Exception('Refresh failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error updating access token'));

        $result = $this->service->refreshAccessToken('refresh_token_123');

        $this->assertNull($result);
    }
}
