<?php

namespace App\Service;


use Symfony\Component\HttpClient\HttpClient;
use App\Service\Enums\DiscountType;
use App\Service\HttpResponse\Discount;
use App\Service\HttpResponse\Order;
use App\Service\Model\OrderReport;
use App\Service\Output\Output;
use DateTime;
use Exception;
use Generator;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class ProcessingOrderService processes orders.
 */
class ReportOrderService
{
    /**
     * Stores reports generated for each line.
     *
     * @var OrderReport[]
     */
    private array $orderReports = [];

    /**
     * Input file path.
     *
     * @var string
     */
    private string $inputFilePath;

    /**
     * Report output engine.
     *
     * @var Output
     */
    private Output $output;

    /**
     * Flag to get geolocation from shipping address or not.
     *
     * @var bool
     */
    private bool $getGeolocation;

    /**
     * Constructor.
     *
     * @param string $inputFilePath  The input file path.
     * @param Output $output         The output engine.
     * @param bool   $getGeolocation Flag to get geolocation from shipping address or not.
     */
    public function __construct(string $inputFilePath, Output $output, bool $getGeolocation = false)
    {
        $this->inputFilePath  = $inputFilePath;
        $this->output         = $output;
        $this->getGeolocation = $getGeolocation;
    }

    /**
     * Gets order reports.
     *
     * @return OrderReport[]
     */
    public function getOrderReports(): array
    {
        return $this->orderReports;
    }

    /**
     * Gets output engine.
     *
     * @return Output
     */
    public function getOutput(): Output
    {
        return $this->output;
    }

    /**
     * Writes reports to the output file.
     *
     * @param OrderReport[] $reports
     *
     * @return void
     * @throws Exception
     */
    public function writeOrderReports(array $reports): void
    {
        $this->output->validateFileHandle();
        $this->output->setOrderReports($reports);
        $this->output->write();
    }

    /**
     * Reads input file and generate report line by line.
     * Use generator to provide meaningful processing percentage output.
     *
     * @return Generator
     * @throws Exception|TransportExceptionInterface
     */
    public function &generateOrderReports(): Generator
    {
        $this->validateInputFile();

        // Open input file to read.

        $handleInput = fopen($this->inputFilePath, 'r');

        // Get input file size to calculate progress percentage.

        $fileSize   = filesize($this->inputFilePath);
        $percentage = 0.0;

        // Open output file for writing.

        $this->output->startWritingFile();

        // Now read the input file line by line.

        while (($line = fgets($handleInput)) !== false)
        {
            // Generate report for each line.

            $report = $this->generateOrderReport($line);

            // Skip report that is null.

            if ($report === null)
            {
                continue;
            }

            /**
             * If we want to write the report to output file line by line then run the method writeOrderReports right
             * away. It also depends on the Output subclass that can generate a valid report file or not.
             * Currently, only Csv and Jsonl support writing line by line.
             */

            if ($this->output->isWriteEachLine())
            {
                $this->writeOrderReports([$report]);
            }

            // Otherwise, save it to the $orderReports to write the whole thing after the loop.

            else
            {
                $this->orderReports[] = $report;
            }

            // Build some progress percentage to display back to the console.

            $percentage += strlen($line) * 100 / $fileSize;
            $percentage = $percentage > 100.0 ? 100.0 : $percentage;
            $percentage = round($percentage, 2);

            yield $percentage;
        }

        if (!$this->output->isWriteEachLine())
        {
            $this->writeOrderReports($this->orderReports);
        }

        // That's it. Close all file handles.

        fclose($handleInput);
        $this->output->stopWritingFile();
    }

    /**
     * Generates order report from a line from the downloaded file.
     *
     * @param string $line The line to generate report from.
     *
     * @return OrderReport|null
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function generateOrderReport(string $line): OrderReport|null
    {
        /** @var Order $order */
        $order = json_decode($line);

        // Now build the order report based on the input.

        $report                 = new OrderReport();
        $report->order_id       = $order->order_id;
        $report->order_datetime = self::castToIso8601($order->order_date);
        $totalOrderValue        = self::calculateTotalOrderValue($order);

        // Order records with 0 total order value should be excluded from the summary output.

        if ($totalOrderValue === 0.0)
        {
            return null;
        }

        $report->total_order_value   = $totalOrderValue;
        $report->average_unit_price  = self::calculateAverageUnitPrice($order);
        $report->distinct_unit_count = self::calculateDistinctUnitCount($order);
        $report->total_units_count   = self::calculateTotalUnitCount($order);
        $report->customer_state      = $order->customer->shipping_address->state;

        // If we need to get geolocation, then use getGeolocation to call API to get lat and lon.

        if ($this->getGeolocation)
        {
            $geolocation = self::getGeolocation($order);

            if (!empty($geolocation))
            {
                list($report->latitude, $report->longitude) = $geolocation;
            }
        }

        // Otherwise, unset lat and lon attributes, so they won't appear in the report.

        else
        {
            unset($report->longitude);
            unset($report->latitude);
        }

        // Return the report object.

        return $report;
    }

    /**
     * Casts date to ISO 8601 format in the UTC timezone.
     *
     * @param string $date The date to be converted.
     *
     * @return string
     * @throws Exception
     */
    private static function castToIso8601(string $date): string
    {
        $datetime = new DateTime($date);

        return $datetime->format(DateTime::ATOM);
    }

    /**
     * Calculates total order value.
     *
     * @param Order $order The OrderReport object.
     *
     * @return float
     */
    private static function calculateTotalOrderValue(mixed $order): float
    {
        $total = 0.0;

        // Get sum of all items.

        foreach ($order->items as $orderItem)
        {
            $total += $orderItem->unit_price * $orderItem->quantity;
        }

        // Now minus the discount.

        return self::calculateAfterDiscount($total, $order->discounts);
    }

    /**
     * Calculates value when applying discounts.
     *
     * @param float      $total     The total value to be discounted.
     * @param Discount[] $discounts List of Discount objects.
     *
     * @return float
     */
    private static function calculateAfterDiscount(float $total, array $discounts): float
    {
        if (empty($discounts))
        {
            return $total;
        }

        // Sort discounts by priority.

        usort($discounts, function ($first, $second)
        {
            return $first->priority >= $second->priority ? 1 : -1;
        });

        // Then for each discount, apply to the total.

        foreach ($discounts as $discount)
        {
            $discountType = DiscountType::from($discount->type);
            $total        -= $discountType->discountedValue($total, $discount->value);
        }

        return round($total, 2);
    }

    /**
     * Calculates average unit price.
     *
     * @param Order $order The OrderReport object.
     *
     * @return float
     */
    private static function calculateAverageUnitPrice(mixed $order): float
    {
        $total = 0.0;

        // Get sum of all unit price.

        foreach ($order->items as $orderItem)
        {
            $total += $orderItem->unit_price;
        }

        // Now divide by total items in the order.

        return round($total / count($order->items), 2);
    }

    /**
     * Calculates distinct unit count.
     *
     * @param Order $order The OrderReport object.
     *
     * @return int
     */
    private static function calculateDistinctUnitCount(mixed $order): int
    {
        $productIds = [];

        // Get sum of all items.

        foreach ($order->items as $orderItem)
        {
            $productIds[] = $orderItem->product->product_id;
        }

        return count(array_unique($productIds));
    }

    /**
     * Calculates total unit count.
     *
     * @param Order $order The OrderReport object.
     *
     * @return int
     */
    private static function calculateTotalUnitCount(mixed $order): int
    {
        $total = 0;

        // Get sum of all item's quantity.

        foreach ($order->items as $orderItem)
        {
            $total += $orderItem->quantity;
        }

        return $total;
    }

    /**
     * Gets longitude and latitude for the given order.
     *
     * @param Order $order The OrderReport object.
     *
     * @return float[]
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private static function getGeolocation(mixed $order): array
    {
        try
        {
            $response = RequestOrderService::getGeolocation($order);
            $response = json_decode($response->getContent());

            // If there is no response from the api, default lat and lon to 0.

            if (empty($response))
            {
                return [
                    0,
                    0,
                ];
            }

            $response = $response[0];

            return [
                $response->lat,
                $response->lon,
            ];
        }
        catch (Exception $e)
        {
            return [];
        }
    }

    /**
     * Validates input file.
     *
     * @throws Exception
     */
    private function validateInputFile(): void
    {
        if (filesize($this->inputFilePath) === 0)
        {
            throw new Exception('Input file is empty.');
        }

        $handle = fopen($this->inputFilePath, 'r');

        if (!$handle)
        {
            throw new Exception('Error opening the input file: ' . $this->inputFilePath);
        }
    }
}
