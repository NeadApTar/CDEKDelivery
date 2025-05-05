<?php

namespace {

    defined('ABSPATH') or exit;
}

namespace Cdek\Actions {

    use Cdek\Helpers\Logger;
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
                return $this->updateOrderStatus();
            }

            return [
                'message' => 'Заказ не найден!'
            ];
        }

        private function getOrder(): bool
        {
            if (empty($this->data['attributes'])) {
                Logger::debug('Webhook: Недопустимые атрибуты запроса.', $this->data);
                return false;
            }

            $order_id = $this->data['attributes']['number'];
            $cdek_number = $this->data['attributes']['cdek_number'];
            $cdek_uuid = $this->data['uuid'];
            $order_prefix = $this->shippingMethod->order_prefix;

            if (!empty($order_prefix)) {
                $order_id = str_replace($order_prefix, '', $order_id);
            }

            $order = wc_get_order($order_id);

            if (!$order) {
                Logger::debug("Webhook: Заказ {$order_id} не найден", [
                    'order_id' => $this->data['attributes']['number'],
                    'cdek_number' => $cdek_number
                ]);
                return false;
            }

            $order_meta = $order->get_meta(self::META_KEY);

            if (!(in_array($cdek_uuid, [$order_meta['order_uuid'], $order_meta['uuid']]) || in_array($cdek_number, [$order_meta['order_number'], $order_meta['number']]))) {
                Logger::debug("Webhook: Некорректный UUID или Track ID для заказа #{$order_id}", [
                    'data_uuid' => $cdek_uuid,
                    'data_number' => $cdek_number,
                    'order_meta' => $order_meta,
                ]);
                return false;
            }

            $this->order = $order;
            return true;
        }

        private function updateOrderStatus(): array
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

            if ($status === 'none') {
                return [
                    'message' => "{$this->data['attributes']['code']} не имеет привязанного статуса для заказа"
                ];
            }

            $order_status = 'wc-' . $this->order->get_status();

            if ($status === $order_status) {
                return [
                    'message' => "Заказ #{$this->order->get_id()} имеет актуальный статус",
                    'status' => [
                        'current' => $order_status,
                        'new' => $status
                    ]
                ];
            }

            $this->order->update_status($status, '[CDEKDelivery]');
            $data = [
                'message' => "Webhook: Заказ #{$this->order->get_id()} синхронизирован",
                'status' => [
                    'old' => $order_status,
                    'new' => $status,
                ]
            ];
            Logger::debug($data['message'], $data['status']);
            return $data;
        }
    }
}
