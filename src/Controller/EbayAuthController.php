<?php

namespace App\Controller;

use App\Service\EbayAuthService;
use App\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;


class EbayAuthController extends AbstractController
{
    /**
     * создаю маршрут (Так же через этот путь можно обновить токен, а точнее просто запишется новый)
     */
    #[Route('/ebay/auth', name: 'ebay_auth')] 
    public function auth(EbayAuthService $ebayAuthService): RedirectResponse
    {
        // Вызывает getAuthorizationUrl() из EbayAuthService для получения ссылки на вход.
        $authorizationUrl = $ebayAuthService->getAuthorizationUrl();

        //перенаправляем пользователя 
        return new RedirectResponse($authorizationUrl);
    }

    /**
     * создаю маршрут
     */
    #[Route('/ebay/callback', name: 'ebay_callback')]
    public function callback(Request $request, EbayAuthService $ebayAuthService, TokenService $tokenService): JsonResponse
    {
        //получаю code из запроса (GET параметр).
        $code = $request->query->get('code');

        // проверка если code нет – возвращает ошибку 400 Bad Request.
        if (!$code) { 
            return new JsonResponse(['error' => 'Code not provided'], 400);
        }

        // Вызывает getAccessToken() из EbayAuthService для получения токена.
        $tokens = $ebayAuthService->getAccessToken($code);

        //сохраняем токен в базу данных 
        $tokenService->saveTokens($tokens); 

        //перенаправляем пользователя 
        return new JsonResponse($tokens); 
    }

    /**
     * создаю маршрут
     */
    #[Route('/ebay/refresh', name: 'ebay_refresh')]
    public function refresh(Request $request, EbayAuthService $ebayAuthService, TokenService $tokenService): JsonResponse
    {
        // Получаю refresh_token из запроса (GET параметр).
        $refreshToken = $request->query->get('refresh_token');

        //Если токена нет – возвращает ошибку 400 Bad Request
        if (!$refreshToken) { 
            return new JsonResponse(['error' => 'Refresh token not provided'], 400);
        }

        // Запрашиваем новый access_token у eBay через refreshAccessToken($refreshToken)
        $tokens = $ebayAuthService->refreshAccessToken($refreshToken);

        // Обновляем сохранённые токены
        $tokenService->updateTokens($refreshToken, $tokens); 
        
        //перенаправляем пользователя 
        return new JsonResponse($tokens); 

    }

}