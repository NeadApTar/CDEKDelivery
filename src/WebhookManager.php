<?php

namespace {
    defined('ABSPATH') or exit;
}

namespace Cdek {
    use Cdek\Helpers\Logger;

    class WebhookManager {
        private static CdekApi $api;
        private static string $currentUrl;

        public function __construct() {
            self::$api = new CdekApi();
            self::$currentUrl = site_url('/wp-json/' . Config::DELIVERY_NAME . '/webhook');
        }

        /**
         * Subscribe and save the webhook ID
         */
        public static function add(): void
        {
            try {
                $data = self::$api->addWebhook();

                if (isset($data['uuid'])) {
                    ShippingMethod::factory()->synchronization_webhook = $data['uuid'];
                    Logger::debug('Webhook: Webhook ID saved', $data);
                } else {
                    Logger::debug('Webhook: Failed to get webhook ID');
                }
            } catch (Exceptions\External\ApiException|Exceptions\External\LegacyAuthException $err) {
                Logger::error('Webhook: Webhook error', $err);
            }
        }

        /**
         * Unsubscribe and remove the webhook ID
         */
        public static function delete(): void
        {
            try {
                $webhook = ShippingMethod::factory()->synchronization_webhook;

                if ($webhook) {
                    self::$api->deleteWebhook($webhook);
                    ShippingMethod::factory()->synchronization_webhook = null;
                    Logger::debug('Webhook: Webhook ID deleted', ['id' => $webhook]);
                }
            } catch (Exceptions\External\ApiException|Exceptions\External\LegacyAuthException $e) {
                Logger::error('Webhook: Delete webhook error', $e);
            }
        }

        /**
         * Checks if a webhook with the current URL and UUID exists
         */
        public static function check(): bool
        {
            try {
                $webhooks = self::$api->getAllWebhooks();

                foreach ($webhooks as $webhook) {
                    if ($webhook['url'] === self::$currentUrl && $webhook['uuid'] === ShippingMethod::factory()->synchronization_webhook) {
                        return true;
                    }
                }
            } catch (Exceptions\External\ApiException|Exceptions\External\LegacyAuthException $e) {
                Logger::error('Webhook: Check webhook error', $e);
            }
            return false;
        }

        /**
         * Cleans up unused or duplicate webhooks by removing those that match the current URL
         * but are not associated with the active synchronization webhook.
         */
        public static function cleanupWebhooks(): void
        {
            try {
                $webhooks = self::$api->getAllWebhooks();

                foreach ($webhooks as $webhook) {
                    if ($webhook['url'] === self::$currentUrl && $webhook['uuid'] !== ShippingMethod::factory()->synchronization_webhook) {
                        self::$api->deleteWebhook($webhook['uuid']);
                        Logger::debug('Webhook: Cleaned up webhook', ['id' => $webhook['uuid']]);
                    }
                }
            } catch (Exceptions\External\ApiException|Exceptions\External\LegacyAuthException $e) {
                Logger::error('Webhook: Cleanup error', $e);
            }
        }

        /**
         * Updates the webhook by ensuring its presence. Performs a cleanup of existing webhooks
         * and adds a new one if none exists.
         */
        public static function update(): void
        {
            self::cleanupWebhooks();
            if (!self::check()) {
                self::add();
            }
        }

    }
}
