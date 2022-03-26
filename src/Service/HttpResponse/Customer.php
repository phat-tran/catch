<?php

namespace App\Service\HttpResponse;


/**
 * Class Customer represents a customer in an order.
 */
class Customer
{
    /**
     * Customer ID.
     *
     * @var int
     */
    public int $customer_id;

    /**
     * Customer First Name.
     *
     * @var string
     */
    public string $first_name;

    /**
     * Customer Last Name.
     *
     * @var string
     */
    public string $last_name;

    /**
     * Customer Email.
     *
     * @var string
     */
    public string $email;

    /**
     * Customer Phone.
     *
     * @var string
     */
    public string $phone;

    /**
     * Customer Shipping Address.
     *
     * @var ShippingAddress
     */
    public ShippingAddress $shipping_address;
}
