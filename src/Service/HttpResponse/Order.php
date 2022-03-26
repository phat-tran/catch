<?php

namespace App\Service\HttpResponse;


/**
 * Class Order represents a row in a list of orders that are fetched from the external URL.
 */
class Order
{
    /**
     * Order id.
     *
     * @var int
     */
    public int $order_id;

    /**
     * Order string.
     *
     * @var string
     */
    public string $order_date;

    /**
     * Customer placed the order.
     *
     * @var Customer
     */
    public Customer $customer;

    /**
     * List of items in this order.
     *
     * @var OrderItem[]
     */
    public array $items = [];

    /**
     * List of discounts for this order.
     *
     * @var Discount[]
     */
    public array $discounts = [];

    /**
     * Order's shipping price.
     *
     * @var float
     */
    public float $shipping_price;
}
