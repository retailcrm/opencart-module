<?php

namespace retailcrm\service;

use retailcrm\repository\CustomerRepository;
use retailcrm\repository\ProductsRepository;

class RetailcrmOrderConverter {
    protected $data;
    protected $order_data = array();
    protected $order_products = array();
    protected $order_totals = array();

    protected $settingsManager;
    protected $customerRepository;
    protected $productsRepository;

    public function __construct(
        SettingsManager $settingsManager,
        CustomerRepository $customerRepository,
        ProductsRepository $productsRepository
    ) {
        $this->settingsManager = $settingsManager;
        $this->customerRepository = $customerRepository;
        $this->productsRepository = $productsRepository;
    }

    public function initOrderData($order_data, $order_products, $order_totals) {
        $this->data = array();
        $this->order_data = $order_data;
        $this->order_products = $order_products;
        $this->order_totals = $order_totals;

        return $this;
    }

    public function getOrder() {
        return $this->data;
    }

    public function setOrderData() {
        if (!empty($this->order_data['shipping_iso_code_2'])) {
            $this->data['countryIso'] = $this->order_data['shipping_iso_code_2'];
        }

        if ($this->settingsManager->getSetting('order_number') == 1) {
            $this->data['number'] = $this->order_data['order_id'];
        }

        if ($this->settingsManager->getSetting('summ_around') == 1) {
            $this->data['applyRound'] = true;
        }

        $this->data['externalId'] = $this->order_data['order_id'];
        $this->data['firstName'] = $this->order_data['shipping_firstname'];
        $this->data['lastName'] = $this->order_data['shipping_lastname'];
        $this->data['phone'] = $this->order_data['telephone'];
        $this->data['customerComment'] = $this->order_data['comment'];

        if (!empty($this->order_data['customer_id'])) {
            $this->data['customer']['externalId'] = $this->order_data['customer_id'];
        }

        if (!empty($this->order_data['email'])) {
            $this->data['email'] = $this->order_data['email'];
        }

        $this->data['createdAt'] = $this->order_data['date_added'];

        if (!empty($this->order_data['order_status_id'])) {
            $this->data['status'] = $this->settingsManager->getStatusSettings()[$this->order_data['order_status_id']];
        } elseif (isset($this->order_data['order_status_id']) && $this->order_data['order_status_id'] == 0) {
            $this->data['status'] = $this->settingsManager->getSetting('missing_status');
        }

        return $this;
    }

    public function setDiscount() {
        $discount = 0;
        $totalCoupon = $this->getTotal('coupon');
        $totalReward = $this->getTotal('reward');
        $totalVoucher = $this->getTotal('voucher');
        $retailcrmDiscount = $this->getTotal(\retailcrm\Retailcrm::RETAILCRM_DISCOUNT);

        if (!empty($totalCoupon)) {
            $discount += abs($totalCoupon);
        }

        if (!empty($totalReward)) {
            $discount += abs($totalReward);
        }

        if (!empty($totalVoucher)) {
            $discount += abs($totalVoucher);
        }

        if (!empty($retailcrmDiscount)) {
            $discount += abs($retailcrmDiscount);
        }

        $this->data['discountManualAmount'] = $discount;

        return $this;
    }

    public function setPayment($create = true) {
        $settings = $this->settingsManager->getPaymentSettings();
        if (!empty($this->order_data['payment_code']) && isset($settings[$this->order_data['payment_code']])) {
            $payment_type = $settings[$this->order_data['payment_code']];
        }

        $payment = array(
            'externalId' => uniqid($this->order_data['order_id'] . "-"),
            'amount' => $this->getTotal('total')
        );

        if ($this->settingsManager->getSetting('sum_payment') &&
            $this->settingsManager->getSetting('sum_payment') == 1) {
            unset($payment['amount']);
        }

        if (!empty($payment_type)) {
            $payment['type'] = $payment_type;
        }

        if (!$create) {
            $payment['order'] = array(
                'externalId' => $this->order_data['order_id']
            );
        }

        if (!empty($payment['type'])) {
            $this->data['payments'][] = $payment;
        }

        return $this;
    }

    public function setDelivery() {
        $settings = $this->settingsManager->getDeliverySettings();

        $this->data['delivery']['address'] = array(
            'index' => $this->order_data['shipping_postcode'],
            'city' => $this->order_data['shipping_city'],
            'countryIso' => $this->order_data['shipping_iso_code_2'],
            'region' => $this->order_data['shipping_zone'],
            'text' => implode(', ', array(
                $this->order_data['shipping_postcode'],
                $this->order_data['shipping_country'] ? $this->order_data['shipping_country'] : '',
                $this->order_data['shipping_city'],
                $this->order_data['shipping_address_1'],
                $this->order_data['shipping_address_2']
            ))
        );

        if (!empty($this->order_data['shipping_code'])) {
            $shippingCode = explode('.', $this->order_data['shipping_code']);
            $shippingModule = $shippingCode[0];

            if (isset($settings[$this->order_data['shipping_code']])) {
                $delivery_code = $settings[$this->order_data['shipping_code']];
            } elseif (isset($settings[$shippingModule])) {
                $delivery_code = $settings[$shippingModule];
            }
        }

        if (!isset($delivery_code) && isset($shippingModule)) {
            if (!empty($settings)) {
                $deliveries = array_keys($settings);
                $shipping_code = '';

                array_walk($deliveries, function ($item, $key) use ($shippingModule, &$shipping_code) {
                    if (strripos($item, $shippingModule) !== false) {
                        $shipping_code = $item;
                    }
                });

                $delivery_code = $settings[$shipping_code];
            }
        }

        if (!empty($delivery_code)) {
            $this->data['delivery']['code'] = $delivery_code;
        }

        $totalShipping = $this->getTotal('shipping');

        if (!empty($totalShipping)) {
            $this->data['delivery']['cost'] = $totalShipping;
        }

        return $this;
    }

    public function setItems() {
        $offerOptions = array('select', 'radio');

        foreach ($this->order_products as $product) {
            $offerId = '';

            if (!empty($product['option'])) {
                $options = array();

                foreach ($product['option'] as $option) {
                    if ($option['type'] == 'checkbox') {
                        $properties[] = array(
                            'code' => $option['product_option_value_id'],
                            'name' => $option['name'],
                            'value' => $option['value']
                        );
                    }

                    if (!in_array($option['type'], $offerOptions)) continue;

                    $productOptions = $this->productsRepository->getProductOptions($product['product_id']);

                    foreach ($productOptions as $productOption) {
                        if ($productOption['product_option_id'] = $option['product_option_id']) {
                            foreach ($productOption['product_option_value'] as $productOptionValue) {
                                if ($productOptionValue['product_option_value_id'] == $option['product_option_value_id']) {
                                    $options[$option['product_option_id']] = $productOptionValue['option_value_id'];
                                }
                            }
                        }
                    }
                }

                ksort($options);

                $offerId = array();
                foreach ($options as $optionKey => $optionValue) {
                    $offerId[] = $optionKey . '-' . $optionValue;
                }
                $offerId = implode('_', $offerId);
            }

            $item = array(
                'offer' => array(
                    'externalId' => !empty($offerId) ? $product['product_id'] . '#' . $offerId : $product['product_id']
                ),
                'productName' => $product['name'],
                'initialPrice' => $product['price'],
                'quantity' => $product['quantity']
            );

            $date = date('Y-m-d');
            $always = '0000-00-00';
            $specials = $this->productsRepository->getProductSpecials($product['product_id']);

            if (!empty($specials)) {
                $customer = $this->customerRepository->getCustomer($this->order_data['customer_id']);

                foreach ($specials as $special) {
                    if (($special['date_start'] == $always && $special['date_end'] == $always)
                        || ($special['date_start'] <= $date && $special['date_end'] >= $date)
                    ) {
                        if ((isset($priority) && $priority > $special['priority'])
                            || !isset($priority)) {
                            if (empty($customer['customer_group_id'])) {
                                continue;
                            }

                            $specialSetting = $this->settingsManager->getSetting('special_' . $customer['customer_group_id']);
                            if ($special['customer_group_id'] == $customer['customer_group_id'] && !empty($specialSetting)) {
                                $item['priceType']['code'] = $specialSetting;
                                $priority = $special['priority'];
                            }
                        }
                    }
                }
            }

            if (isset($properties)) $item['properties'] = $properties;

            $this->data['items'][] = $item;
        }

        return $this;
    }

    public function setCorporateCustomer($order, $corp_customer_id) {
        $order['contragent']['contragentType'] = 'legal-entity';
        $order['contact'] = $order['customer'];
        unset($order['customer']);
        $order['customer'] = array(
            'id' => $corp_customer_id
        );

        return $order;
    }

    public function setCustomFields() {
        $settings = $this->settingsManager->getSetting('custom_field');
        if (!empty($settings) && $this->order_data['custom_field']) {
            $customFields = $this->order_data['custom_field'];

            foreach ($customFields as $key => $value) {
                if (isset($settings['o_' . $key])) {
                    $customFieldsToCrm[$settings['o_' . $key]] = $value;
                }
            }

            if (isset($customFieldsToCrm)) {
                $this->data['customFields'] = $customFieldsToCrm;
            }
        }

        return $this;
    }

    private function getTotal($total) {
        $totals = $this->getTotals();

        if (!empty($totals[$total])) {
            return $totals[$total];
        }

        return 0;
    }

    private function getTotals() {
        $totals = array();

        foreach ($this->order_totals as $total) {
            $totals[$total['code']] = $total['value'];
        }

        return $totals;
    }
}
