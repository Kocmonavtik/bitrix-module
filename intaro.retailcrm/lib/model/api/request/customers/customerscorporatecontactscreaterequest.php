<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\Customers
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Request\Customers;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\Request\ByTrait;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Model\Api\Request\SiteScopedTrait;

/**
 * Class CustomersCorporateCompaniesRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Customers
 */
class CustomersCorporateContactsCreateRequest extends AbstractApiModel
{
    use ByTrait;
    use SiteScopedTrait;

    /**
     * @var string
     */
    public $idOrExternalId;

    /**
     * @var \Intaro\RetailCrm\Model\Api\CustomerContact
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\CustomerContact")
     * @Mapping\SerializedName("contact")
     */
    public $contact;
}
