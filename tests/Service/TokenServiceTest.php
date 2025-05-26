<?php

namespace App\Tests\Service;

use App\Entity\Token;
use App\Repository\TokenRepository;
use App\Service\EbayAuthService;
use App\Service\TokenService;
use PHPUnit\Framework\TestCase;

class TokenServiceTest extends TestCase
{
    private $tokenRepository;
    private $ebayAuthService;
    private $service;

    /**
     * Настройка окружения для тестов. Выполняется перед каждым тестовым методом.
     */
    protected function setUp(): void
    {
        $this->tokenRepository = $this->createMock(TokenRepository::class);
        $this->ebayAuthService = $this->createMock(EbayAuthService::class);
        $this->service = new TokenService($this->tokenRepository, $this->ebayAuthService);
    }

    /**
     * Тестирует сохранение новых токенов, когда в базе данных еще нет ни одного токена.
     * Ожидается создание нового объекта Token и его сохранение.
     */
    public function testSaveTokensNewToken(): void
    {
        $tokens = [
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in' => 3600,
            'refresh_token_expires_in' => 7200,
        ];

        $this->tokenRepository->expects($this->once())
            ->method('findOneBy')
            ->with([])
            ->willReturn(null);

        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Token::class), true);

        $this->service->saveTokens($tokens);
    }

    /**
     * Тестирует сохранение обновленных токенов, когда в базе данных уже есть токен.
     * Ожидается обновление существующего объекта Token и его сохранение.
     */
    public function testSaveTokensExistingToken(): void
    {
        $tokens = [
            'access_token' => 'updated_access_token',
            'refresh_token' => 'updated_refresh_token',
            'expires_in' => 3600,
            'refresh_token_expires_in' => 7200,
        ];

        $existingToken = new Token();
        $this->tokenRepository->expects($this->once())
            ->method('findOneBy')
            ->with([])
            ->willReturn($existingToken);

        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($existingToken, true);

        $this->service->saveTokens($tokens);
        $this->assertEquals('updated_access_token', $existingToken->getAccessToken());
        $this->assertEquals('updated_refresh_token', $existingToken->getRefreshToken());
    }

    /**
     * Тестирует получение валидного access token, когда существующий токен еще не истек.
     * Ожидается возврат существующего access token.
     */
    public function testGetValidAccessTokenExistingValid(): void
    {
        $existingToken = new Token();
        $existingToken->setAccessToken('valid_access_token')
            ->setUpdatedAt(new \DateTimeImmutable('-10 minutes'))
            ->setExpiresIn(3600);

        $this->tokenRepository->expects($this->once())
            ->method('findOneBy')
            ->with([])
            ->willReturn($existingToken);

        $this->assertEquals('valid_access_token', $this->service->getValidAccessToken());
    }

    /**
     * Тестирует получение валидного access token, когда существующий токен истек и требуется его обновление.
     * Ожидается вызов метода обновления токена у EbayAuthService и сохранение новых токенов.
     */
    public function testGetValidAccessTokenNeedsRefreshSuccess(): void
    {
        $existingToken = new Token();
        $existingToken->setAccessToken('expired_access_token')
            ->setRefreshToken('test_refresh_token')
            ->setUpdatedAt(new \DateTimeImmutable('-60 minutes'))
            ->setExpiresIn(3600);

        $this->tokenRepository->expects($this->exactly(2)) 
            ->method('findOneBy')
            ->with([])
            ->willReturn($existingToken);

        $newTokens = [
            'access_token' => 'new_valid_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in' => 3600,
        ];

        $this->ebayAuthService->expects($this->once())
            ->method('refreshAccessToken')
            ->with('test_refresh_token')
            ->willReturn($newTokens);

        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($existingToken, true);

        $this->assertEquals('new_valid_access_token', $this->service->getValidAccessToken());
        $this->assertEquals('new_valid_access_token', $existingToken->getAccessToken());
        $this->assertEquals('new_refresh_token', $existingToken->getRefreshToken());
        $this->assertEquals(3600, $existingToken->getExpiresIn());
    }

    /**
     * Тестирует ситуацию, когда обновление токена завершается неудачей.
     * Ожидается выброс исключения.
     */
    public function testGetValidAccessTokenNeedsRefreshFailure(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to update the token.');

        $existingToken = new Token();
        $existingToken->setAccessToken('expired_access_token')
            ->setRefreshToken('test_refresh_token')
            ->setUpdatedAt(new \DateTimeImmutable('-60 minutes'))
            ->setExpiresIn(3600);

        $this->tokenRepository->expects($this->once())
            ->method('findOneBy')
            ->with([])
            ->willReturn($existingToken);

        $this->ebayAuthService->expects($this->once())
            ->method('refreshAccessToken')
            ->with('test_refresh_token')
            ->willReturn([]);

        $this->service->getValidAccessToken();
    }

    /**
     * Тестирует ситуацию, когда токен отсутствует в базе данных.
     * Ожидается выброс исключения.
     */
    public function testGetValidAccessTokenMissingToken(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The token is missing from the database.');

        $this->tokenRepository->expects($this->once())
            ->method('findOneBy')
            ->with([])
            ->willReturn(null);

        $this->service->getValidAccessToken();
    }

    /**
     * Тестирует обновление токенов с передачей массива новых токенов.
     */
    public function testUpdateTokens(): void
    {
        $existingToken = new Token();
        $existingToken->setRefreshToken('old_refresh_token')
            ->setRefreshTokenExpiresIn(10000);

        $this->tokenRepository = $this->createMock(TokenRepository::class); 
        $this->tokenRepository->expects($this->once())
            ->method('findOneBy')
            ->with([])
            ->willReturn($existingToken);
        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($existingToken, true);
        $this->service = new TokenService($this->tokenRepository, $this->ebayAuthService); 

        $newTokens = [
            'access_token' => 'updated_access',
            'refresh_token' => 'new_refresh',
            'expires_in' => 1200,
            'refresh_token_expires_in' => 2400,
        ];
        $this->service->updateTokens($newTokens);

        $this->assertEquals('updated_access', $existingToken->getAccessToken());
        $this->assertEquals('new_refresh', $existingToken->getRefreshToken());
        $this->assertEquals(1200, $existingToken->getExpiresIn());
        $this->assertEquals(2400, $existingToken->getRefreshTokenExpiresIn());

        $existingToken2 = new Token();
        $existingToken2->setRefreshToken('old_refresh_token')
            ->setRefreshTokenExpiresIn(10000)
            ->setAccessToken('another_updated_access');

        $this->tokenRepository = $this->createMock(TokenRepository::class); 
        $this->tokenRepository->expects($this->once())
            ->method('findOneBy')
            ->with([])
            ->willReturn($existingToken2);
        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($existingToken2, true);
        $this->service = new TokenService($this->tokenRepository, $this->ebayAuthService); 

        $tokensWithoutRefresh = [
            'access_token' => 'another_updated_access',
            'expires_in' => 1800,
        ];
        $this->service->updateTokens($tokensWithoutRefresh);
        $this->assertEquals('another_updated_access', $existingToken2->getAccessToken());
        $this->assertEquals('old_refresh_token', $existingToken2->getRefreshToken()); 
        $this->assertEquals(1800, $existingToken2->getExpiresIn());
        $this->assertEquals(10000, $existingToken2->getRefreshTokenExpiresIn()); 
    }
}