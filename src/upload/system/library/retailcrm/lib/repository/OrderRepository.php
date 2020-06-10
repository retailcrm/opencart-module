<?php

namespace retailcrm\repository;

class OrderRepository extends \retailcrm\Base {
    public function getOrder($order_id) {
        if (null !== $this->model_sale_order) {
            return $this->model_sale_order->getOrder($order_id);
        }

        if (null !== $this->model_checkout_order) {
            return $this->model_checkout_order->getOrder($order_id);
        }

        return array();
    }

    public function getOrderTotals($order_id) {
        if (null !== $this->model_sale_order) {
            return $this->model_sale_order->getOrderTotals($order_id);
        }

        if (null !== $this->model_account_order) {
            return $this->model_account_order->getOrderTotals($order_id);
        }

        return array();
    }
}
