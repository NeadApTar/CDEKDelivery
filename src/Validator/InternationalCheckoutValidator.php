<?php

declare(strict_types=1);

namespace {
    defined('ABSPATH') or exit;
}

namespace Cdek\Validator {

    class InternationalCheckoutValidator {

        /**
         * Статическая валидация для checkout
         *
         * @param array    $data   Все данные чекаута
         * @param WP_Error $errors Ошибки валидации
         */
        public static function validate( $data, $errors ) {
            if ( $data['billing_country'] === 'RU' ) return;

            $required_fields = [
                'passport_series'        => __( 'Введите серию паспорта', 'cdekdelivery' ),
                'passport_number'        => __( 'Введите номер паспорта', 'cdekdelivery' ),
                'passport_date_of_issue' => __( 'Укажите дату выдачи паспорта', 'cdekdelivery' ),
                'passport_organization'  => __( 'Укажите организацию, выдавшую паспорт', 'cdekdelivery' ),
                'tin'                    => __( 'Введите ИНН', 'cdekdelivery' ),
                'passport_date_of_birth' => __( 'Укажите дату рождения', 'cdekdelivery' ),
            ];

            foreach ( $required_fields as $field_key => $message ) {
                $value = isset( $_POST[ $field_key ] ) ? trim( (string) wp_unslash( $_POST[ $field_key ] ) ) : '';
                if ( $value === '' ) {
                    $errors->add( $field_key . '_required', $message );
                }
            }

            // простая проверка формата ИНН
            if (!empty($_POST['tin']) && !preg_match('/^([А-Яа-яA-Za-z]{0,2})?[0-9]{7,10}([0-9]{2,4})?$/iu', $_POST['tin'])) {
                $errors->add('tin_invalid', __('ИНН должен состоять из 9, 10, 12 или 14 символов.', 'cdekdelivery'));
            }
        }
    }
}
