<?php

namespace retailcrm\factory;

use retailcrm\service\RetailcrmCustomerConverter;
use retailcrm\service\SettingsManager;

class CustomerConverterFactory {
    public static function create(\Registry $registry) {
        return new RetailcrmCustomerConverter(new SettingsManager($registry));
    }
}
