<?php

namespace App\Service;

use App\Entity\Token;
use App\Repository\TokenRepository;
use App\Service\EbayAuthService; 

class TokenService
{
    private TokenRepository $tokenRepository;
    private EbayAuthService $ebayAuthService; 

    public function __construct(
        TokenRepository $tokenRepository,
        EbayAuthService $ebayAuthService
    )
    {
        $this->tokenRepository = $tokenRepository;
        $this->ebayAuthService = $ebayAuthService; 
    }

    /**
     * метод для молучания access tokena и refresh token 
     * Если токен уже существует, он обновляется.
     *
     * @param array $tokens Массив с access_token и refresh_token.
     * @return void
     */
    public function saveTokens(array $tokens): void 
    {
        $existingToken = $this->tokenRepository->findOneBy([]);
    
        // проверка есть ли токен в базы данных и если есть просто обновляем его 
        if ($existingToken) {
            $existingToken->setAccessToken($tokens['access_token']);
            $existingToken->setRefreshToken($tokens['refresh_token']);
            $existingToken->setUpdatedAt(new \DateTimeImmutable());
            $tokenEntity = $existingToken;
        } else {
            //если нету то записываем новый 
            $tokenEntity = new Token();
            $tokenEntity->setAccessToken($tokens['access_token']);
            $tokenEntity->setRefreshToken($tokens['refresh_token']);
            $tokenEntity->setExpiresIn($tokens['expires_in']);
            $tokenEntity->setRefreshTokenExpiresIn($tokens['refresh_token_expires_in']);
            $tokenEntity->setCreatedAt(new \DateTimeImmutable());
            $tokenEntity->setUpdatedAt(new \DateTimeImmutable());
        }
    
        // сохраняем 
        $this->tokenRepository->save($tokenEntity, true);
    }

    /**
     * метод получения валидного токена 
     *
     * @return string Валидный access token.
     * @throws \Exception Если токен отсутствует в базе данных или не удалось обновить токен.
     */
    public function getValidAccessToken(): string
    {
        $tokenEntity = $this->tokenRepository->findOneBy([]);

        if (!$tokenEntity) {
            throw new \Exception('The token is missing from the database.');
        }

        // проверка валидности токена 
        if ($this->isAccessTokenValid($tokenEntity)) {

            // если токен рабочий передаем его 
            return $tokenEntity->getAccessToken();

        // если нет то обновляем и записываем новый
        } else {
            $newTokens = $this->ebayAuthService->refreshAccessToken($tokenEntity->getRefreshToken());

            if (!isset($newTokens['access_token'])) {
                throw new \Exception('Failed to update the token.');
            }

            $this->updateTokens($newTokens);

            return $newTokens['access_token'];
        }
    }

    /**
     * метод для полверки токена
     *
     * @param Token $tokenEntity Объект токена.
     * @return bool True, если токен действителен (с учетом 5-минутного запаса), false в противном случае.
     */
    private function isAccessTokenValid(Token $tokenEntity): bool
    {
        // Получаем текущую дату и время
        $now = new \DateTimeImmutable();

        // Получаем время, когда токен был обновлен, и добавляем к этому времени значение срока действия токена
        $expiresAt = $tokenEntity->getUpdatedAt()->modify('+' . $tokenEntity->getExpiresIn() . ' seconds');
        
        // Проверяем, что текущее время меньше, чем время истечения срока действия токена 
        // минус 5 минут.(но предполонаю что это не самый удачный способ реальзации)
        return $now < $expiresAt->modify('-5 minutes');
    }

    /**
     * метод записи токена после обновления 
     *
     * @param array $tokens Массив с обновленными access_token и refresh_token.
     * @return void
     */
    public function updateTokens(array $tokens): void
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
