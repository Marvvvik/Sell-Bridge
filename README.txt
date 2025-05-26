Система для работы с eBay API

Описание:

Данная система предназначена для работы с eBay API. В ней можно получить Access Token и Refresh Token, а также система автоматически обновляет Access Token через Refresh Token.
Функциональность системы включает:

  1. Добавление товаров в базу данных(В таблицу ebay_inventory_items которая которая думана для создания карточки товара);

  2. Просмотр и удаление товаров;

  3. Добавление товаров в eBay Inventory;

  4. Просмотр всех товаров и конкретного товара в eBay Inventory, а также их удаление;

  5. Создание offer (предложений), просмотр всех или конкретных offer, а также их удаление;

  6. Публикация offer. 

  7. Можно контролировать выставленые товары( все ответы записываются в ebay_listings) 

Система взаимодействует через отправку HTTP-запросов, например, с помощью Postman.

Пути и их назночение: 

1. /ebay/in ventory/add — Добавление товара в базу данных
Метод: POST

Body (пример):

{
  "sku": "TEST-SKU",
  "locale": "en_US",
  "product": {
    "title": "TEST-TITLE",
    "aspects": {
      "Size": ["TEST-SIZE"],
      "Color": ["TEST-COLOR"],
      "Material": ["TEST-MATERIAL"]
    },
    "description": "TEST-DESCRIPTION",
    "brand": "TEST-BRAND",
    "mpn": "TEST-MPN",
    "imageUrls": [
      "TEST-IMAGE-URL"
    ]
  },
  "condition": "NEW",
  "price": {
    "value": 89.99,
    "currency": "USD"
  },
  "quantity": 250,
  "availability_quantity": 50,
  "marketplaceId": "EBAY_US",
  "format": "FIXED_PRICE"
}

2. /ebay/inventory — Получение всех товаров из базы
Метод: GET

3. /ebay/inventory/{sku} — Получение конкретного товара по SKU
Метод: GET

4. /ebay/inventoryItem/add/{sku} — Добавление товара в eBay Inventory по SKU
Метод: PUT

5. /ebay/inventoryItem/items — Получение всех товаров из eBay Inventory
Метод: GET

6. /ebay/inventoryItem/item/{sku} — Получение товара из eBay Inventory по SKU
Метод: GET

7. /ebay/inventory/delete/{sku} — Удаление товара из eBay Inventory по SKU
Метод: DELETE

8. /ebay/offer/add/{sku} — Создание offer по SKU
Метод: POST

9. /ebay/offers/item/{sku} — Получение offer по SKU
Метод: GET

10. /ebay/offer/item/{offerId} — Получение offer по offerId
Метод: GET

11. /ebay/offer/delete/{offerId} — Удаление offer по offerId
Метод: DELETE

12. /ebay/offer/publish/{offerId} — Публикация offer по offerId
Метод: POST

13. /ebay/listings - вывод ebayListing для контроля товаров 
Метод: GET

но так же через этот URL можно фильтровать вывот к URL добавляем
"?" и параметр по которому хотим отфильтровать "sku=TEST1"

к примеру: /ebay/listings?sku=TEST1

фильтровать можно по таким параметрам как: 

1. "id" - id предмета в ebay_inventory_items 
2. "sku" - sku предмета 
3. "offerId" - offer id которный предмет получил при создании
4. "status" - статус в котором сейчас находится предме

и по пути config/routes/ebay.yaml в этом файле собраны все маршруты

Команда для тестирования: php bin/console app:ebay:create-test-listing

Эта команда:

  1. создаст тестовый товар в базе данных,

  2. добавит его в eBay Inventory,

  3. создаст offer на основе этого товара,

  4. опубликует его.

Инструкция по запуску проекта через DDEV:

✅ Требования:

Установленный Docker

Установленный DDEV PHP 

Минимальная версия PHP: 8.2

1. Клонируйте репозиторий:

git clone https://github.com/Marvvvik/Sell-Bridge.git

2. Откройте PowerShell или CMD и выполните:

прописать: cd your-project-name

3. Запустите DDEV:

прописать: ddev start

4. Установите зависимости:

прописать: ddev composer install

5. Запустите проект: 

прописать: ddev launch

это была инструкция по запуску проекта через ddev можно использовать так же другие локальные сервера, усианова тогда будет зависетиь от того какой сервис вы используете 


дальнейщие шаги настройки уже относятся к самому проекту:

1. Откройте файл .env и укажите все необходимые параметры:

   указать такие параметры как: 
   
   1. EBAY_CLIENT_ID
   2. EBAY_CLIENT_SECRET
   3. EBAY_REDIRECT_URI
   4. DATABASE_URL
   
   по надобности:
   
   5. EBAY_SCOPES
   6. EBAY_ENVIRONMENT

2. Выполнить миграцию 

Если используете DDEV:

    2.1 откроте PowerSheel или CMD 

    2.2 окроете проект: cd your-project-name

    2.3 Прописать: ddev ssh

    2.4 создать миграцию: php bin/console doctrine:migrations:diff

    2.5 выплонить минрацию: php bin/console doctrine:migrations:migrate

в друних сервисах выполнение миграции может отличатся 

3. Перейдите по адресу /ebay/auth, авторизуйтесь и получите токены для работы системы.

4. Откройте файл src/Service/EbayOffersService.php и в методе buildOfferData() при необходимости настройте параметры создания offer.

5. система готова к работе 