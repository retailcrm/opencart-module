<?php

class ModelExtensionRetailcrmInventories extends Model {

    public function uploadInventories()
    {
        $this->load->model('setting/setting');
        
        $module_setting = $this->model_setting_setting->getSetting('module_retailcrm');
        $uploadType = $module_setting['module_retailcrm_stock_upload'];
        $store = $module_setting['module_retailcrm_store_select'] ?? null;

        if ($uploadType === '1') {
            $this->toCrmUpload($store);
        } elseif ($uploadType === '2') {
            $this->fromCrmUpload();
        } else {
            return;
        }
    }

    public function toCrmUpload($store) 
    {
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
            try {
                $this->sendToCrm($pack);
            } catch (Exception $exception) {
                $this->log->write($exception->getMessage());

                continue;
            }
        }
    }

    public function fromCrmUpload() 
    {
        $page = 1;

        do {
            try {
                $products = $this->getProducts($page);
            } catch (Exception $exception) {
                $this->log->write($exception->getMessage());

                break;
            }

            foreach ($products['offers'] as $offer) {
                if (isset($offer['externalId'])) {
                    try {
                        $this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = '" . $offer['quantity'] . "' WHERE product_id = '" . $offer['externalId'] . "'");
                    } catch (Exception $exception) {
                        $this->log->write($exception->getMessage());

                        continue;
                    }
                }
            }

            $page++;
        } while (count($products['offers']) > 0);
    }

    public function sendToCrm($pack) 
    {
        $inventory_manager = $this->retailcrm->getInventoryManager(); 
      
        return $inventory_manager->storeInventoriesUpload($pack);
    }

    public function getProducts($page)
    {
        $inventory_manager = $this->retailcrm->getInventoryManager();

        $filters = ['details' => 0];

        return $inventory_manager->getInventories($filters, $page);
    }
}

