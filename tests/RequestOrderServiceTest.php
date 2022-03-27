<?php

namespace App\Tests;


use App\Service\HttpResponse\Customer;
use App\Service\HttpResponse\Order;
use App\Service\HttpResponse\ShippingAddress;
use App\Service\RequestOrderService;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Class RequestOrderServiceTest consists test cases for RequestOrderService class.
 */
class RequestOrderServiceTest extends TestCase
{
    const JSON_URL = 'https://s3-ap-southeast-2.amazonaws.com/catch-code-challenge/challenge-1/orders.jsonl';

    /**
     * Tests get file path.
     */
    public function testGetFilePath(): void
    {
        $fileUrl            = 'https://localhost/orders.jsonl';
        $destinationDirPath = 'storage';
        $request            = new RequestOrderService($fileUrl, $destinationDirPath);
        $this->assertEquals('storage/orders.jsonl', $request->getFilePath());
    }

    /**
     * Tests download orders.
     *
     * @throws Exception
     */
    public function testDownloadOrders(): void
    {
        $fileUrl            = self::JSON_URL;
        $destinationDirPath = $_ENV['ORDERS_FILE_SAVED_PATH'];
        $request            = new RequestOrderService($fileUrl, $destinationDirPath);
        $request->downloadOrders();
        $filePath = $destinationDirPath . '/orders.jsonl';
        $this->assertFileExists($filePath);
    }

    /**
     * Tests get geolocation.
     *
     * @throws TransportExceptionInterface
     */
    public function testGetGeolocation(): void
    {
        $order                                       = new Order();
        $order->customer                             = new Customer();
        $order->customer->shipping_address           = new ShippingAddress();
        $order->customer->shipping_address->street   = '240-246 E Boundary Rd';
        $order->customer->shipping_address->suburb   = 'Bentleigh East';
        $order->customer->shipping_address->state    = 'VIC';
        $order->customer->shipping_address->postcode = '3165';
        $response                                    = RequestOrderService::getGeolocation($order);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
