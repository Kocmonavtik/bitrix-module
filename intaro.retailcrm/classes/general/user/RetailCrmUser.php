<?php

/**
 * @category RetailCRM
 * @package  RetailCRM\User
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

IncludeModuleLangFile(__FILE__);

use Bitrix\Main\UserTable;
use Bitrix\Main\UserFieldTable;
use Bitrix\Main\UserFieldLangTable;

/**
 * Class RetailCrmUser
 *
 * @category RetailCRM
 * @package RetailCRM\User
 */
class RetailCrmUser
{

    /**
     * @param array $arFields
     * @param       $api
     * @param       $contragentType
     * @param false $send
     * @param null  $site
     *
     * @return array|false
     * @throws \Exception
     */
    public static function customerSend(array $arFields, $api, $contragentType, bool $send = false, $site = null)
    {
        if (!$api || empty($contragentType)) {
            return false;
        }

        if (empty($arFields)) {
            RCrmActions::eventLog('RetailCrmUser::customerSend', 'empty($arFields)', 'incorrect customer');

            return false;
        }


       /* $userFields = UserFieldTable::getList([
            'filter' => ['ENTITY_ID' => 'USER'], // Фильтруем только по сущности "USER"
            'select' => ['FIELD_NAME']
        ]);*/

        //$result = $api->customDictionariesEdit(['code' => 'partner_list','name' => 'partners', 'elements' => [['name'=> 'test3', 'code' => 'test3']]]);
        //$result = $api->customDictionariesGet('test1111');
        //$test = RCrmActions::apiMethod($api, 'customFieldsCreate', __METHOD__, ['test', 'test']);
        //$test = $api->customFieldsList(['code' => 'adress']);
        //$test = RCrmActions::apiMethod($api, 'customFieldsList', __METHOD__, $customer, $site)
        //$newResCustomer = retailCrmBeforeCustomerSend([]);

        //$ar = [];

        //while ($field = $userFields->fetch()) {
        //    $ar[] = $field['FIELD_NAME'];
        //}
        $customer = self::getSimpleCustomer($arFields);
        $customer['createdAt'] = new \DateTime($arFields['DATE_REGISTER']);
        $customer['contragent'] = ['contragentType' => $contragentType];

        if (RetailcrmConfigProvider::getCustomFieldsStatus() === 'Y') {
            $customer['customFields'] = self::getCustomFields($arFields);
        }

        if ($send && isset($_COOKIE['_rc']) && $_COOKIE['_rc'] != '') {
            $customer['browserId'] = $_COOKIE['_rc'];
        }

        if (function_exists('retailCrmBeforeCustomerSend')) {

            //Следующий код для функции:
            //Получаем сохраненный список полей. В случае отсутствия - создаем. Код модуля и константа от разработчика (текущие под вопросом)
            //Пример массива $options = ['uf_test' => ['test1', 'test2', 'test3']], где ключ - это код справочника и кастомного поля. Значения - код справочников
            $savedCustomEnumFields = unserialize(COption::GetOptionString('intaro.retailcrm', 'saved_custom_enum_fields', 0), []);

            if ($savedCustomEnumFields === []) {
                COption::SetOptionString('intaro.retailcrm', 'saved_custom_enum_fields', serialize([]));
            }

            // Получаем все кастомные поля объекта USER и его переводы
            $userFields = UserFieldTable::getList(array (
                'select'   =>   array ('ID', 'FIELD_NAME', 'ENTITY_ID', 'USER_TYPE_ID', 'MULTIPLE', 'SETTINGS', 'TITLE'),
                'filter'   =>   array (
                    '=ENTITY_ID'                     =>   'USER',
                    '=MAIN_USER_FIELD_TITLE_LANGUAGE_ID'   =>   'ru', 'USER_TYPE_ID' => 'enumeration',
                ),
                'runtime'   =>   array (
                    'TITLE'         =>   array (
                        'data_type'      =>   UserFieldLangTable::getEntity(),
                        'reference'      =>   array (
                            '=this.ID'      =>   'ref.USER_FIELD_ID',
                        ),
                    ),
                ),
            ))->fetchAll();
            /*$userFields = UserFieldTable::getList([
                'filter' => ['ENTITY_ID' => 'USER', 'USER_TYPE_ID' => 'enumeration'], // Фильтруем только по сущности "USER" и получаем только списки
            ])->fetchAll();*/
            /*array(1) {
                [0]=> array(13) {
                ["ID"]=> string(2) "30"
                ["FIELD_NAME"]=> string(10) "UF_TEST112"
                ["ENTITY_ID"]=> string(4) "USER"
                ["USER_TYPE_ID"]=> string(11) "enumeration"
                ["MULTIPLE"]=>string(1) "Y"
                ["SETTINGS"]=>array(4) {
                    ["DISPLAY"]=> string(4) "LIST"
                    ["LIST_HEIGHT"]=>int(3)
                    ["CAPTION_NO_VALUE"]=>string(0) ""
                    ["SHOW_NO_VALUE"]=string(1) "Y"
                },
                ["MAIN_USER_FIELD_TITLE_USER_FIELD_ID"]=>string(2) "30"
                ["MAIN_USER_FIELD_TITLE_LANGUAGE_ID"]=>string(2) "ru"
                ["MAIN_USER_FIELD_TITLE_EDIT_FORM_LABEL"]=>string(4) "test" --берем вот это
                ["MAIN_USER_FIELD_TITLE_LIST_COLUMN_LABEL"]=>string(4) "test"
                ["MAIN_USER_FIELD_TITLE_LIST_FILTER_LABEL"]=>string(4) "test"
                ["MAIN_USER_FIELD_TITLE_ERROR_MESSAGE"]=>string(4) "test"
                ["MAIN_USER_FIELD_TITLE_HELP_MESSAGE"]=>string(4) "test"
            }}*/

            $listCustomValues = [];
            $enumerationList = [];
            $enumBuilder = new CUserFieldEnum();

            //сборка всех необходимых параметров
            foreach ($userFields as $userField) {
                if (!isset($userField['FIELD_NAME'])) {
                    continue;
                }

                if (isset($arFields[$userField['FIELD_NAME']]) && $arFields[$userField['FIELD_NAME']] !== false) {
                    $arEnum = $enumBuilder->GetList([], ['USER_FIELD_NAME' => $userField['FIELD_NAME']]);
                    $enumItems = [];

                    //Получение значения выбранного элемента в списке
                    while ($enumElement = $arEnum->Fetch()) {
                        if (in_array($enumElement['ID'], $arFields[$userField['FIELD_NAME']], true)) {


                            //временная конструкция
                            $code = function () use ($enumElement['VALUE']) {
                                $translit = "Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Lower();";
                                $string = transliterator_transliterate($translit, $enumElement['VALUE']);
                                $string = preg_replace('/[-\s]+/', '_', $string);
                                return trim($string, '-');
                            };

                            $enumItems[$code()] = $enumElement['VALUE'];
                        }
                    }

                    $listCustomValues[$userField['FIELD_NAME']] = [
                        'items' => $enumItems,
                        'name' => $userField['MAIN_USER_FIELD_TITLE_EDIT_FORM_LABEL'] ?? null
                    ];
                }
            }

            //Проверка наличия полей
            if ($listCustomValues === []) {
                return $customer;
            }

            $lossCustomFields = [];
            $lossEnumFields = [];

            //Заполнение customer и поиск отсутствующих значений
            foreach ($listCustomValues as $codeField => $values) {
                $crmCode = strtolower($codeField);
                $customer['customFields'][$crmCode] = array_keys($values['items']);//заполнение кастомных полей

                if (!isset($savedCustomEnumFields[$crmCode])) {
                    $elements = [];

                    foreach ($values['items'] as $code => $name) {
                        $elements[] = ['name' => $name, 'code' => $code];
                    }

                    $responseDictionaryCreate = $api->customDictionariesCreate(['code' => $crmCode, 'elements' => $elements]);
                    $responseCustomFieldCreate = $api->customFieldsCreate('customer', ['code' => $crmCode, 'name' => $values['name'], 'type' => 'multiselect_dictionary', 'dictionary' => $crmCode]);

                    $savedCustomEnumFields[$crmCode] = array_column($elements, 'code');
                    //проверка добавление и в случае чего откат
                }

                if (count(array_intersect($savedCustomEnumFields[$crmCode], array_keys($values['items']))) !== count($values['items'])) {
                    $newDictionaryList = array_unique(array_merge($savedCustomEnumFields[$crmCode], array_keys($values['items'])));

                    $elements = []; // тут проблема. Нету названий справочника, т.к. мы получили их из $savedCustomEnumFields в котором только коды.
                    //Возможно стоит добавить в сохраняемых массив и названия, что успростит работу, т.к. не придется вызывать new CUserFieldEnum(); в данном участке кода?
                    //Или же дополнительно искать те, которые мы не заполнили, в холостую в итоге работает все равно. А лучше тупо все написать в логике CUserFieldEnum() и там фиксировать новые элементы!!!!!!


                }


                //Отправка запросов в систему с поиском кастомного поля и справочника по ключу (одинаковый код у поля и справочника)
                //При отсутствии - создаем. При наличии - проверяем наличия значения в справочнике.
                // Производим транслитерацию значения в списке для записи кода. (Или же пишем value_1 ...)
                //записываем в customer['customFields'][код] выбранные значения
            }

            //Инициализация апи-клиента retailcrm

            //Возвращаем customer

            $newResCustomer = retailCrmBeforeCustomerSend($customer);

            if (is_array($newResCustomer) && !empty($newResCustomer)) {
                $customer = $newResCustomer;
            } elseif ($newResCustomer === false) {
                RCrmActions::eventLog('RetailCrmUser::customerSend', 'retailCrmBeforeCustomerSend()', 'UserID = ' . $arFields['ID'] . '. Sending canceled after retailCrmBeforeCustomerSend');

                return false;
            }
        }

        $normalizer = new RestNormalizer();
        $customer = $normalizer->normalize($customer, 'customers');

        if (array_key_exists('UF_SUBSCRIBE_USER_EMAIL', $arFields)) {
            // UF_SUBSCRIBE_USER_EMAIL = '1' or '0'
            $customer['subscribed'] = (bool) $arFields['UF_SUBSCRIBE_USER_EMAIL'];
        }

        Logger::getInstance()->write($customer, 'customerSend');

        if (
            $send
            && !RCrmActions::apiMethod($api, 'customersCreate', __METHOD__, $customer, $site)
        ) {
                return false;
        }

        return $customer;
    }

    public static function customerEdit($arFields, $api, $optionsSitesList = array()) : bool
    {
        if (empty($arFields)) {
            RCrmActions::eventLog('RetailCrmUser::customerEdit', 'empty($arFields)', 'incorrect customer');
            return false;
        }

        $customer = self::getSimpleCustomer($arFields);
        $found = false;

        if (RetailcrmConfigProvider::getCustomFieldsStatus() === 'Y') {
            $customer['customFields'] = self::getCustomFields($arFields);
        }

        if (count($optionsSitesList) > 0) {
            foreach ($optionsSitesList as $site) {
                $userCrm = RCrmActions::apiMethod($api, 'customersGet', __METHOD__, $arFields['ID'], $site);
                if (isset($userCrm['customer'])) {
                    $found = true;
                    break;
                }
            }
        } else {
            $site = null;
            $userCrm = RCrmActions::apiMethod($api, 'customersGet', __METHOD__, $arFields['ID'], $site);
            if (isset($userCrm['customer'])) {
                $found = true;
            }
        }

        if ($found) {
            $normalizer = new RestNormalizer();
            $customer = $normalizer->normalize($customer, 'customers');
            $customer = self::getBooleanFields($customer, $arFields);

            if (function_exists('retailCrmBeforeCustomerSend')) {
                $newResCustomer = retailCrmBeforeCustomerSend($customer);
                if (is_array($newResCustomer) && !empty($newResCustomer)) {
                    $customer = $newResCustomer;
                } elseif ($newResCustomer === false) {
                    RCrmActions::eventLog('RetailCrmUser::customerEdit', 'retailCrmBeforeCustomerSend()', 'UserID = ' . $arFields['ID'] . '. Sending canceled after retailCrmBeforeCustomerSend');

                    return false;
                }
            }

            Logger::getInstance()->write($customer, 'customerSend');

            RCrmActions::apiMethod($api, 'customersEdit', __METHOD__, $customer, $site);
        }

        return true;
    }

    /**
     * @param array $arFields
     *
     * @return array
     */
    private static function getSimpleCustomer(array $arFields): array
    {
        $customer['externalId'] = $arFields['ID'];
        $customer['firstName'] = $arFields['NAME'] ?? null;
        $customer['lastName'] = $arFields['LAST_NAME'] ?? null;
        $customer['patronymic'] = $arFields['SECOND_NAME'] ?? null;
        $customer['phones'][]['number'] = $arFields['PERSONAL_PHONE'] ?? null;
        $customer['phones'][]['number'] = $arFields['WORK_PHONE'] ?? null;
        $customer['address']['city'] = $arFields['PERSONAL_CITY'] ?? null;
        $customer['address']['text'] = $arFields['PERSONAL_STREET'] ?? null;
        $customer['address']['index'] = $arFields['PERSONAL_ZIP'] ?? null;

        if (mb_strlen($arFields['EMAIL']) < 100) {
            $customer['email'] = $arFields['EMAIL'];
        }

        return $customer;
    }

    private static function getBooleanFields($customer, $arFields)
    {
        if (isset($arFields['UF_SUBSCRIBE_USER_EMAIL'])) {
            if ($arFields['UF_SUBSCRIBE_USER_EMAIL'] === "1") {
                $customer['subscribed'] = true;
            } else {
                $customer['subscribed'] = false;
            }
        }

        return $customer;
    }

    private static function getCustomFields(array $arFields)
    {
        if (!method_exists(RCrmActions::class, 'getTypeUserField')
            || !method_exists(RCrmActions::class, 'convertCmsFieldToCrmValue')
        ) {
            return [];
        }

        $customUserFields = RetailcrmConfigProvider::getMatchedUserFields();
        $typeList = RCrmActions::getTypeUserField();
        $result = [];

        foreach ($customUserFields as $code => $codeCrm) {
            if (isset($arFields[$code])) {
                $type = $typeList[$code] ?? '';
                $result[$codeCrm] = RCrmActions::convertCmsFieldToCrmValue($arFields[$code], $type);
            }
        }

        return $result;
    }

    public static function fixDateCustomer(): void
    {
        CAgent::RemoveAgent("RetailCrmUser::fixDateCustomer();", RetailcrmConstants::MODULE_ID);
        COption::SetOptionString(RetailcrmConstants::MODULE_ID, RetailcrmConstants::OPTION_FIX_DATE_CUSTOMER, 'Y');

        $startId = COption::GetOptionInt(RetailcrmConstants::MODULE_ID, RetailcrmConstants::OPTION_FIX_DATE_CUSTOMER_LAST_ID, 0);
        $api = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
        $optionsSitesList = RetailcrmConfigProvider::getSitesList();
        $limit = 50;
        $offset = 0;

        while(true) {
            try {
                $usersResult = UserTable::getList([
                    'select' => ['ID', 'DATE_REGISTER', 'LID'],
                    'filter' => ['>ID' => $startId],
                    'order' => ['ID'],
                    'limit' => $limit,
                    'offset' => $offset,
                ]);
            } catch (\Throwable $exception) {
                Logger::getInstance()->write($exception->getMessage(), 'fixDateCustomers');

                break;
            }

            $users = $usersResult->fetchAll();

            if ($users === []) {
                break;
            }

            foreach ($users as $user) {
                $site = null;

                if ($optionsSitesList) {
                    if (isset($user['LID']) && array_key_exists($user['LID'], $optionsSitesList) && $optionsSitesList[$user['LID']] !== null) {
                        $site = $optionsSitesList[$user['LID']];
                    } else {
                        continue;
                    }
                }

                $customer['externalId'] = $user['ID'];

                try {
                    $date = new \DateTime($user['DATE_REGISTER']);
                    $customer['createdAt'] = $date->format('Y-m-d H:i:s');

                    RCrmActions::apiMethod($api, 'customersEdit', __METHOD__, $customer, $site);
                } catch (\Throwable $exception) {
                    Logger::getInstance()->write($exception->getMessage(), 'fixDateCustomers');
                    continue;
                }

                time_nanosleep(0, 250000000);
            }

            COption::SetOptionInt(RetailcrmConstants::MODULE_ID, RetailcrmConstants::OPTION_FIX_DATE_CUSTOMER_LAST_ID, end($users)['ID']);

            $offset += $limit;
        }
    }
}
