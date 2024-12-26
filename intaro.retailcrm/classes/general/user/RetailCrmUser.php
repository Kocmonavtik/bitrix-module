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
use Bitrix\Main\Config\Option;
use Bitrix\Main\Diag\FileLogger;
use Intaro\RetailCrm\Component\ConfigProvider;
use RetailCrm\ApiClient;
use CUserFieldEnum;

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

        $customer = self::getSimpleCustomer($arFields);

        $user = CUser::GetByID($customer['externalId']);
        $test = $user->Fetch();

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
            //Пример массива $options = ['uf_test' => ['test1' => 'Название', 'test2' => 'Название2', 'test3' => 'Название 3']], где ключ - это код справочника и кастомного поля. Значения - код справочников
            // Может быть добавить сюда ещё id поля спрачоника, тогда обойдем вариант постоянного поиска кодов. Но не особо безопасно, т.к. вариант в справочнике может быть переименован.
            //$savedCustomEnumFields = unserialize(COption::GetOptionString('intaro.retailcrm', 'saved_custom_enum_fields', 0), []);
            $savedCustomEnumFields = unserialize(Option::get('intaro.retailcrm', 'saved_custom_enum_fields', 0), []);

            if (!$savedCustomEnumFields) {
                Option::set('intaro.retailcrm', 'saved_custom_enum_fields', serialize([]));
            }

            // Получаем все кастомные поля объекта USER и его переводы
            $userFields = UserFieldTable::getList([
                'select' => ['ID', 'FIELD_NAME', 'ENTITY_ID', 'USER_TYPE_ID', 'MULTIPLE', 'SETTINGS', 'TITLE'],
                'filter' => [
                    '=ENTITY_ID' => 'USER',
                    '=MAIN_USER_FIELD_TITLE_LANGUAGE_ID' => 'ru',
                    'USER_TYPE_ID' => 'enumeration',
                ],
                'runtime' => [
                    'TITLE' => [
                        'data_type' => UserFieldLangTable::getEntity(),
                        'reference' => [
                            '=this.ID' => 'ref.USER_FIELD_ID',
                        ],
                    ],
                ],
            ])->fetchAll();

            $listCustomValues = [];
            $enumBuilder = new CUserFieldEnum();

            //сборка всех необходимых параметров
            foreach ($userFields as $userField) {
                if (!isset($userField['FIELD_NAME'])) {
                    continue;
                }

                if (isset($arFields[$userField['FIELD_NAME']]) && !empty($arFields[$userField['FIELD_NAME']])) {
                    $arEnum = $enumBuilder->GetList([], ['USER_FIELD_NAME' => $userField['FIELD_NAME']]);
                    $enumItems = [];

                    //Получение значения выбранного элемента в списке
                    while ($enumElement = $arEnum->Fetch()) {
                        $enumValue = $arFields[$userField['FIELD_NAME']];

                        if (
                            (!is_array($enumValue) && $enumElement['ID'] == $enumValue)
                            || (is_array($enumValue) && in_array($enumElement['ID'], $enumValue))
                        ) {
                            //временная конструкция
                            $code = function ($string) {
                                $translit = "Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Lower();";
                                $string = transliterator_transliterate($translit, $string);
                                $string = preg_replace('/[-\s]+/', '_', $string);
                                return trim($string, '-');
                            };

                            $enumItems[$code($enumElement['VALUE'])] = $enumElement['VALUE'];
                        }
                    }

                    $listCustomValues[$userField['FIELD_NAME']] = [
                        'items' => $enumItems,
                        'name' => $userField['MAIN_USER_FIELD_TITLE_EDIT_FORM_LABEL'] ?? strtolower($userField['FIELD_NAME']),
                        'isMultiple' => $userField['MULTIPLE'] === 'Y'
                    ];
                }
            }

            //Проверка наличия полей
            if ($listCustomValues === []) {
                return $customer;
            }

            /**
             * @var \RetailCrm\ApiClient $api
             */
            //Заполнение customer и поиск отсутствующих значений
            foreach ($listCustomValues as $codeField => $values) {
                $crmCode = strtolower($codeField);
                $customFieldValue = array_keys($values['items']);

                if (!$values['isMultiple'] && count($customFieldValue) === 1) {
                    $customFieldValue = current($customFieldValue);
                }

                $customer['customFields'][$crmCode] = $customFieldValue;

                if (!isset($savedCustomEnumFields[$crmCode])) {
                    $elements = [];

                    foreach ($values['items'] as $code => $name) {
                        $elements[] = ['name' => $name, 'code' => $code];
                    }

                    $responseDictionaryCreate = $api->customDictionariesCreate(['code' => $crmCode,'name' => $values['name'], 'elements' => $elements]);

                    if (!$responseDictionaryCreate->isSuccessful()) {
                        unset($customer['customFields'][$crmCode]);

                        Logger::getInstance()//прописать путь к логам
                            ->write(
                                sprintf(
                                    'Справочник %s не был выгружен. Клиент %s был выгружен без справочника. (Code: %s. Message %s)',
                                    $codeField,
                                    $customer['externalId'],
                                    $responseDictionaryCreate->getStatusCode(),
                                    implode(';', $responseDictionaryCreate->getResponseBody())
                                )
                            )
                        ;

                        continue;
                    }

                    $fieldType = $values['isMultiple'] ? 'multiselect_dictionary' : 'dictionary';
                    $responseCustomFieldCreate = $api->customFieldsCreate('customer', ['code' => $crmCode, 'name' => $values['name'], 'type' => $fieldType, 'dictionary' => $crmCode]);

                    if (!$responseCustomFieldCreate->isSuccessful()) {
                        unset($customer['customFields'][$crmCode]);
                        continue;//логгирование
                    }

                    foreach ($elements as $element) {
                        $savedCustomEnumFields[$crmCode][$element['code']] = $element['name'];
                    }

                    Option::set('intaro.retailcrm', 'saved_custom_enum_fields', serialize($savedCustomEnumFields));
                    //COption::SetOptionString('intaro.retailcrm', 'saved_custom_enum_fields', serialize($savedCustomEnumFields));
                } elseif (count(array_intersect(array_keys($savedCustomEnumFields[$crmCode]), array_keys($values['items']))) !== count($values['items'])) {
                    $newDictionaryList = array_unique(array_merge($savedCustomEnumFields[$crmCode], $values['items']));//получение полного нового справочника.

                    $savedCustomEnumFields[$crmCode] = $newDictionaryList;

                    $elements = [];

                    foreach ($newDictionaryList as $code => $value)
                    {
                        $elements[] = ['name' => $value, 'code' => $code];
                    }

                    $responseDictionaryEdit = $api->customDictionariesEdit(['code' => $crmCode, 'name' => $values['name'], 'elements' => $elements]);

                    if (!$responseDictionaryEdit->isSuccessful()) {
                        unset($customer['customFields'][$crmCode]);
                        continue;//логгирование
                    }

                    Option::set('intaro.retailcrm', 'saved_custom_enum_fields', serialize($savedCustomEnumFields));
                    //COption::SetOptionString('intaro.retailcrm', 'saved_custom_enum_fields', serialize($savedCustomEnumFields));
                }
            }

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
        $api = new ApiClient(ConfigProvider::getApiUrl(), ConfigProvider::getApiKey());

        if (empty($arFields)) {
            RCrmActions::eventLog('RetailCrmUser::customerEdit', 'empty($arFields)', 'incorrect customer');
            return false;
        }

        $customer = self::getSimpleCustomer($arFields);
        $found = false;

        $user = CUser::GetByID(9999999)->Fetch();
        //$test = $user->Fetch();

        $customer['createdAt'] = new \DateTime($arFields['DATE_REGISTER']);

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
                //Следующий код для функции:
                //Получаем сохраненный список полей. В случае отсутствия - создаем. Код модуля и константа от разработчика (текущие под вопросом)
                //Пример массива $options = ['uf_test' => ['test1' => 'Название', 'test2' => 'Название2', 'test3' => 'Название 3']], где ключ - это код справочника и кастомного поля. Значения - код справочников
                // Может быть добавить сюда ещё id поля спрачоника, тогда обойдем вариант постоянного поиска кодов. Но не особо безопасно, т.к. вариант в справочнике может быть переименован.
                //$savedCustomEnumFields = unserialize(COption::GetOptionString('intaro.retailcrm', 'saved_custom_enum_fields', 0), []);
                $savedCustomEnumFields = unserialize(Option::get('intaro.retailcrm', 'saved_custom_enum_fields', 0), []);

                if (!$savedCustomEnumFields) {
                    Option::set('intaro.retailcrm', 'saved_custom_enum_fields', serialize([]));
                }

                // Получаем все кастомные поля объекта USER и его переводы
                $userFields = UserFieldTable::getList([
                    'select' => ['ID', 'FIELD_NAME', 'ENTITY_ID', 'USER_TYPE_ID', 'MULTIPLE', 'SETTINGS', 'TITLE'],
                    'filter' => [
                        '=ENTITY_ID' => 'USER',
                        '=MAIN_USER_FIELD_TITLE_LANGUAGE_ID' => 'ru',
                        'USER_TYPE_ID' => 'enumeration',
                    ],
                    'runtime' => [
                        'TITLE' => [
                            'data_type' => UserFieldLangTable::getEntity(),
                            'reference' => [
                                '=this.ID' => 'ref.USER_FIELD_ID',
                            ],
                        ],
                    ],
                ])->fetchAll();

                $listCustomValues = [];
                $enumBuilder = new CUserFieldEnum();

                //сборка всех необходимых параметров
                foreach ($userFields as $userField) {
                    if (!isset($userField['FIELD_NAME'])) {
                        continue;
                    }

                    if (isset($arFields[$userField['FIELD_NAME']]) && !empty($arFields[$userField['FIELD_NAME']])) {
                        $arEnum = $enumBuilder->GetList([], ['USER_FIELD_NAME' => $userField['FIELD_NAME']]);
                        $enumItems = [];

                        //Получение значения выбранного элемента в списке
                        while ($enumElement = $arEnum->Fetch()) {
                            $enumValue = $arFields[$userField['FIELD_NAME']];

                            if (
                                (!is_array($enumValue) && $enumElement['ID'] == $enumValue)
                                || (is_array($enumValue) && in_array($enumElement['ID'], $enumValue))
                            ) {
                                //временная конструкция
                                $code = function ($string) {
                                    $translit = "Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Lower();";
                                    $string = transliterator_transliterate($translit, $string);
                                    $string = preg_replace('/[-\s]+/', '_', $string);
                                    return trim($string, '-');
                                };

                                $enumItems[$code($enumElement['VALUE'])] = $enumElement['VALUE'];
                            }
                        }

                        $listCustomValues[$userField['FIELD_NAME']] = [
                            'items' => $enumItems,
                            'name' => $userField['MAIN_USER_FIELD_TITLE_EDIT_FORM_LABEL'] ?? strtolower($userField['FIELD_NAME']),
                            'isMultiple' => $userField['MULTIPLE'] === 'Y'
                        ];
                    }
                }

                //Проверка наличия полей
                if ($listCustomValues === []) {
                    return $customer;
                }

                /**
                 * @var \RetailCrm\ApiClient $api
                 */
                //Заполнение customer и поиск отсутствующих значений
                foreach ($listCustomValues as $codeField => $values) {
                    $crmCode = strtolower($codeField);
                    $customFieldValue = array_keys($values['items']);

                    if (!$values['isMultiple'] && count($customFieldValue) === 1) {
                        $customFieldValue = current($customFieldValue);
                    }

                    $customer['customFields'][$crmCode] = $customFieldValue;

                    if (!isset($savedCustomEnumFields[$crmCode])) {
                        $elements = [];

                        foreach ($values['items'] as $code => $name) {
                            $elements[] = ['name' => $name, 'code' => $code];
                        }

                        $responseDictionaryCreate = $api->customDictionariesCreate(['code' => $crmCode,'name' => $values['name'], 'elements' => $elements]);

                        if (!$responseDictionaryCreate->isSuccessful()) {
                            unset($customer['customFields'][$crmCode]);

                            Logger::getInstance()//прописать путь к логам
                            ->write(
                                sprintf(
                                    'Справочник %s не был выгружен. Клиент %s был выгружен без справочника. (Code: %s. Message %s)',
                                    $codeField,
                                    $customer['externalId'],
                                    $responseDictionaryCreate->getStatusCode(),
                                    implode(';', $responseDictionaryCreate->getResponseBody())
                                )
                            )
                            ;

                            continue;
                        }

                        $fieldType = $values['isMultiple'] ? 'multiselect_dictionary' : 'dictionary';
                        $responseCustomFieldCreate = $api->customFieldsCreate('customer', ['code' => $crmCode, 'name' => $values['name'], 'type' => $fieldType, 'dictionary' => $crmCode]);

                        if (!$responseCustomFieldCreate->isSuccessful()) {
                            unset($customer['customFields'][$crmCode]);
                            continue;//логгирование
                        }

                        foreach ($elements as $element) {
                            $savedCustomEnumFields[$crmCode][$element['code']] = $element['name'];
                        }

                        Option::set('intaro.retailcrm', 'saved_custom_enum_fields', serialize($savedCustomEnumFields));
                        //COption::SetOptionString('intaro.retailcrm', 'saved_custom_enum_fields', serialize($savedCustomEnumFields));
                    } elseif (count(array_intersect(array_keys($savedCustomEnumFields[$crmCode]), array_keys($values['items']))) !== count($values['items'])) {
                        $newDictionaryList = array_unique(array_merge($savedCustomEnumFields[$crmCode], $values['items']));//получение полного нового справочника.

                        $savedCustomEnumFields[$crmCode] = $newDictionaryList;

                        $elements = [];

                        foreach ($newDictionaryList as $code => $value)
                        {
                            $elements[] = ['name' => $value, 'code' => $code];
                        }

                        $responseDictionaryEdit = $api->customDictionariesEdit(['code' => $crmCode, 'name' => $values['name'], 'elements' => $elements]);

                        if (!$responseDictionaryEdit->isSuccessful()) {
                            unset($customer['customFields'][$crmCode]);
                            continue;//логгирование
                        }

                        Option::set('intaro.retailcrm', 'saved_custom_enum_fields', serialize($savedCustomEnumFields));
                        //COption::SetOptionString('intaro.retailcrm', 'saved_custom_enum_fields', serialize($savedCustomEnumFields));
                    }
                }






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
