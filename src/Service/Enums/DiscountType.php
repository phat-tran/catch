<?php


namespace App\Service\Enums;

/**
 * Enum DiscountType consists of various discount types.
 */
enum DiscountType: string
{
    case DOLLAR     = 'DOLLAR';
    case PERCENTAGE = 'PERCENTAGE';

    /**
     * Gets the discounted value in dollar amount by different types of discount.
     *
     * @param float $total
     * @param float $value
     *
     * @return float
     */
    public function discountedValue(float $total, float $value): float
    {
        return match ($this)
        {
            DiscountType::DOLLAR     => $value,
            DiscountType::PERCENTAGE => $total * ($value / 100),
        };
    }
}
