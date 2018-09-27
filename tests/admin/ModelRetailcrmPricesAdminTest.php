<?php

namespace Tests;

class ModelRetailcrmPricesAdminTest extends OpenCartTest
{
    private $pricesModel;
    private $apiClientMock;
    private $settingModel;
    private $retailcrm;

    public function setUp()
    {
        parent::setUp();

        $this->pricesModel = $this->loadModel('extension/retailcrm/prices');

        $this->apiClientMock = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                'storePricesUpload',
                'sitesList'
            ))
            ->getMock();

        $this->settingModel = $this->loadModel('setting/setting');
        $this->retailcrm = new \Retailcrm\Retailcrm(self::$registry);

        $this->settingModel->editSetting(
            \Retailcrm\Retailcrm::MODULE,
            array(
                \Retailcrm\Retailcrm::MODULE . '_apiversion' => 'v5',
                \Retailcrm\Retailcrm::MODULE . '_special' => 'special'
            )
        );
    }

    public function testUploadPrices()
    {
        $response = new \RetailcrmApiResponse(200, json_encode($this->getSites()));
        $this->apiClientMock->expects($this->any())->method('sitesList')->willReturn($response);

        $productModel = $this->loadModel('catalog/product');
        $products = $productModel->getProducts();
        $prices = $this->pricesModel->uploadPrices($products, $this->apiClientMock, $this->retailcrm);
        $price = $prices[0][0];

        $this->assertInternalType('array', $prices);
        $this->assertInternalType('array', $prices[0]);
        $this->assertInternalType('array', $price);
        $this->assertArrayHasKey('externalId', $price);
        $this->assertArrayHasKey('site', $price);
        $this->assertEquals('test_site', $price['site']);
        $this->assertArrayHasKey('prices', $price);
        $this->assertInternalType('array', $price['prices']);
    }

    private function getSites()
    {
        return array(
            'success' => true,
            'sites' => array(
                array(
                    'code' => 'test_site'
                )
            )
        );
    }
}