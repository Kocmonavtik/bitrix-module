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
use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Model\Api\Request\ByTrait;
use Intaro\RetailCrm\Model\Api\Request\EntityByTrait;
use Intaro\RetailCrm\Model\Api\Request\SiteScopedTrait;

/**
 * Class CustomersCorporateAddressesEditRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Customers
 */
class CustomersCorporateAddressesEditRequest extends AbstractApiModel
{
    use ByTrait;
    use EntityByTrait;
    use SiteScopedTrait;

    /**
     * @var string
     */
    public $externalId;

    /**
     * @var \Intaro\RetailCrm\Model\Api\Address
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Address")
     * @Mapping\SerializedName("address")
     */
    public $address;
}
