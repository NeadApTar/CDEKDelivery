<?php

defined('ABSPATH') or exit;

/**
 * @var \Cdek\Model\Order $order
 * @var array $items
 */

$items = [];
foreach ($order->items as $item) {
    $items[$item['product_id']] = ['name' => $item['name'], 'qty' => $item['quantity']];
}
?>

<div class="create" aria-invalid="true">
    <div>
        <h3><?php esc_html_e("Packaging dimensions", 'cdekdelivery') ?> №1</h3>
        <select>
            <option value="-1">
                <?php esc_html_e("Select product", 'cdekdelivery') ?>
            </option>
            <?php foreach ($items as $key => $item): ?>
                <option value="<?php esc_attr_e($key) ?>">
                    <?php esc_html_e($item['name']) ?>
                </option>
            <?php endforeach ?>
        </select>
        <?php foreach ($items as $id => $item): ?>
            <div class="item" aria-hidden="true" data-id="<?php esc_attr_e($id) ?>">
                <span><?php esc_html_e($item['name']) ?></span>
                <span>x</span>
                <input name="qty" type="number" min="1" max="<?php esc_attr_e($item['qty']) ?>" value="1" />
            </div>
        <?php endforeach ?>
        <div>
            <p class="form-field form-field-wide">
                <input name="length" type="text" placeholder="<?php esc_attr_e('Length in cm', 'cdekdelivery') ?>">
            </p>
            <p class="form-field form-field-wide">
                <input name="width" type="text" placeholder="<?php esc_attr_e('Width in cm', 'cdekdelivery') ?>">
            </p>
            <p class="form-field form-field-wide">
                <input name="height" type="text" placeholder="<?php esc_html_e('Height in cm', 'cdekdelivery') ?>">
            </p>
        </div>
        <button type="button" class="button package">
            <?php esc_html_e('Save', 'cdekdelivery') ?>
        </button>
    </div>

    <div class="list"></div>

    <button type="button" class="button" data-id="<?php echo esc_attr($order->id) ?>">
        <?php esc_html_e('Sync to CDEK', 'cdekdelivery') ?>
    </button>
</div>
