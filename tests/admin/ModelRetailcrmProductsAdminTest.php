<?php

namespace Tests;

class ModelRetailcrmProductsAdminTest extends OpenCartTest
{
    public function testGetProductOptions()
    {
        $model = $this->loadModel('extension/retailcrm/products');
        $options = $model->getProductOptions(42);

        $this->assertNotEmpty($options);
    }
}
