<?php

use Bitrix\Main\Context\Culture;
use Bitrix\Sale\Basket;

IncludeModuleLangFile(__FILE__);


/**
 * Class RetailCrmCart
 */
class RetailCrmCart
{
    private static string $dateFormat = "Y-m-d H:i:sP";

    public static function prepareCart(array $arBasket): void
    {
        $api = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
        $optionsSitesList = RetailcrmConfigProvider::getSitesList();

        if ($optionsSitesList) {
            if (array_key_exists($arBasket['LID'], $optionsSitesList) && $optionsSitesList[$arBasket['LID']] !== null) {
                $site = $optionsSitesList[$arBasket['LID']];

                $api->setSite($site);
            } else {
                RCrmActions::eventLog(
                    'RetailCrmCart::prepareCart',
                    'RetailcrmConfigProvider::getSitesList',
                    'Error set site'
                );

                return;
            }
        } else {
            $site = RetailcrmConfigProvider::getSitesAvailable();
        }

        $crmBasket = RCrmActions::apiMethod($api, 'cartGet', __METHOD__, $arBasket['USER_ID'], $site);

        if (empty($arBasket['BASKET'])) {
            if (!empty($crmBasket['cart']['items'])) {
                RCrmActions::apiMethod(
                    $api,
                    'cartClear',
                    __METHOD__,
                    [
                        'clearedAt' => date(self::$dateFormat),
                        'customer' => [
                            'externalId' => $arBasket['USER_ID']
                        ]
                    ],
                    $site
                );

                return;
            }

            return;
        }

        $date = 'createdAt';
        $items = [];

        foreach ($arBasket['BASKET'] as $itemBitrix) {
            $item['quantity'] = $itemBitrix['QUANTITY'];
            $item['price'] =  $itemBitrix['PRICE'];
            $item['createdAt'] = $itemBitrix['DATE_INSERT']->format(self::$dateFormat);
            $item['updateAt'] = $itemBitrix['DATE_UPDATE']->format(self::$dateFormat);
            $item['offer']['externalId'] = $itemBitrix['PRODUCT_ID'];
            $items[] = $item;
        }

        if (!empty($crmBasket['cart']['items'])) {
            $date = 'updatedAt';
        }

        RCrmActions::apiMethod(
            $api,
            'cartSet',
            __METHOD__,
            [
                'customer' => [
                    'externalId' => $arBasket['USER_ID'],
                    'site' => $site,
                    $date => date(self::$dateFormat),
                ],
                'items' => $items,
            ],
            $site
        );
    }

    /**
     * @throws \Bitrix\Main\SystemException
     *
     * @return array|null
     */
    public static function getBasketArray($event): ?array
    {
        if ($event instanceof Basket) {
            $obBasket = $event;
        } elseif ($event instanceof Event) {
            $obBasket = $event->getParameter('ENTITY');
        } else {
            RCrmActions::eventLog('RetailCrmEvent::onChangeBasket', 'getBasketArray', 'event error');

            return null;
        }

        $arBasket = [
            'LID' => $obBasket->getSiteId(),
        ];

        $items = $obBasket->getBasket();

        foreach ($items as $item) {
            $arBasket['BASKET'][] = $item->getFields();
        }

        return $arBasket;
    }
}
