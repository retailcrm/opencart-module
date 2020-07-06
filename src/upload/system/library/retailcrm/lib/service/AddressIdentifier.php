<?php

namespace retailcrm\service;

class AddressIdentifier {
    public static function createAddressExternalId($customer, $address) {
        return $customer['id'] . '_' . $address['address_id'];
    }

    public static function getAddressId($addressExternalId) {
        $pieces = explode('_', $addressExternalId);

        return !empty($pieces[1]) ? $pieces[1] : '';
    }
}
