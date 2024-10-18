<?php

namespace {

    defined('ABSPATH') or exit;
}

namespace Cdek\Actions {

    use Cdek\CdekApi;
    use Cdek\Config;
    use Cdek\Helper;
    use RuntimeException;

    class SyncOrderAction
    {
        private CdekApi $api;
        private array $data;
        private \WC_Order $order;

        public function __construct()
        {
            $this->api = new CdekApi;
        }

        public function __invoke(array $dataJson): array
        {
            $this->data = $dataJson;

            $this->getOrder();
            $this->updateOrderStatus();

            return ['state' => 'OK'];
        }

        private function getOrder()
        {
            if (empty($this->data['attributes'])) {
                throw new RuntimeException('[CDEKDelivery] Недопустимые атрибуты запроса.');
            }

            $order_id = $this->data['attributes']['number'];
            $order_prefix = Helper::getActualShippingMethod()->get_option('order_prefix');

            if (!empty($order_prefix)) {
                $order_id = str_replace($order_prefix, '', $order_id);
            }

            $order = wc_get_order($order_id);

            if (!$order) {
                throw new RuntimeException(sprintf('[CDEKDelivery] Заказ %s не найден', $order_id ));
            }
            if ($this->data['uuid'] !== $order->get_meta(Config::META_KEY)['order_uuid']) {
                throw new RuntimeException('[CDEKDelivery] Некорректный UUID заказа.');
            }

            $this->order = $order;
        }

        private function updateOrderStatus()
        {
            $status = 'none';

            switch ($this->data['attributes']['code']) {
                case 'CREATED':
                    $status = Helper::getActualShippingMethod()->get_option('status_exported');
                    break;
                case 'RECEIVED_AT_SHIPMENT_WAREHOUSE':
                    $status = Helper::getActualShippingMethod()->get_option('status_warehouse');
                    break;
                case 'READY_TO_SHIP_AT_SENDING_OFFICE':
                case 'READY_FOR_SHIPMENT_IN_TRANSIT_CITY':
                case 'READY_FOR_SHIPMENT_IN_SENDER_CITY':
                case 'RETURNED_TO_SENDER_CITY_WAREHOUSE':
                case 'TAKEN_BY_TRANSPORTER_FROM_SENDER_CITY':
                case 'SENT_TO_TRANSIT_CITY':
                case 'ACCEPTED_IN_TRANSIT_CITY':
                case 'ACCEPTED_AT_TRANSIT_WAREHOUSE':
                case 'RETURNED_TO_TRANSIT_WAREHOUSE':
                case 'READY_TO_SHIP_IN_TRANSIT_OFFICE':
                case 'TAKEN_BY_TRANSPORTER_FROM_TRANSIT_CITY':
                case 'SENT_TO_SENDER_CITY':
                case 'SENT_TO_RECIPIENT_CITY':
                case 'ACCEPTED_IN_SENDER_CITY':
                case 'ACCEPTED_IN_RECIPIENT_CITY':
                case 'ACCEPTED_AT_RECIPIENT_CITY_WAREHOUSE':
                case 'IN_CUSTOMS_INTERNATIONAL':
                case 'SHIPPED_TO_DESTINATION':
                case 'PASSED_TO_TRANSIT_CARRIER':
                case 'IN_CUSTOMS_LOCAL':
                case 'CUSTOMS_COMPLETE':
                    $status = Helper::getActualShippingMethod()->get_option('status_in_transit');
                    break;
                case 'ACCEPTED_AT_PICK_UP_POINT':
                case 'POSTOMAT_POSTED':
                    $status = Helper::getActualShippingMethod()->get_option('status_in_pvz');
                    break;
                case 'TAKEN_BY_COURIER':
                    $status = Helper::getActualShippingMethod()->get_option('status_courier');
                    break;
                case 'POSTOMAT_RECEIVED':
                case 'DELIVERED':
                    $status = Helper::getActualShippingMethod()->get_option('status_delivered');
                    break;
                case 'POSTOMAT_SEIZED':
                case 'NOT_DELIVERED':
                    $status = Helper::getActualShippingMethod()->get_option('status_returned');
                    break;
            }

            $order_status = 'wc-' . $this->order->get_status();
            if (($status !== $order_status) && ($status !== 'none')) {
                $this->order->update_status($status, '[CDEKDelivery]');
            }
        }
    }
}
