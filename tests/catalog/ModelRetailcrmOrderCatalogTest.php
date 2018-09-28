<?php

namespace Tests;

class ModelRetailcrmOrderCatalogTest extends OpenCartTest
{
    public function testGetOrderStatus()
    {
        $model = $this->loadModel('extension/retailcrm/order');
        $status_id = $model->getOrderStatusId(1);

        $this->assertNotEmpty($status_id);
        $this->assertNotNull($status_id);
    }
}
