<?php

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

            $cityInput     = CheckoutHelper::getValueFromCurrentSession('city');
            $postcodeInput = CheckoutHelper::getValueFromCurrentSession('postcode');

            if (empty($cityInput)) {
                return;
            }

            $period = $shippingMethodCurrent->get_meta_data()[MetaKeys::PERIOD];

            include __DIR__.'/../../templates/public/delivery-days.php';
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
    }
}
