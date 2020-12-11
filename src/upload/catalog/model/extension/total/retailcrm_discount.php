<?php
class ModelExtensionTotalRetailCrmDiscount extends Model {
    public function getTotal($total) {
        $this->load->language('extension/total/discount');

        $total['totals'][] = array(
            'code'       => \retailcrm\Retailcrm::RETAILCRM_DISCOUNT,
            'title'      => $this->config->get('module_retailcrm_label_retailcrm_discount'),
            'value'      => max(0, $total['total']),
            'sort_order' => $this->config->get('module_retailcrm_label_retailcrm_discount')
        );
    }
}
