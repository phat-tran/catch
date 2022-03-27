<?php

namespace App\Tests;


use App\Service\Enums\DiscountType;
use App\Service\HttpResponse\Customer;
use App\Service\HttpResponse\Discount;
use App\Service\HttpResponse\Order;
use App\Service\HttpResponse\OrderItem;
use App\Service\HttpResponse\Product;
use App\Service\HttpResponse\ShippingAddress;
use App\Service\Model\OrderReport;
use App\Service\Output\OutputCsv;
use App\Service\Output\OutputJson;
use App\Service\ReportOrderService;
use DateTime;
use Exception;
use Faker\Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class ReportOrderServiceTest consists test cases for ReportOrderService class.
 */
class ReportOrderServiceTest extends TestCase
{
    /**
     * Tests get output.
     */
    public function testGetOutput(): void
    {
        $inputFilePath = 'storage/input.jsonl';
        $output        = $this->getMockBuilder(OutputCsv::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reportService = new ReportOrderService($inputFilePath, $output, 0);
        $this->assertInstanceOf(OutputCsv::class, $reportService->getOutput());
    }

    /**
     * Tests write order reports.
     *
     * @throws Exception
     */
    public function testWriteOrderReports(): void
    {
        $inputFilePath  = 'public/input.jsonl';
        $outputFileName = 'out' . time();
        $output         = new OutputCsv(
            $_ENV['ORDERS_FILE_SAVED_PATH'],
            $outputFileName,
        );

        $reportService = new ReportOrderService($inputFilePath, $output, false);

        // Since we open the file to write, the file should exist by now.

        $reportService->getOutput()->startWritingFile();
        $this->assertFileExists($reportService->getOutput()->getOutputFilePath());

        // We write no data to the file. The file should be empty.

        $reportService->writeOrderReports([]);
        $this->assertEquals(0, filesize($reportService->getOutput()->getOutputFilePath()));

        // Write something to the output file, filesize should be greater than 0.

        $orderReport           = new OrderReport();
        $orderReport->order_id = random_int(0, 1000);
        $reportService->writeOrderReports([$orderReport]);
        clearstatcache();
        $this->assertGreaterThan(0, filesize($reportService->getOutput()->getOutputFilePath()));

        // Now check the output Csv has the columns and the content we write.

        $headers     = array_keys(get_object_vars($orderReport));
        $headers     = implode(',', $headers);
        $fileContent = file_get_contents($reportService->getOutput()->getOutputFilePath());
        $this->assertStringContainsString($headers, $fileContent);
        $this->assertStringContainsString($orderReport->order_id, $fileContent);

        // Delete the output file.

        unlink($reportService->getOutput()->getOutputFilePath());
    }

    /**
     * Tests generate order report.
     *
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function testGenerateOrderReport(): void
    {
        $inputFilePath = 'storage/input.jsonl';
        $output        = $this->getMockBuilder(OutputCsv::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reportService = new ReportOrderService($inputFilePath, $output, 0);

        // Create a fake order.

        $order     = self::createFakeOrder();
        $orderJson = json_encode($order);

        // Assert return type.

        $orderReport = $reportService->generateOrderReport($orderJson);
        $this->assertInstanceOf(OrderReport::class, $reportService->generateOrderReport($orderJson));

        // Assert OrderReport values.

        $this->assertEquals($order->order_id, $orderReport->order_id);
        $this->assertEquals($order->order_date, $orderReport->order_datetime);
        $this->assertEquals($order->customer->shipping_address->state, $orderReport->customer_state);
    }

    /**
     * Tests generate Csv order report.
     *
     * @depends testGenerateOrderReport
     * @depends testWriteOrderReports
     *
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function testGenerateCsvOrderReports(): void
    {
        // Create input file.

        $inputFilePath = $_ENV['ORDERS_FILE_SAVED_PATH'] . '/input.jsonl';

        // Let's write to the input file with some orders.

        $handle    = fopen($inputFilePath, 'w');
        $numOrders = 5;

        foreach (range(0, $numOrders - 1) as $i)
        {
            fwrite($handle, json_encode(self::createFakeOrder()) . "\n");
        }

        fclose($handle);

        // Create OutputCsv engine with getGeolocation = false.

        $outputFileName = 'out' . time();
        $output         = new OutputCsv(
            $_ENV['ORDERS_FILE_SAVED_PATH'],
            $outputFileName,
        );

        $reportService = new ReportOrderService($inputFilePath, $output, false);

        /**
         * Now call generateOrderReports(), the returned generator should iterate same amount of time with number of
         * lines from input file.
         */

        $i = 0;

        foreach ($reportService->generateOrderReports() as &$percentage)
        {
            $i++;
        }

        $this->assertEquals($numOrders, $i);
        $this->assertFileExists($reportService->getOutput()->getOutputFilePath());
        $this->assertGreaterThan(0, filesize($reportService->getOutput()->getOutputFilePath()));

        // Since we do not get geolocation.

        $fileContent = file_get_contents($reportService->getOutput()->getOutputFilePath());
        $this->assertStringNotContainsString('longitude,latitude', $fileContent);

        // Delete input and output files.

        unlink($inputFilePath);
        unlink($reportService->getOutput()->getOutputFilePath());
    }

    /**
     * Tests generate Json order report.
     *
     * @depends testGenerateOrderReport
     * @depends testWriteOrderReports
     *
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function testGenerateJsonOrderReports(): void
    {
        // Create input file.

        $inputFilePath = $_ENV['ORDERS_FILE_SAVED_PATH'] . '/input.jsonl';

        // Let's write to the input file with some orders.

        $handle    = fopen($inputFilePath, 'w');
        $numOrders = 5;

        foreach (range(0, $numOrders - 1) as $i)
        {
            fwrite($handle, json_encode(self::createFakeOrder()) . "\n");
        }

        fclose($handle);

        // Create OutputCsv engine with getGeolocation = false.

        $outputFileName = 'out' . time();
        $output         = new OutputJson(
            $_ENV['ORDERS_FILE_SAVED_PATH'],
            $outputFileName,
        );

        $reportService = new ReportOrderService($inputFilePath, $output, false);

        /**
         * Now call generateOrderReports(), the returned generator should iterate same amount of time with number of
         * lines from input file.
         */

        $i = 0;

        foreach ($reportService->generateOrderReports() as &$percentage)
        {
            $i++;
        }

        $this->assertEquals($numOrders, $i);
        $this->assertCount($numOrders, $reportService->getOrderReports());
        $this->assertInstanceOf(OrderReport::class, $reportService->getOrderReports()[0]);
        $this->assertFileExists($reportService->getOutput()->getOutputFilePath());
        $this->assertGreaterThan(0, filesize($reportService->getOutput()->getOutputFilePath()));

        // Since we do not get geolocation.

        $fileContent = file_get_contents($reportService->getOutput()->getOutputFilePath());
        $this->assertStringNotContainsString('longitude,latitude', $fileContent);

        // Delete input and output files.

        unlink($inputFilePath);
        unlink($reportService->getOutput()->getOutputFilePath());
    }

    /**
     * Tests generate Csv order report with geolocation.
     *
     * @depends testGenerateOrderReport
     * @depends testWriteOrderReports
     *
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function testGenerateCsvOrderReportsWithGeolocation(): void
    {
        // Create input file.

        $inputFilePath = $_ENV['ORDERS_FILE_SAVED_PATH'] . '/input.jsonl';

        // Let's write to the input file with some orders.

        $handle    = fopen($inputFilePath, 'w');
        $numOrders = 5;

        foreach (range(0, $numOrders - 1) as $i)
        {
            fwrite($handle, json_encode(self::createFakeOrder()) . "\n");
        }

        fclose($handle);

        // Create OutputCsv engine with getGeolocation = true.

        $outputFileName = 'out' . time();
        $output         = new OutputCsv(
            $_ENV['ORDERS_FILE_SAVED_PATH'],
            $outputFileName,
        );

        $reportService = new ReportOrderService($inputFilePath, $output, true);

        /**
         * Now call generateOrderReports(), the returned generator should iterate same amount of time with number of
         * lines from input file.
         */

        $i = 0;

        foreach ($reportService->generateOrderReports() as &$percentage)
        {
            $i++;
        }

        $this->assertEquals($numOrders, $i);
        $this->assertFileExists($reportService->getOutput()->getOutputFilePath());
        $this->assertGreaterThan(0, filesize($reportService->getOutput()->getOutputFilePath()));

        // Since we do get geolocation.

        $fileContent = file_get_contents($reportService->getOutput()->getOutputFilePath());
        $this->assertStringContainsString('longitude,latitude', $fileContent);

        // Delete input and output files.

        unlink($inputFilePath);
        unlink($reportService->getOutput()->getOutputFilePath());
    }

    /**
     * Creates fake order.
     *
     * @return Order
     */
    private static function createFakeOrder(): Order
    {
        $faker                      = Factory::create('en_AU');
        $order                      = new Order();
        $order->order_id            = $faker->randomNumber(6);
        $order->shipping_price      = $faker->randomFloat(2, 1, 50);
        $discount                   = new Discount();

        $discount->type             = $faker->randomElement([
            DiscountType::DOLLAR->value,
            DiscountType::PERCENTAGE->value,
        ]);

        $discount->value            = $faker->randomFloat(2, 1, 10);
        $discount->priority         = 1;
        $order->discounts           = [$discount];
        $order->order_date          = $faker->dateTimeBetween('-2 years', 'now')->format(DateTime::ATOM);
        $customer                   = new Customer();
        $customer->customer_id      = $faker->randomNumber(6);
        $customer->email            = $faker->email;
        $customer->first_name       = $faker->firstName;
        $customer->last_name        = $faker->lastName;
        $customer->phone            = $faker->phoneNumber;
        $shippingAddress            = new ShippingAddress();
        $shippingAddress->street    = $faker->streetAddress;
        $shippingAddress->state     = $faker->state;
        $shippingAddress->suburb    = $faker->city;
        $shippingAddress->postcode  = $faker->postcode;
        $customer->shipping_address = $shippingAddress;
        $order->customer            = $customer;
        $orderItem                  = new OrderItem();
        $orderItem->quantity        = $faker->numberBetween(1, 10);
        $orderItem->unit_price      = $faker->randomFloat(2, 1, 50);
        $product                    = new Product();
        $product->product_id        = $faker->randomNumber(6);
        $orderItem->product         = $product;
        $order->items               = [$orderItem];

        return $order;
    }

    /**
     * Creates fake order report.
     *
     * @return OrderReport
     */
    private static function createFakeOrderReport(): OrderReport
    {
        $faker                            = Factory::create();
        $orderReport                      = new OrderReport();
        $orderReport->order_id            = $faker->randomNumber(6);
        $orderReport->order_datetime      =
            $faker->dateTimeBetween('-2 years', 'now')->format(DateTime::ATOM);
        $orderReport->total_order_value   = $faker->randomFloat(2, 100, 1000);
        $orderReport->average_unit_price  = $faker->randomFloat(2, 1, 50);
        $orderReport->distinct_unit_count = $faker->numberBetween(1, 10);
        $orderReport->total_units_count   = $faker->numberBetween(1, 20);
        $orderReport->customer_state      = $faker->city;

        return $orderReport;
    }
}
