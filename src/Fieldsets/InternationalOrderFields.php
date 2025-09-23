<?php

declare(strict_types=1);

namespace {

    defined('ABSPATH') or exit;
}

namespace Cdek\Fieldsets {

    use Cdek\Contracts\FieldsetContract;
    use Cdek\ShippingMethod;

    class InternationalOrderFields extends FieldsetContract
    {
        final public function isApplicable(): bool
        {
            return ShippingMethod::factory()->international_mode;
        }

        final protected function getFields(): array
        {
            return [
                'passport_series'        => [
                    'priority'          => 120,
                    'label'             => esc_html__('Passport Series', 'cdekdelivery'),
                    'required'          => false,
                    'custom_attributes' => [
                        'maxlength' => 4,
                    ],
                    'class'             => ['form-row-first'],
                ],
                'passport_number'        => [
                    'priority'          => 120,
                    'label'             => esc_html__('Passport number', 'cdekdelivery'),
                    'required'          => false,
                    'custom_attributes' => [
                        'maxlength' => 7,
                    ],
                    'class'             => ['form-row-last'],
                ],
                'passport_date_of_issue' => [
                    'priority' => 120,
                    'type'     => 'date',
                    'label'    => esc_html__('Passport date of issue', 'cdekdelivery'),
                    'required' => false,
                    'class'    => ['form-row-first'],
                ],
                'passport_organization'  => [
                    'priority' => 120,
                    'label'    => esc_html__('Passport organization', 'cdekdelivery'),
                    'required' => false,
                    'class'    => ['form-row-last'],
                ],
                'tin'                    => [
                    'priority'          => 120,
                    'label'             => esc_html__('TIN', 'cdekdelivery'),
                    'required'          => false,
                    'custom_attributes' => [
                        'maxlength' => 14,
                    ],
                    'class'             => ['form-row-first'],
                ],
                'passport_date_of_birth' => [
                    'priority' => 120,
                    'type'     => 'date',
                    'label'    => esc_html__('Birthday', 'cdekdelivery'),
                    'required' => false,
                    'class'    => ['form-row-last'],
                ],
            ];
        }
    }
}
