<?php

require_once __DIR__ . '/../' . getenv('TEST_SUITE') . '/TestCase.php';

class ModelRetailcrmInventoryAdminTest extends TestCase
{
    private $inventoriesModel;
    private $apiClientMock;
    private $dbMock;

    public function setUp()
    {
        parent::setUp();

        $this->inventoriesModel = $this->loadModel('extension/retailcrm/inventories');

        $this->apiClientMock = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                'storeInventoriesUpload','getInventories'
            ))
            ->getMock();
        
        $this->dbMock = $this->getMockBuilder(\DB::class)
            ->disableOriginalConstructor()
            ->setMethods(array('query'))
            ->getMock();

        $this->inventoriesModel->db = $this->dbMock;
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
    
    public function testFromCrmUpload()
    {
        $store = 'test_store';
        $testOffer = [
            'offers' => [
                [
                    'externalId' => '41',
                    'quantity' => 15,
                ]
            ]
        ];

        $this->apiClientMock->expects($this->once())
            ->method('getInventories')
            ->with(
                $this->equalTo(['sites' => [$store], 'details' => 0]),
                $this->equalTo(1)
            )
            ->willReturn($testOffer);

        $expectedQuery = "UPDATE " . DB_PREFIX . "product SET quantity = '15' WHERE product_id = '41'";

        $this->dbMock->expects($this->once())
            ->method('query')
            ->with($expectedQuery);

        $this->inventoriesModel->fromCrmUpload($store);
    }
}
