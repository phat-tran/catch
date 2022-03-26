<?php

namespace App\Service\HttpResponse;

/**
 * Class Product represents a product.
 */
class Product
{
    /**
     * Product ID.
     *
     * @var int
     */
    public int $product_id;

    /**
     * Product Title.
     *
     * @var string
     */
    public string $title;

    /**
     * Product Title.
     *
     * @var string
     */
    public string $subtitle;

    /**
     * Product Title.
     *
     * @var string
     */
    public string $image;

    /**
     * Product Title.
     *
     * @var string
     */
    public string $thumbnail;

    /**
     * Product Title.
     *
     * @var string[]
     */
    public array $category = [];

    /**
     * Product Title.
     *
     * @var string
     */
    public string $url;

    /**
     * Product Title.
     *
     * @var int
     */
    public int $upc;

    /**
     * Product Title.
     *
     * @var string
     */
    public string $gtin14;

    /**
     * Product Title.
     *
     * @var string
     */
    public string $created_at;

    /**
     * Product Title.
     *
     * @var Brand
     */
    public Brand $brand;
}
