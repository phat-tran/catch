<?php

namespace App\Service\Model;


/**
 * Class OrderReport represents a report for each order.
 */
class OrderReport
{
    /**
     * Order ID.
     *
     * @var int
     */
    public int $order_id;

    /**
     * Order date and time.
     *
     * @var string
     */
    public string $order_datetime;

    /**
     * Total order value.
     *
     * @var float
     */
    public float $total_order_value;

    /**
     * Average unit price.
     *
     * @var float
     */
    public float $average_unit_price;

    /**
     * Distinct unit count.
     *
     * @var int
     */
    public int $distinct_unit_count;

    /**
     * Total unit count.
     *
     * @var int
     */
    public int $total_units_count;

    /**
     * Customer state.
     *
     * @var string
     */
    public string $customer_state;
}
