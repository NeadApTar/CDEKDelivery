<?php

namespace {
    defined('ABSPATH') or exit;
}

namespace Cdek {

    class WebhookManager
    {
        public function __construct() {}

        /**
         * Subscribe and save the webhook ID
         */
        public static function add()
        {
            try {
                $data = (new CdekApi())->addWebhook();

                if ($data && isset($data['uuid'])) {
                    ShippingMethod::factory()->synchronization_webhook = $data['uuid'];
                } else {
                    if ($log = wc_get_logger()) {
                        $log->debug('[CDEKDelivery] Что-то не так. Не удалось получить ID вебхука');
                    }
                }
            } catch (Exceptions\External\ApiException|Exceptions\External\LegacyAuthException $err) {

            }
        }

        /**
         * Unsubscribe and remove the webhook ID
         */
        public static function delete()
        {
            try {
                (new CdekApi())->deleteWebhook();
                ShippingMethod::factory()->synchronization_webhook = null;
            } catch (Exceptions\External\ApiException|Exceptions\External\LegacyAuthException $e) {

            }
        }

        /**
         * Update the webhook ID
         */
        public static function update()
        {
            self::delete();
            self::add();
        }
    }
}
