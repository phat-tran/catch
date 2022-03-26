<?php

namespace App\Service\HttpResponse;


/**
 * Class Discount represents an order's discount.
 */
class Discount
{
    /**
     * Discount type. Should be a value of Enum DiscountType.
     *
     * @var string
     */
    public string $type;

    /**
     * Discount value.
     *
     * @var float
     */
    public float $value;

    /**
     * Discount priority.
     *
     * @var int
     */
    public int $priority;
}
