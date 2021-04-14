<?php

namespace retailcrm\history;

use retailcrm\repository\DataRepository;
use retailcrm\repository\OrderRepository;
use retailcrm\repository\ProductsRepository;
use retailcrm\Retailcrm;
use retailcrm\service\SettingsManager;

class Order {
    private $data_repository;
    private $settings_manager;
    private $product_repository;
    private $order_repository;

    private $payment;
    private $delivery;
    private $oc_payment;
    private $oc_delivery;

    public function __construct(
        DataRepository $data_repository,
        SettingsManager $settings_manager,
        ProductsRepository $product_repository,
        OrderRepository $order_repository
    ) {
        $this->data_repository = $data_repository;
        $this->settings_manager = $settings_manager;
        $this->product_repository = $product_repository;
        $this->order_repository = $order_repository;

        $this->payment = array_flip($settings_manager->getPaymentSettings());
        $this->delivery = array_flip($settings_manager->getDeliverySettings());
    }

    public function setOcPayment($oc_payment) {
        $this->oc_payment = $oc_payment;
    }

    public function setOcDelivery($oc_delivery) {
        $this->oc_delivery = $oc_delivery;
    }

    /**
     * @param array $data opencart order
     * @param array $order RetailCRM order
     */
    public function handleBaseOrderData(&$data, $order) {
        $mail = !empty($order['email']) ? $order['email'] : $order['customer']['email'];
        $phone = !empty($order['phone']) ? $order['phone'] : '';

        if (!$phone) {
            $data['telephone'] = $order['customer']['phones'] ? $order['customer']['phones'][0]['number'] : '80000000000';
        } else {
            $data['telephone'] = $phone;
        }

        $data['currency_code'] = $this->data_repository->getConfig('config_currency');
        $data['currency_value'] = $this->data_repository->getCurrencyByCode($data['currency_code'], 'value');
        $data['currency_id'] = $this->data_repository->getCurrencyByCode($data['currency_code'], 'currency_id');
        $data['language_id'] = $this->data_repository->getLanguageByCode(
            $this->data_repository->getConfig('config_language'),
            'language_id'
        );
        $data['store_id'] = !is_null($this->data_repository->getConfig('config_store_id'))
            ? $this->data_repository->getConfig('config_store_id') : 0;
        $data['store_name'] = $this->data_repository->getConfig('config_name');
//        $data['customer_id'] = $customer_id;
        $data['customer_group_id'] = 1;
        $data['firstname'] = $order['firstName'];
        $data['lastname'] = (!empty($order['lastName'])) ? $order['lastName'] : $order['firstName'];
        $data['email'] = !empty($mail) ? $mail : uniqid() . '@retailrcm.ru';
        $data['comment'] = !empty($order['customerComment']) ? $order['customerComment'] : '';

        // this data will not retrive from crm for now
        $data['fax'] = '';
        $data['tax'] = '';
        $data['tax_id'] = '';
        $data['product'] = '';
        $data['product_id'] = '';
        $data['reward'] = '';
        $data['affiliate'] = '';
        $data['affiliate_id'] = 0;
        $data['payment_tax_id'] = '';
        $data['order_product_id'] = '';
        $data['payment_company'] = '';
    }

    /**
     * @param array $data opencart order
     * @param array $order RetailCRM order
     * @param array $corporateAddress
     */
    public function handlePayment(&$data, $order, $corporateAddress = array()) {
        if (!empty($order['customer']['type']) && $order['customer']['type'] === 'customer_corporate') {
            $customer = $order['contact'];

            if (empty($customer['address'])) {
                $customer['address'] = $corporateAddress;
            }
            if (empty($customer['address'])) {
                $customer['address'] = $order['delivery']['address'];
            }
        } else {
            $customer = $order['customer'];
        }

        $default_payment_country = !empty($data['payment_country']) ? $data['payment_country'] : '';
        $default_payment_country_id = !empty($data['payment_country_id']) ? $data['payment_country_id'] : 0;
        $default_payment_zone = !empty($data['payment_zone']) ? $data['payment_zone'] : '';
        $default_payment_zone_id = !empty($data['payment_zone_id']) ? $data['payment_zone_id'] : 0;
        if (isset($customer['address']['countryIso'])) {
            $payment_country = $this->data_repository->getCountryByIsoCode($customer['address']['countryIso']);
        }

        if (isset($customer['address']['region'])) {
            $payment_zone = $this->data_repository->getZoneByName($customer['address']['region']);

            if ($payment_zone) {
                $payment_zone_id = $payment_zone['zone_id'];
            }
        }

        $data['payment_firstname'] = $customer['firstName'];
        $data['payment_lastname'] = (isset($customer['lastName'])) ? $customer['lastName'] : $customer['firstName'];
        $data['payment_address_2'] = '';

        if (!empty($order['company'])) {
            $data['payment_company'] = $order['company']['name'];
        }

        if (!empty($customer['address'])) {
            $data['payment_address_1'] = $customer['address']['text'];
            $data['payment_city'] = !empty($customer['address']['city'])
                ? $customer['address']['city']
                : $order['delivery']['address']['city'];
            $data['payment_postcode'] = !empty($customer['address']['index'])
                ? $customer['address']['index']
                : $order['delivery']['address']['index'];
        }

        $data['payment_country_id'] = !empty($payment_country['country_id']) ? $payment_country['country_id'] : $default_payment_country_id;
        $data['payment_country'] = !empty($payment_country['name']) ? $payment_country['name'] : $default_payment_country;
        $data['payment_zone'] = isset($customer['address']['region']) ? $customer['address']['region'] : $default_payment_zone;
        $data['payment_zone_id'] = isset($payment_zone_id) ? $payment_zone_id : $default_payment_zone_id;

        if (isset($order['payments']) && $order['payments']) {
            $payment = end($order['payments']);
            $data['payment_method'] = $this->oc_payment[$this->payment[$payment['type']]];
            $data['payment_code'] = $this->payment[$payment['type']];
        } elseif (empty($data['payment_code']) && empty($data['payment_method'])) {
            $data['payment_method'] = $this->oc_payment[$this->settings_manager->getSetting('default_payment')];
            $data['payment_code'] = $this->settings_manager->getSetting('default_payment');
        }
    }

    /**
     * @param array $data opencart order
     * @param array $order RetailCRM order
     */
    public function handleShipping(&$data, $order) {
        $default_shipping_country = !empty($data['shipping_country']) ? $data['shipping_country'] : '';
        $default_shipping_country_id = !empty($data['shipping_country_id']) ? $data['shipping_country_id'] : 0;
        $default_shipping_zone = !empty($data['shipping_zone']) ? $data['shipping_zone'] : '';
        $default_shipping_zone_id = !empty($data['shipping_zone_id']) ? $data['shipping_zone_id'] : 0;
        if (!empty($order['delivery']['address']['region'])) {
            $shipping_zone = $this->data_repository->getZoneByName($order['delivery']['address']['region']);
        }

        if (isset($order['countryIso'])) {
            $shipping_country = $this->data_repository->getCountryByIsoCode($order['countryIso']);
        }

        $delivery = isset($order['delivery']['code']) ? $order['delivery']['code'] : null;

        $data['shipping_country_id'] = isset($shipping_country['country_id']) ? $shipping_country['country_id'] : $default_shipping_country_id;
        $data['shipping_country'] = isset($shipping_country['name']) ? $shipping_country['name'] : $default_shipping_country;
        $data['shipping_zone_id'] = isset($shipping_zone['zone_id']) ? $shipping_zone['zone_id'] : $default_shipping_zone_id;
        $data['shipping_zone'] = isset($shipping_zone['name']) ? $shipping_zone['name'] : $default_shipping_zone;
        $data['shipping_firstname'] = $order['firstName'];
        $data['shipping_lastname'] = (isset($order['lastName'])) ? $order['lastName'] : $order['firstName'];
        $data['shipping_address_1'] = $order['delivery']['address']['text'];
        $data['shipping_address_2'] = '';
        $data['shipping_company'] = '';
        $data['shipping_city'] = $order['delivery']['address']['city'];
        $data['shipping_postcode'] = $order['delivery']['address']['index'];

        if (!isset($data['shipping_code'])) {
            $data['shipping_code'] = $delivery != null
                ? $this->delivery[$delivery]
                : $this->settings_manager->getSetting('default_shipping');

            $shipping = explode('.', $data['shipping_code']);
            $shipping_module = $shipping[0];

            if (isset($this->oc_delivery[$shipping_module][$data['shipping_code']]['title'])) {
                $data['shipping_method'] = $this->oc_delivery[$shipping_module][$data['shipping_code']]['title'];
            } else {
                $data['shipping_method'] = $this->oc_delivery[$shipping_module]['title'];
            }
        } else {
            if ($delivery !== null) {
                if (isset($this->settings_manager->getDeliverySettings()[$data['shipping_code']])
                    && isset($this->delivery[$delivery])
                ) {
                    $data['shipping_code'] = $this->delivery[$delivery];

                    $shipping = explode('.', $data['shipping_code']);
                    $shippingModule = $shipping[0];

                    if (isset($this->oc_delivery[$shippingModule][$data['shipping_code']]['title'])) {
                        $data['shipping_method'] = $this->oc_delivery[$shippingModule][$data['shipping_code']]['title'];
                    } else {
                        $data['shipping_method'] = $this->oc_delivery[$shippingModule]['title'];
                    }
                }
            }
        }
    }

    /**
     * @param array $data opencart order
     * @param array $order RetailCRM order
     */
    public function handleProducts(&$data, $order) {
        $data['order_product'] = array();

        foreach ($order['items'] as $item) {
            $product_id = $item['offer']['externalId'];
            $options = array();

            if (mb_strpos($item['offer']['externalId'], '#', 0, mb_internal_encoding()) > 1) {
                $offer = explode('#', $item['offer']['externalId']);
                $product_id = $offer[0];
                $options_from_crm = explode('_', $offer[1]);

                foreach ($options_from_crm as $option_from_crm) {
                    $option_data = explode('-', $option_from_crm);
                    $product_option_id = $option_data[0];
                    $option_value_id = $option_data[1];

                    $product_options = $this->product_repository->getProductOptions($product_id);

                    foreach ($product_options as $product_option) {
                        if ($product_option_id == $product_option['product_option_id']) {
                            foreach ($product_option['product_option_value'] as $product_option_value) {
                                if ($product_option_value['option_value_id'] == $option_value_id) {
                                    $options[] = array(
                                        'product_option_id' => $product_option_id,
                                        'product_option_value_id' => $product_option_value['product_option_value_id'],
                                        'value' => $this->data_repository->getOptionValue($product_option_value['option_value_id'], 'name'),
                                        'type' => $product_option['type'],
                                        'name' => $product_option['name'],
                                    );
                                }
                            }
                        }
                    }
                }
            }

            $product = $this->product_repository->getProduct($product_id);
            $rewards = $this->product_repository->getProductRewards($product_id);

            $data['order_product'][] = array(
                'name' => $product['name'],
                'model' => $product['model'],
                'price' => $item['initialPrice'],
                'total' => (float)($item['initialPrice'] * $item['quantity']),
                'product_id' => $product_id,
                'quantity' => $item['quantity'],
                'option' => $options,
                'reward' => $rewards[$data['customer_group_id']]['points'] * $item['quantity']
            );
        }
    }

    /**
     * @param array $data opencart order
     * @param array $order RetailCRM order
     */
    public function handleTotals(&$data, $order) {
        $delivery_cost = !empty($order['delivery']['cost']) ? $order['delivery']['cost'] : 0;

        $subtotal_settings = $this->settings_manager->getSettingByKey($this->data_repository->totalTitles() . 'sub_total');
        $total_settings = $this->settings_manager->getSettingByKey($this->data_repository->totalTitles() . 'total');
        $shipping_settings = $this->settings_manager->getSettingByKey($this->data_repository->totalTitles() . 'shipping');
        $retailcrm_label_discount = $this->settings_manager->getSetting('label_discount')
            ?: $this->data_repository->getLanguage('default_retailcrm_label_discount');

        $totalDiscount = 0;
        foreach ($order['items'] as $item) {
            if ($item['discountTotal'] !==  0) {
                $totalDiscount += $item['discountTotal'] * $item['quantity'];
            }
        }

        $data['total'] = $order['totalSumm'];
        $data['order_total'] = array(
            array(
                'order_total_id' => '',
                'code' => 'sub_total',
                'title' => $this->data_repository->getLanguage('product_summ'),
                'value' => $order['summ'],
                'text' => $order['summ'],
                'sort_order' => $subtotal_settings['sub_total_sort_order']
            ),
            array(
                'order_total_id' => '',
                'code' => 'shipping',
                'title' => $data['shipping_method'],
                'value' => $delivery_cost,
                'text' => $delivery_cost,
                'sort_order' => $shipping_settings[$this->data_repository->totalTitles() . 'shipping_sort_order']
            ),
            array(
                'order_total_id' => '',
                'code' => 'total',
                'title' => $this->data_repository->getLanguage('column_total'),
                'value' => !empty($order['totalSumm']) ? $order['totalSumm'] : $order['summ'] + $delivery_cost,
                'text' => isset($order['totalSumm']) ? $order['totalSumm'] : $order['summ'] + $delivery_cost,
                'sort_order' => $total_settings[$this->data_repository->totalTitles() . 'total_sort_order']
            )
        );

        //TODO подкорректировать логику добавления скидки из RetailCRM
        //Если заказ создали со скидкой в RetailCRM, то добавить скидку
        if (!empty($totalDiscount)) {
            $data['order_total'][] = array(
                'order_total_id' => '',
                'code' => Retailcrm::RETAILCRM_DISCOUNT,
                'title' => $retailcrm_label_discount,
                'value' => -$totalDiscount,
                'sort_order' =>  Retailcrm::RETAILCRM_DISCOUNT_SORT_ORDER,
            );
        }

        if (!empty($order['externalId'])) {
            $orderTotals = $this->order_repository->getOrderTotals($order['externalId']);

            foreach ($orderTotals as $orderTotal) {
                if ($orderTotal['code'] == 'coupon'
                    || $orderTotal['code'] == 'reward'
                    || $orderTotal['code'] == 'voucher'
                ) {
                    $data['order_total'][] = $orderTotal;
                    $totalDiscount -= abs($orderTotal['value']);
                }
            }

            //TODO подкорректировать логику добавления скидки из RetailCRM
            $keyRetailCrmDiscount = array_search(Retailcrm::RETAILCRM_DISCOUNT, array_map(function ($item) {
                return $item['code'];
            }, $data['order_total']));

            if ($totalDiscount > 0 && false !== $keyRetailCrmDiscount) {
                $data['order_total'][$keyRetailCrmDiscount]['value'] = -$totalDiscount;
            } elseif ($totalDiscount <= 0  && false !== $keyRetailCrmDiscount) {
                unset($data['order_total'][$keyRetailCrmDiscount]);
            }
        }
    }

    /**
     * @param array $data opencart order
     * @param array $order RetailCRM order
     */
    public function handleCustomFields(&$data, $order) {
        $settings = $this->settings_manager->getSetting('custom_field');
        if (!empty($settings)) {
            $custom_field_setting = array_flip($settings);
        }

        if (isset($custom_field_setting) && $order['customFields']) {
            foreach ($order['customFields'] as $code => $value) {
                if (array_key_exists($code, $custom_field_setting)) {
                    $field_code = str_replace('o_', '', $custom_field_setting[$code]);
                    $custom_fields[$field_code] = $value;
                }
            }

            $data['custom_field'] = isset($custom_fields) ? $custom_fields : '';
        }
    }
}
