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

    #[Route('/ebay/auth', name: 'ebay_auth')] //создаю маршрут (Так же через этот путь можно обновить токен, а точнее просто запишется новый)
    public function auth(EbayAuthService $ebayAuthService): RedirectResponse
    {
        $authorizationUrl = $ebayAuthService->getAuthorizationUrl(); // Вызывает getAuthorizationUrl() из EbayAuthService для получения ссылки на вход.
        return new RedirectResponse($authorizationUrl); //перенаправляем пользователя 
    }

    #[Route('/ebay/callback', name: 'ebay_callback')] //создаю маршрут 
    public function callback(Request $request, EbayAuthService $ebayAuthService, TokenService $tokenService): JsonResponse
    {
        $code = $request->query->get('code'); //получаю code из запроса (GET параметр).

        if (!$code) { // проверка если code нет – возвращает ошибку 400 Bad Request.
            return new JsonResponse(['error' => 'Code not provided'], 400);
        }

        $tokens = $ebayAuthService->getAccessToken($code); // Вызывает getAccessToken() из EbayAuthService для получения токена.
        $tokenService->saveTokens($tokens); //сохраняем токен в базу данных 
        return new JsonResponse($tokens); //перенаправляем пользователя 
    }

    #[Route('/ebay/refresh', name: 'ebay_refresh')] //создаю маршрут 
    public function refresh(Request $request, EbayAuthService $ebayAuthService, TokenService $tokenService): JsonResponse
    {
        $refreshToken = $request->query->get('refresh_token');// Получаю refresh_token из запроса (GET параметр).

        if (!$refreshToken) { //Если токена нет – возвращает ошибку 400 Bad Request
            return new JsonResponse(['error' => 'Refresh token not provided'], 400);
        }

        $tokens = $ebayAuthService->refreshAccessToken($refreshToken);// Запрашиваем новый access_token у eBay через refreshAccessToken($refreshToken)
        $tokenService->updateTokens($refreshToken, $tokens); // Обновляем сохранённые токены
        return new JsonResponse($tokens); //перенаправляем пользователя 

    }

}