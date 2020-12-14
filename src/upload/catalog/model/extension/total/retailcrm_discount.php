<?php
class ModelExtensionTotalRetailcrmDiscount extends Model {
    public function getTotal($total) {
        $total['totals'][] = array(
            'code'       => \retailcrm\Retailcrm::RETAILCRM_DISCOUNT,
            'title'      => '',
            'value'      => '',
            'sort_order' => \retailcrm\Retailcrm::RETAILCRM_DISCOUNT_SORT_ORDER,
        );
    }
}
