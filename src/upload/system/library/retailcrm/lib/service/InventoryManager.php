<?php

namespace retailcrm\service;

class InventoryManager 
{
    private $api;

    public function __construct(\RetailcrmProxy $api) {
        $this->api = $api;
    }

    public function storeInventoriesUpload($pack) {
       return  $this->api->storeInventoriesUpload($pack);
    }
    
    public function getInventories($filters, $page)
    {
        return $this->api->storeInventories($filters, $page);
    }
}
