<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class CardcomService
{
    protected $client;
    protected $url;
    protected $terminal;
    protected $username;
    protected $apiName;
    protected $apiPassword;

    public function __construct()
    {
        $this->client = new Client();
        $this->url = config('services.cardcom.url', 'https://secure.cardcom.co.il');
        $this->terminal = config('services.cardcom.terminal');
        $this->username = config('services.cardcom.username');
        $this->apiName = config('services.cardcom.api_name');
        $this->apiPassword = config('services.cardcom.api_password');
    }

    public function charge($amount, $currency = 'ILS', $cardDetails = [], $invoiceDetails = [])
    {
        try {
            $chargeParams = [
                'terminalNumber' => $this->terminal,
                'userName' => $this->username,
                'sum' => $amount,
                'coinId' => $this->getCurrencyCode($currency),
                'cardNumber' => $cardDetails['number'],
                'cardValIdityMonth' => $cardDetails['month'],
                'cardValIdityYear' => $cardDetails['year'],
                'cvv' => $cardDetails['cvv'],
                'identityNumber' => $cardDetails['identity'],
                'createToken' => $cardDetails['create_token'] ?? false,
            ];

            $invoiceParams = $this->prepareInvoiceParams($invoiceDetails);

            $response = $this->client->post("{$this->url}/Interface/Direct2.aspx", [
                'form_params' => array_merge($chargeParams, $invoiceParams)
            ]);

            return $this->parseResponse($response->getBody()->getContents());
        } catch (\Exception $e) {
            Log::error('Cardcom Charge Error: ' . $e->getMessage());
            throw new \Exception("Payment processing failed.");
        }
    }

    public function refund($transactionId, $amount, $currency = 'ILS')
    {
        try {
            $refundParams = [
                'terminalNumber' => $this->terminal,
                'username' => $this->apiName,
                'userPassword' => $this->apiPassword,
                'dealType' => '51',
                'sum' => $amount,
                'coinId' => $this->getCurrencyCode($currency),
                'internalDealNumber' => $transactionId,
            ];

            $response = $this->client->post("{$this->url}/BillGoldPost2.aspx", [
                'form_params' => $refundParams
            ]);

            return $this->parseResponse($response->getBody()->getContents(), ';');
        } catch (\Exception $e) {
            Log::error('Cardcom Refund Error: ' . $e->getMessage());
            throw new \Exception("Refund processing failed.");
        }
    }

    public function cancel($transactionId, $partialAmount = null)
    {
        try {
            $cancelParams = [
                'terminalNumber' => $this->terminal,
                'name' => $this->apiName,
                'pass' => $this->apiPassword,
                'internalDealNumber' => $transactionId,
                'cancelOnly' => true,
            ];

            if ($partialAmount) {
                $cancelParams['partialSum'] = $partialAmount;
            }

            $response = $this->client->post("{$this->url}/Interface/CancelDeal.aspx", [
                'form_params' => $cancelParams
            ]);

            return $this->parseResponse($response->getBody()->getContents());
        } catch (\Exception $e) {
            Log::error('Cardcom Cancel Error: ' . $e->getMessage());
            throw new \Exception("Cancellation failed.");
        }
    }

    private function getCurrencyCode($currency)
    {
        $currencies = [
            'ILS' => 1,
            'USD' => 2,
            'EUR' => 978,
        ];

        return $currencies[strtoupper($currency)] ?? 1;
    }

    private function prepareInvoiceParams($invoiceDetails)
    {
        if (empty($invoiceDetails)) {
            return [];
        }

        $invoiceParams = [
            'invCreateInvoice' => true,
            'invcusid' => $invoiceDetails['identity'] ?? null,
            'invDestEmail' => $invoiceDetails['email'],
            'invCustName' => $invoiceDetails['customer_name'],
            'InvComments' => $invoiceDetails['comments'] ?? null,
            'invLanguages' => $invoiceDetails['invoice_language'] ?? 'he',
            'InvNoVat' => $invoiceDetails['no_vat'] ?? false,
        ];

        foreach ($invoiceDetails['items'] ?? [] as $key => $item) {
            $line = $key == 0 ? '' : $key;
            $invoiceParams["InvExtLine{$line}.Description"] = $item['description'];
            $invoiceParams["InvExtLine{$line}.PriceIncludeVAT"] = $item['price'];
            $invoiceParams["InvExtLine{$line}.Quantity"] = $item['quantity'] ?? '1';
            $invoiceParams["InvExtLine{$line}.ProductID"] = $item['id'] ?? null;
            $invoiceParams["InvExtLine{$line}.IsVatFree"] = $item['vat_free'] ?? false;
        }

        return $invoiceParams;
    }

    private function parseResponse($response, $separator = '&')
    {
        if ($separator === '&') {
            parse_str($response, $array);
            return $this->mapResponse($array);
        }

        if ($separator === ';') {
            $array = explode($separator, $response);
            return $this->mapResponse([
                'code' => $array[0],
                'transaction' => $array[1],
                'message' => $array[2],
            ]);
        }

        return $response;
    }

    private function mapResponse($array)
    {
        return [
            'code' => $array['ResponseCode'] ?? $array['code'],
            'message' => $array['Description'] ?? $array['message'],
            'transaction' => $array['InternalDealNumber'] ?? $array['transaction'],
            'token' => $array['Token'] ?? null,
            'approval' => $array['ApprovalNumber'] ?? null,
        ];
    }
}
