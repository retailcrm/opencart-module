<?php

require_once __DIR__ . '/../' . getenv('TEST_SUITE') . '/TestCase.php';

class ModelRetailcrmInventoryAdminTest extends TestCase
{
    private $inventoriesModel;
    private $apiClientMock;

    public function setUp()
    {
        parent::setUp();

        $this->inventoriesModel = $this->loadModel('extension/retailcrm/inventories');

        $this->apiClientMock = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                'storeInventoriesUpload',
            ))
            ->getMock();

        self::$registry->set(\RetailcrmProxy::class, $this->apiClientMock);
    }

    public function testUploadToCrm()
    {
        $productModel = $this->loadModel('catalog/product');
        $product = $productModel->getProducts([]);
        $productSend = $this->inventoriesModel->uploadToCrm($product, $this->apiClientMock);
        $product= $productSend[0][0];

        $this->assertInternalType('array', $productSend);
        $this->assertArrayHasKey('externalId', $product);
        $this->assertArrayHasKey('stores', $product);
    }
}
