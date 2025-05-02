<?php

declare(strict_types=1);

namespace {

    defined('ABSPATH') or exit;
}

namespace Cdek\UI {

    use Cdek\Config;
    use Cdek\Helpers\CheckoutHelper;
    use Cdek\MetaKeys;

    class CheckoutDays
    {
        public function __invoke($shippingMethodCurrent): void
        {
            if (!is_checkout() || !$this->isTariffDestinationCdek($shippingMethodCurrent)) {
                return;
            }

            $cityInput = CheckoutHelper::getCurrentValue('city');

            if (empty($cityInput)) {
                return;
            }

            $period = $shippingMethodCurrent->get_meta_data()[MetaKeys::PERIOD];

            echo "Срок доставки: {$this->formatPeriodDays($period)}";
        }

        private function isTariffDestinationCdek($shippingMethodCurrent): bool
        {
            if ($shippingMethodCurrent->get_method_id() !== Config::DELIVERY_NAME) {
                return false;
            }

            $shippingMethodIdSelected = WC()->session->get('chosen_shipping_methods', []);

            if (empty($shippingMethodIdSelected[0]) ||
                $shippingMethodCurrent->get_id() !== $shippingMethodIdSelected[0]) {
                return false;
            }

            $tariffCode = explode(':', $shippingMethodIdSelected[0])[1];

            return !!$tariffCode;
        }

        private function formatPeriodDays(string $period): string
        {
            if (strpos($period, '-') !== false) {
                $days = explode('-', $period);
                $lastNumber = (int)$days[1];
            } else {
                $lastNumber = (int)$period;
            }

            if ($lastNumber === 1) {
                $suffix = 'день';
            } elseif ($lastNumber >= 2 && $lastNumber <= 4) {
                $suffix = 'дня';
            } else {
                $suffix = 'дней';
            }

            return "{$period} {$suffix}";
        }

    }
}
