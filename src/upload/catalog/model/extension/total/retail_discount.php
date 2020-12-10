<?php
class ModelExtensionTotalRetailDiscount extends Model {
    public function getTotal($total) {
        $this->load->language('extension/total/discount');

        $total['totals'][] = array(
            'code'       => 'retail_discount',
            'title'      => $this->language->get('retail_discount_order'),
            'value'      => max(0, $total['retail_discount'])
        );
    }
}