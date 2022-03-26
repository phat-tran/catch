<?php

namespace App\Service\HttpResponse;

/**
 * Class OrderItem represents an item inside an order.
 */
class OrderItem
{
    /**
     * Item Quantity.
     *
     * @var int
     */
    public int $quantity;

    /**
     * Item Unit Price.
     *
     * @var float
     */
    public float $unit_price;

    /**
     * Order Product Item.
     *
     * @var Product
     */
    public Product $product;
}
