<?php

namespace Tests;

class ModelRetailcrmProductsCatalogTest extends OpenCartTest
{
    public function testGetProductOptions()
    {
        $model = $this->loadModel('extension/retailcrm/products');
        $options = $model->getProductOptions(42);

        $this->assertNotEmpty($options);
    }
}