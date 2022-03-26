<?php

namespace App\Service;


use App\Service\HttpResponse\Order;
use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RequestOrderService
{
    /**
     * The file URL.
     *
     * @var string
     */
    private string $fileUrl;

    /**
     * The local file path.
     *
     * @var string
     */
    private string $filePath;

    /**
     * Constructor.
     *
     * @param string $fileUrl
     * @param string $destinationDirPath
     */
    public function __construct(string $fileUrl, string $destinationDirPath)
    {
        $this->fileUrl  = $fileUrl;
        $this->filePath = implode('/', [
            $destinationDirPath,
            basename($this->fileUrl),
        ]);
    }

    /**
     * Gets file path.
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Downloads orders from the given URL and saves to the destination path with the same file name.
     *
     * @throws Exception
     */
    public function downloadOrders(): void
    {
        file_put_contents($this->filePath, file_get_contents($this->fileUrl));

        if (!file_exists($this->filePath))
        {
            throw new Exception('JSON file failed to download: ' . $this->fileUrl);
        }
    }

    /**
     * Gets longitude and latitude for the given order.
     *
     * @param Order $order The OrderReport object.
     *
     * @return ResponseInterface
     * @throws TransportExceptionInterface
     */
    public static function getGeolocation(mixed $order): ResponseInterface
    {
        $url = $_ENV['OPEN_STREET_MAP_API'] . urlencode(implode(' ', [
                    $order->customer->shipping_address->street,
                    $order->customer->shipping_address->suburb,
                    $order->customer->shipping_address->state,
                    $order->customer->shipping_address->postcode,
                ])
            );

        $client = HttpClient::create();

        return $client->request('GET', $url);
    }
}
