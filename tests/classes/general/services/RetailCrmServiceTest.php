<?php

use Tests\Intaro\RetailCrm\DataService;

/**
 * Class RetailCrmServiceTest
 */
class RetailCrmServiceTest extends PHPUnit\Framework\TestCase
{
    public function testOnUnsetIntegrationDeliveryFields()
    {
        $value = serialize(['boxberry' => 'test']);
        COption::SetOptionString(RetailcrmConstants::MODULE_ID, RetailcrmConstants::CRM_INTEGRATION_DELIVERY, $value);
        $newParams     = RetailCrmService::unsetIntegrationDeliveryFields(DataService::deliveryDataForValidation());
        $expectedArray = [
            'delivery' => [
                'code' => 'boxberry',
            ],
        ];
        
        $this->assertEquals($newParams, $expectedArray);
    }

    public function testOnUnsetIntegrationDeliveryFieldsWithCourier()
    {
        $value = serialize(['test' => 'courier']);
        COption::SetOptionString(RetailcrmConstants::MODULE_ID, RetailcrmConstants::CRM_INTEGRATION_DELIVERY, $value);
        $result = RetailCrmService::unsetIntegrationDeliveryFields(DataService::deliveryDataCourier());

        $this->assertEquals(DataService::deliveryDataCourier(), $result);
    }

    /**
     * @param array $data
     * @param array $expected
     * @dataProvider selectIntegrationDeliveriesProvider
     */
    public function testSelectIntegrationDeliveries(array $data, array $expected)
    {
        $this->assertEquals($expected, RetailCrmService::selectIntegrationDeliveries($data));
    }

    /**
     * @param array $data
     * @param array $expected
     * @dataProvider selectIntegrationPaymentsProvider
     */
    public function testSelectIntegrationPayments(array $data, array $expected)
    {
        $this->assertEquals($expected, RetailCrmService::selectIntegrationPayments($data));
    }

    public function selectIntegrationDeliveriesProvider()
    {
        return [[
           'data' => [
               [
                   'code' => 'test1',
                   'integrationCode' => 'test2'
               ]
           ],
            'expected' => ['test1' => 'test2']
        ]];
    }

    public function selectIntegrationPaymentsProvider()
    {
        return [[
            'data' => [
                [
                    'code' => 'test1',
                    'integrationModule' => 'test2'
                ]
            ],
            'expected' => ['test1']
        ]];
    }
}
