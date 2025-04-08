<?php

namespace App\Service;

use App\Entity\Token;
use App\Repository\TokenRepository;
use App\Service\EbayAuthService; 

class TokenService
{
    private TokenRepository $tokenRepository;
    private EbayAuthService $ebayAuthService; 

    public function __construct(TokenRepository $tokenRepository, EbayAuthService $ebayAuthService)
    {
        $this->tokenRepository = $tokenRepository;
        $this->ebayAuthService = $ebayAuthService; 
    }

    public function saveTokens(array $tokens): void // метод для молучания access tokena и refresh token 
    {
        $existingToken = $this->tokenRepository->findOneBy([]);
    
        if ($existingToken) { // проверка есть ли токен в базы данных и если есть просто обновляем его 
            $existingToken->setAccessToken($tokens['access_token']);
            $existingToken->setRefreshToken($tokens['refresh_token']);
            $existingToken->setUpdatedAt(new \DateTimeImmutable());
            $tokenEntity = $existingToken;
        } else {
            $tokenEntity = new Token(); //если нету то записываем новый 
            $tokenEntity->setAccessToken($tokens['access_token']);
            $tokenEntity->setRefreshToken($tokens['refresh_token']);
            $tokenEntity->setExpiresIn($tokens['expires_in']);
            $tokenEntity->setRefreshTokenExpiresIn($tokens['refresh_token_expires_in']);
            $tokenEntity->setCreatedAt(new \DateTimeImmutable());
            $tokenEntity->setUpdatedAt(new \DateTimeImmutable());
        }
    
        $this->tokenRepository->save($tokenEntity, true); // сохраняем 
    }

    public function getValidAccessToken(): string // метод получения валидного токена 
    {
        $tokenEntity = $this->tokenRepository->findOneBy([]);

        if (!$tokenEntity) {
            throw new \Exception('The token is missing from the database.');
        }

        if ($this->isAccessTokenValid($tokenEntity)) { // проверка валидности токена 
            return $tokenEntity->getAccessToken(); // если токен рабочий передаем его 
        } else { // если нет то обновляем и записываем новый 
            $newTokens = $this->ebayAuthService->refreshAccessToken($tokenEntity->getRefreshToken());

            if (!isset($newTokens['access_token'])) {
                throw new \Exception('Failed to update the token.');
            }

            $this->updateTokens($newTokens);

            return $newTokens['access_token'];
        }
    }

    private function isAccessTokenValid(Token $tokenEntity): bool // метод для полверки токена
    {
        $now = new \DateTimeImmutable(); // Получаем текущую дату и время
        $expiresAt = $tokenEntity->getUpdatedAt()->modify('+' . $tokenEntity->getExpiresIn() . ' seconds'); // Получаем время, когда токен был обновлен, и добавляем к этому времени значение срока действия токена
        
        return $now < $expiresAt->modify('-5 minutes');// Проверяем, что текущее время меньше, чем время истечения срока действия токена минус 5 минут.(но предполонаю что это не самый удачный способ реальзации)
    }

    public function updateTokens(array $tokens): void // метод записи токена после обновления 
    {
        $tokenEntity = $this->tokenRepository->findOneBy([]);

        $tokenEntity->setAccessToken($tokens['access_token']);
        $tokenEntity->setRefreshToken($tokens['refresh_token'] ?? $tokenEntity->getRefreshToken());
        $tokenEntity->setExpiresIn($tokens['expires_in']);
        $tokenEntity->setRefreshTokenExpiresIn($tokens['refresh_token_expires_in'] ?? $tokenEntity->getRefreshTokenExpiresIn());
        $tokenEntity->setUpdatedAt(new \DateTimeImmutable());

        $this->tokenRepository->save($tokenEntity, true);
    }
}
