<?php

namespace retailcrm\factory;

use retailcrm\service\RetailcrmOrderConverter;
use retailcrm\service\SettingsManager;
use retailcrm\repository\CustomerRepository;
use retailcrm\repository\ProductsRepository;

class OrderConverterFactory {
    public static function create(\Registry $registry) {
        return new RetailcrmOrderConverter(
            new SettingsManager($registry),
            new CustomerRepository($registry),
            new ProductsRepository($registry)
        );
    }
}
