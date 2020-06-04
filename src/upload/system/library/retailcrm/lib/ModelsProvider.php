<?php

namespace retailcrm;

class ModelsProvider extends Base {
    public function includeDependencies() {
        $dependencies = $this->getDependencies();

        foreach ($dependencies[$this->getContext()] as $dependency) {
            $this->load->model($dependency);
        }
    }

    private function getContext()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            return defined('HTTP_ADMIN') ? 'catalog' : 'admin';
        } else {
            return defined('HTTP_CATALOG') ? 'admin' : 'catalog';
        }
    }

    private function getDependencies() {
        return array(
            'catalog' => array(
                'setting/setting',
                'checkout/order',
                'account/order',
                'account/customer',
                'account/address',
                'localisation/country',
                'localisation/zone',
                'account/address',
                'catalog/product',
            ),
            'admin' => array(
                'setting/setting',
                'sale/order',
                'customer/customer',
                'catalog/product'
            )
        );
    }
}
