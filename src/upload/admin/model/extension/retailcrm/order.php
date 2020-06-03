<?php

class ModelExtensionRetailcrmOrder extends Model {
    /**
     * Upload orders to CRM
     *
     * @param array $orders
     *
     * @return mixed
     */
    public function uploadToCrm($orders)
    {
        $ordersToCrm = array();
        /** @var OrderManager $order_manager */
        $order_manager = $this->retailcrm->getOrderManager();

        foreach ($orders as $order) {
            $ordersToCrm[] = $order_manager->prepareOrder($order, $order['products'], $order['totals']);
        }

        $chunkedOrders = array_chunk($ordersToCrm, 50);

        foreach($chunkedOrders as $ordersPart) {
            $order_manager->uploadOrders($ordersPart);
        }

        return $chunkedOrders;
    }
}
