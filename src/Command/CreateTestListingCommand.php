<?php

namespace App\Command;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateTestListingCommand extends Command
{
    private $client;
    private const INVENTORY_ADD_URL = 'https://sell-bridge.ddev.site/ebay/inventory/add';
    private const INVENTORY_ITEM_ADD_URL = 'https://sell-bridge.ddev.site/ebay/inventoryItem/add/';
    private const OFFER_ADD_URL = 'https://sell-bridge.ddev.site/ebay/offer/add/';
    private const OFFER_PUBLISH_URL = 'https://sell-bridge.ddev.site/ebay/offer/publish/';

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
    }

    protected function configure()
    {
        $this->setName('app:ebay:create-test-listing')
            ->setDescription('Creates a test listing on eBay via the API.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Шаг 1: Отправка POST запроса для создания предмета в базе данных
            $response = $this->client->post(self::INVENTORY_ADD_URL, [
                'json' => [
                    'sku' => 'TEST-MIKROTIK-HAP-AX2-001',
                    'locale' => 'en_US',
                    'product' => [
                        'title' => 'MikroTik hAP ax² Dual Band WiFi 6 Router',
                        'aspects' => [
                            'Size' => ['Standard'],
                            'Color' => ['Black'],
                            'Material' => ['Plastic']
                        ],
                        'description' => 'The MikroTik hAP ax² is a powerful dual-band WiFi 6 router...',
                        'brand' => 'MikroTik',
                        'mpn' => 'HAP-AX2-001',
                        'imageUrls' => [
                            'https://www.dateks.lv/images/pic/2400/2400/402/1266.jpg'
                        ]
                    ],
                    'condition' => 'NEW',
                    'price' => [
                        'value' => 89.99,
                        'currency' => 'USD'
                    ],
                    'quantity' => 250,
                    'availability_quantity' => 50,
                    'marketplaceId' => 'EBAY_US',
                    'format' => 'FIXED_PRICE'
                ]
            ]);

            $output->writeln('The product has been successfully created in the database.');

            // Шаг 2: Отправка PUT запроса для добавления предмета в eBay inventory
            $inventoryItemResponse = $this->client->put(self::INVENTORY_ITEM_ADD_URL . 'TEST-MIKROTIK-HAP-AX2-001');
            $inventoryItemData = json_decode($inventoryItemResponse->getBody(), true);

            if ($inventoryItemResponse->getStatusCode() !== 200) {
                throw new \Exception('Error while adding the product to the inventory.');
            }

            $output->writeln('The product has been successfully added to the eBay inventory.');

            // Шаг 3: Отправка POST запроса для создания offer
            $offerResponse = $this->client->post(self::OFFER_ADD_URL . 'TEST-MIKROTIK-HAP-AX2-001');
            $offerData = json_decode($offerResponse->getBody(), true);

            if ($offerResponse->getStatusCode() !== 200 || empty($offerData['data']['offerId'])) {
                throw new \Exception('Error while creating the offer.');
            }

            $offerId = $offerData['data']['offerId'];
            $output->writeln("The product has been successfully added to eBay offers. offerID: $offerId");

            // Шаг 4: Отправка POST запроса для публикации offer
            $publishResponse = $this->client->post(self::OFFER_PUBLISH_URL . $offerId);

            if ($publishResponse->getStatusCode() !== 200) {
                throw new \Exception('Error while publishing the offer.');
            }

            $output->writeln('The offer has been successfully published on eBay.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('Ошибка: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
