<?php

namespace App\Http\Controllers;

use App\Utils\LocationInfo;
use Illuminate\Http\Request;
use Square\SquareClient;
use Square\Models\Money;
use Square\Models\CreatePaymentRequest;
use Square\Exceptions\ApiException;
use Ramsey\Uuid\Uuid;

class PaymentController extends Controller
{
    protected $locationInfo;
    protected $squareClient;

    public function __construct(LocationInfo $locationInfo)
    {
        $this->locationInfo = $locationInfo;

        // Initialize the Square client
        $this->squareClient = new SquareClient([
            'accessToken' => env('SQUARE_ACCESS_TOKEN'),
            'environment' => env('ENVIRONMENT'),
            'userAgentDetail' => 'sample_app_php_payment',
        ]);
    }

    public function index()
    {
        // Determine the correct Square SDK URL based on environment
        $web_payment_sdk_url = config('app.env') === 'production' 
            ? "https://web.squarecdn.com/v1/square.js" 
            : "https://sandbox.web.squarecdn.com/v1/square.js";

        return view('payment.index', [
            'web_payment_sdk_url' => $web_payment_sdk_url,
            'locationInfo' => $this->locationInfo,
        ]);
    }

    public function processPayment(Request $request)
    {
        if ($request->method() != 'POST') {
            error_log('Received a non-POST request');
            return response('Request not allowed', 405);
        }

        $data = $request->json()->all();
        $token = $data['token'] ?? null;
        $idempotencyKey = $data['idempotencyKey'] ?? Uuid::uuid4()->toString();

        if (!$token) {
            return response()->json(['error' => 'Token is required'], 400);
        }

        $paymentsApi = $this->squareClient->getPaymentsApi();

        $money = new Money();
        $money->setAmount(1000);  // Amount in the smallest unit of the currency (e.g., cents)
        $money->setCurrency($this->locationInfo->getCurrency());

        try {
            $createPaymentRequest = new CreatePaymentRequest($token, $idempotencyKey, $money);
            $createPaymentRequest->setLocationId($this->locationInfo->getId());

            $response = $paymentsApi->createPayment($createPaymentRequest);

            if ($response->isSuccess()) {
                return response()->json($response->getResult());
            } else {
                return response()->json(['errors' => $response->getErrors()], 400);
            }
        } catch (ApiException $e) {
            return response()->json(['errors' => $e->getMessage()], 500);
        }
    }
}
