<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component;

/**
 * Class Constants
 *
 * @package Intaro\RetailCrm\Component
 */
class Constants
{
    public const CRM_PURCHASE_PRICE_NULL = 'purchasePrice_null';
    public const BITRIX_USER_ID_PREFIX = 'bitrixUserId-';
    public const CRM_USERS_MAP = 'crm_users_map';
    public const CRM_INTEGRATION_PAYMENT = 'integration_payment';
    public const MODULE_ID = 'intaro.retailcrm';
    public const CRM_API_HOST_OPTION = 'api_host';
    public const CRM_API_KEY_OPTION = 'api_key';
    public const CRM_ORDER_TYPES_ARR = 'order_types_arr';
    public const CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
    public const CRM_DELIVERY_SERVICES_ARR = 'deliv_services_arr';
    public const CRM_PAYMENT_TYPES = 'pay_types_arr';
    public const CRM_PAYMENT_STATUSES = 'pay_statuses_arr';
    public const CRM_PAYMENT = 'payment_arr';
    public const CRM_ORDER_LAST_ID = 'order_last_id';
    public const CRM_ORDER_SITES = 'sites_ids';
    public const CRM_ORDER_DISCHARGE = 'order_discharge';
    public const CRM_SITES_LIST = 'sites_list';
    public const CRM_ORDER_PROPS = 'order_props';
    public const CRM_LEGAL_DETAILS = 'legal_details';
    public const CRM_CUSTOM_FIELDS = 'custom_fields';
    public const CRM_CONTRAGENT_TYPE = 'contragent_type';
    public const CRM_SITES_LIST_CORPORATE = 'shops-corporate';
    public const CRM_ORDER_NUMBERS = 'order_numbers';
    public const CRM_ORDER_VAT = 'order_vat';
    public const CRM_COUPON_FIELD = 'crm_coupon_field';
    public const CRM_CANCEL_ORDER = 'cansel_order';
    public const CRM_INVENTORIES_UPLOAD = 'inventories_upload';
    public const CRM_STORES = 'stores';
    public const CRM_SHOPS = 'shops';
    public const CRM_IBLOCKS_INVENTORIES = 'iblocks_inventories';
    public const CRM_PRICES_UPLOAD = 'prices_upload';
    public const CRM_PRICES = 'prices';
    public const CRM_PRICE_SHOPS = 'price_shops';
    public const CRM_IBLOCKS_PRICES = 'iblock_prices';
    public const CRM_COLLECTOR = 'collector';
    public const CRM_COLL_KEY = 'coll_key';
    public const CRM_UA = 'ua';
    public const CRM_UA_KEYS = 'ua_keys';
    public const CRM_DISCOUNT_ROUND = 'discount_round';
    public const CRM_CC = 'cc';
    public const CRM_CORP_SHOPS = 'shops-corporate';
    public const CRM_CORP_NAME = 'nickName-corporate';
    public const CRM_CORP_ADDRESS = 'adres-corporate';
    public const CRM_API_VERSION = 'api_version';
    public const CRM_CURRENCY = 'currency';
    public const CRM_ADDRESS_OPTIONS = 'address_options';
    public const CRM_DIMENSIONS = 'order_dimensions';
    public const PROTOCOL                       = 'protocol';
    public const CRM_ORDER_FAILED_IDS           = 'order_failed_ids';
    public const CRM_CUSTOMERS_HISTORY_SINCE_ID = 'customer_history';
    public const CRM_ORDER_HISTORY_DATE = 'order_history_date';
    public const CRM_CATALOG_BASE_PRICE = 'catalog_base_price';
    public const CRM_ORDER_DIMENSIONS = 'order_dimensions';
    public const CANCEL_PROPERTY_CODE = 'INTAROCRM_IS_CANCELED';
    public const CRM_INTEGRATION_DELIVERY = 'integration_delivery';
    public const CRM_SHIPMENT_DEDUCTED        = 'shipment_deducted';
    public const CORPORATE_CONTRAGENT_TYPE    = 'legal-entity';
    public const SEND_PAYMENT_AMOUNT          = 'send_payment_amount';
    public const CRM_ONLINE_CONSULTANT        = 'online_consultant';
    public const CRM_ONLINE_CONSULTANT_SCRIPT = 'online_consultant_script';
    public const LOYALTY_PROGRAM_TOGGLE    = 'loyalty_program_toggle';
    public const CLIENT_ID                 = 'client_id';
    public const AGREEMENT_LOYALTY_PROGRAM = 'agreement_loyalty_program';
    public const AGREEMENT_PERSONAL_DATA  = 'agreement_personal_data';
    public const HL_LOYALTY_CODE          = 'LoyaltyProgramRetailCRM';
    public const HL_LOYALTY_TABLE_NAME    = 'loyalty_program';
    public const LP_BONUS_INFO                  = 'LP_BONUS_INFO';
    public const API_ERRORS_LOG                 = 'apiErrors';
    public const LOYALTY_ERROR                  = 'loyaltyErrors';
    public const HISTORY_ERRORS                 = 'historyErrors';
    public const REPOSITORY_ERRORS              = 'repositoryErrors';
    public const TEMPLATES_ERROR                = 'templatesErrors';
    public const DEFAULT_LOYALTY_TEMPLATE       = 'default_loyalty';
    public const LOYALTY_PROGRAM_ID             = 'LOYALTY_PROGRAM_ID';
    public const LOYALTY_FIELDS                 = 'loyalty_fields';
    public const AGREEMENT_PERSONAL_DATA_CODE   = 'AGREEMENT_PERSONAL_DATA_CODE';
    public const AGREEMENT_LOYALTY_PROGRAM_CODE = 'AGREEMENT_LOYALTY_PROGRAM_CODE';
    public const CART                           = 'cart';
    public const LP_EVENTS                      = [
        ['EVENT_NAME' => 'OnSaleOrderSaved', 'FROM_MODULE' => 'sale'],
        ['EVENT_NAME' => 'OnSaleComponentOrderResultPrepared', 'FROM_MODULE' => 'sale'],
        ['EVENT_NAME' => 'OnAfterUserRegister', 'FROM_MODULE' => 'main'],
    ];
    public const SUBSCRIBE_EVENTS = [
        ['EVENT_NAME' => 'OnAfterUserRegister', 'FROM_MODULE' => 'main'],
        ['EVENT_NAME' => 'OnAfterUserUpdate', 'FROM_MODULE' => 'main']
    ];
    public const SITES_AVAILABLE = 'sites_available';
}
