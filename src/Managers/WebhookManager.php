<?php

namespace {
    defined('ABSPATH') or exit;
}

namespace Cdek\Managers{

    use Cdek\CdekApi;
    use Cdek\Exceptions\CdekApiException;
    use Cdek\Helper;

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
                $data = json_decode($data, true);

                if ($data && isset($data['entity']['uuid'])) {
                    Helper::getActualShippingMethod()->update_option('synchronization_webhook', $data['entity']['uuid']);
                } else {
                    throw new RuntimeException('[CDEKDelivery] Что-то не так. Не удалось получить ID вебхука');
                }
            } catch (CdekApiException $e) {

            } catch (\JsonException $e) {

            }
        }

        /**
         * Unsubscribe and remove the webhook ID
         */
        public static function delete()
        {
            try {
                (new CdekApi())->deleteWebhook();
                Helper::getActualShippingMethod()->update_option('synchronization_webhook', null);
            } catch (CdekApiException $e) {

            } catch (\JsonException $e) {

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
