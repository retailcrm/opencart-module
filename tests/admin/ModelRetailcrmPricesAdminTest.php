<?php

require_once __DIR__ . '/../' . getenv('TEST_SUITE') . '/TestCase.php';

class ModelRetailcrmPricesAdminTest extends TestCase
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
                'sitesList',
                'isSuccessful'
            ))
            ->getMock();

        $this->settingModel = $this->loadModel('setting/setting');
        $this->retailcrm = new \retailcrm\Retailcrm(self::$registry);

        $this->settingModel->editSetting(
            $this->retailcrm->getModuleTitle(),
            array(
                $this->retailcrm->getModuleTitle() . '_apiversion' => 'v5',
                $this->retailcrm->getModuleTitle() . '_special_1' => 'special1',
                $this->retailcrm->getModuleTitle() . '_special_2' => 'special2',
                $this->retailcrm->getModuleTitle() . '_special_3' => 'special3'
            )
        );
    }

    public function testUploadPrices()
    {

        $response = new \RetailcrmApiResponse(
            201,
            json_encode(
                $this->sites()
            )
        );

        $this->apiClientMock->expects($this->any())->method('sitesList')->willReturn($response);
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
        $this->assertEquals('special1', $price['prices'][0]['code']);
        $this->assertEquals('special2', $price['prices'][1]['code']);
        $this->assertEquals('special3', $price['prices'][2]['code']);
        $this->assertFalse($price['prices'][0]['remove']);
        $this->assertNotFalse($price['prices'][1]['remove']);
        $this->assertNotFalse($price['prices'][2]['remove']);
    }

    private function sites(){
        return array(
            "success"=> true,
            "sites"=> array(
                "BitrixMod"=> array(
                    "name"=> "site",
                    "url"=> "http://site.ru",
                    "code"=> "site",
                    "defaultForCrm"=> false,
                    "ymlUrl"=> "http://site.ru/retailcrm.xml",
                    "loadFromYml"=> false,
                    "catalogUpdatedAt"=> "2019-02-08 13:30:37",
                    "catalogLoadingAt"=> "2019-02-11 09:12:18",
                    "contragent"=> array(
                        "contragentType"=> "legal-entity",
                        "legalName"=> "code",
                        "code"=> "code",
                        "countryIso"=> "RU",
                        "vatRate"=> "",
                    ),
                    "countryIso"=> "",
                )
            ),
        );
    }
}