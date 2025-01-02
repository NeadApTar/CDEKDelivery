<?php

namespace {

    defined('ABSPATH') or exit;
}

namespace Cdek\Actions {

    use Cdek\ShippingMethod;

    class SyncOrderAction
    {
        private array $data;
        private \WC_Order $order;
        private const META_KEY = 'order_data';
        private ShippingMethod $shippingMethod;

        public function __construct()
        {
            $this->shippingMethod = ShippingMethod::factory();
        }

        public function __invoke(array $dataJson): array
        {
            $this->data = $dataJson;

            if ($this->getOrder()) {
                $this->updateOrderStatus();
            }

            return ['state' => 'OK'];
        }

        private function getOrder(): bool
        {
            if (empty($this->data['attributes'])) {
                wc_get_logger()->debug('Недопустимые атрибуты запроса.', $this->data);
                return false;
            }

            $order_id = $this->data['attributes']['number'];
            $order_prefix = $this->shippingMethod->order_prefix;

            if (!empty($order_prefix)) {
                $order_id = str_replace($order_prefix, '', $order_id);
            }

            $order = wc_get_order($order_id);

            if (!$order) {
                wc_get_logger()->debug("Заказ {$order_id} не найден");
                return false;
            }

            $order_meta = $order->get_meta(self::META_KEY);

            if (!in_array($this->data['uuid'], [$order_meta['order_uuid'], $order_meta['uuid']])) {
                wc_get_logger()->debug("Некорректный UUID для заказа #{$order_id}", [
                    'data_uuid' => $this->data['uuid'],
                    'order_meta' => $order_meta,
                ]);
                return false;
            }

            $this->order = $order;
            return true;
        }

        private function updateOrderStatus()
        {
            $status = 'none';

            switch ($this->data['attributes']['code']) {
                case 'CREATED':
                    $status = $this->shippingMethod->status_exported;
                    break;
                case 'RECEIVED_AT_SHIPMENT_WAREHOUSE':
                    $status = $this->shippingMethod->status_warehouse;
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
                    $status = $this->shippingMethod->status_in_transit;
                    break;
                case 'ACCEPTED_AT_PICK_UP_POINT':
                case 'POSTOMAT_POSTED':
                    $status = $this->shippingMethod->status_in_pvz;
                    break;
                case 'TAKEN_BY_COURIER':
                    $status = $this->shippingMethod->status_courier;
                    break;
                case 'POSTOMAT_RECEIVED':
                case 'DELIVERED':
                    $status = $this->shippingMethod->status_delivered;
                    break;
                case 'POSTOMAT_SEIZED':
                case 'NOT_DELIVERED':
                    $status = $this->shippingMethod->status_returned;
                    break;
            }

            $order_status = 'wc-' . $this->order->get_status();
            if (($status !== $order_status) && ($status !== 'none')) {
                $this->order->update_status($status, '[CDEKDelivery]');
                wc_get_logger()->debug("Заказ #{$this->order->get_id()} синхронизирован", [
                    'old_status' => $order_status,
                    'new_status' => $status,
                ]);
            }
        }
    }
}
