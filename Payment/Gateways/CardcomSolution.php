<?php

namespace Modules\Payment\Gateways;

use Illuminate\Http\Request;
use Modules\Order\Entities\Order;
use Modules\Payment\GatewayInterface;
use Modules\Payment\Responses\CardComSolutionResponse;
use GuzzleHttp\Client;

class CardcomSolution implements GatewayInterface
{
    public $label;
    public $description;

    protected $client;

    public function __construct()
    {


        $this->client = new Client();
    }

    public function purchase(Order $order, Request $request): CardComSolutionResponse
    {
        $url = 'https://secure.cardcom.solutions/api/v11/LowProfile/Create'; // URL for Cardcom API


        // Prepare the request data
        $requestData = [
            'TerminalNumber' => setting('card_com_solution_terminal_number'),
            'ApiName' => setting('card_com_solution_api_name'),
            'ReturnValue' => uniqid('ref_'), // Unique transaction reference
            'Amount' => (float)$order->total->convertToCurrentCurrency()->amount(),
            'SuccessRedirectUrl' => $this->getRedirectUrl($order),
            'FailedRedirectUrl' => $this->getPaymentFailedUrl($order),
            'WebHookUrl' => $this->getWebhookUrl($order), // URL for webhook
            'Document' => [
                'Name' => $order->billing_first_name . ' ' . $order->billing_last_name,
                'Email' =>  $order->customer_email,
                'Products' => $this->prepareProducts($order),
            ],
        ];
        // echo "<pre>";
        // print_r($order);
        // die;
        // Send the POST request to Cardcom
        $response = $this->client->post($url, [
            'json' => $requestData,
        ]);

        // Parse the response
        $responseBody = json_decode($response->getBody(), true);

        return new CardComSolutionResponse($order, $responseBody);
    }

    protected function prepareProducts($order): array
    {
        $products = [];

        foreach ($order->products as $orderProduct) {
            $products[] = [
                'Description' => $orderProduct->product->name,
                'UnitCost' => (float)$orderProduct->line_total->convertToCurrentCurrency()->amount(),
            ];
        }
        if ($order->shipping_cost) {
            $products[] = [
                'Description' => $order->shipping_method,
                'UnitCost' => (float)$order->shipping_cost->convertToCurrentCurrency()->amount(),
            ];
        }

        return $products;
    }

    private function getRedirectUrl($order)
    {
        return route('checkout.complete.store', [
            'orderId' => $order->id,
            'paymentMethod' => 'card_com_solution',
            'reference' => uniqid('cardcom_'),
        ]);
    }

    private function getPaymentFailedUrl($order)
    {
        return route('checkout.payment_canceled.store', [
            'orderId' => $order->id,
            'paymentMethod' => 'card_com_solution',
        ]);
    }

    private function getWebhookUrl($order)
    {
        return route('payment.webhook', [
            'orderId' => $order->id,
            'paymentMethod' => 'card_com_solution',
        ]);
    }

    public function complete(Order $order): CardComSolutionResponse
    {
        return new CardComSolutionResponse($order, request()->all());
    }
}
