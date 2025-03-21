<?php

class ModelExtensionRetailcrmInventories extends Model {

    public function uploadInventories()
    {
        $this->load->model('setting/setting');
        
        $module_setting = $this->model_setting_setting->getSetting('module_retailcrm');
        $uploadType = $module_setting['module_retailcrm_stock_upload'];
        $store = $module_setting['module_retailcrm_store_select'];

        if ($uploadType === '1') {
            $this->toCrmUpload($store);
        }
    }

    public function toCrmUpload($store) {
        $products = $this->model_catalog_product->getProducts([]);
        $offers = [];

        foreach ($products as $product) {
           $offers[] = [
               'externalId' => $product['product_id'],
               'stores' => [['code' => $store, 'available' => $product['quantity']]]
            ]; 
        }

        $packs = array_chunk($offers, 50);

        foreach ($packs as $pack) {
           $this->sendToCrm($pack);
        }
   }

   public function sendToCrm($pack) {
       $inventory_manager = $this->retailcrm->getInventoryManager(); 
      
       return $inventory_manager->storeInventoriesUpload($pack);
   }
}

