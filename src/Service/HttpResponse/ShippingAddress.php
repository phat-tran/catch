<?php

namespace App\Service\HttpResponse;


/**
 * Class ShippingAddress represents a shipping address.
 */
class ShippingAddress
{
    /**
     * Address's Street.
     *
     * @var string
     */
    public string $street;

    /**
     * Address's Postcode.
     *
     * @var string
     */
    public string $postcode;

    /**
     * Address's Suburb.
     *
     * @var string
     */
    public string $suburb;

    /**
     * Address's State.
     *
     * @var string
     */
    public string $state;
}
