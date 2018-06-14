<?php

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
        $this->retailcrm = new \retailcrm\Retailcrm(self::$registry);

        $this->settingModel->editSetting(
            $this->retailcrm->getModuleTitle(),
            array(
                $this->retailcrm->getModuleTitle() . '_apiversion' => 'v5',
                $this->retailcrm->getModuleTitle() . '_special' => 'special'
            )
        );
    }

    public function testUploadPrices()
    {
        $productModel = $this->loadModel('catalog/product');
        $products = $productModel->getProducts();
        $prices = $this->pricesModel->uploadPrices($products, $this->apiClientMock);
        $price = $prices[0][0];

        $this->assertInternalType('array', $prices);
        $this->assertInternalType('array', $prices[0]);
        $this->assertInternalType('array', $price);
        $this->assertArrayHasKey('externalId', $price);
        $this->assertArrayHasKey('site', $price);
        $this->assertArrayHasKey('prices', $price);
        $this->assertInternalType('array', $price['prices']);
    }
}