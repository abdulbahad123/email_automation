<?php

namespace Modules\PaymentRazorpay\Services;

use Modules\Payment\Interfaces\PaymentInterface;
use Razorpay\Api\Api as RazorpayApi;
use Exception;

class PaymentService implements PaymentInterface
{
    protected string $keyId;
    protected string $keySecret;
    protected string $baseUrl = 'https://api.razorpay.com/v1';

    public function __construct()
    {
        $this->keyId = (string)get_option('razorpay_key_id', 'rzp_test_T9UaATIMf1qeO8');
        $this->keySecret = (string)get_option('razorpay_key_secret', 'BQ9Z865NgRQrrIMCusfzmskZ');

        if (empty($this->keyId) || empty($this->keySecret)) {
            throw new Exception(__("Razorpay API key or secret is not configured."));
        }
    }

    /**
     * Create a Razorpay Payment Link or Order.
     */
    public function pay(array $data)
    {
        try {
            $currency = strtoupper($data['currency'] ?? get_option('currency', 'INR'));
            // Razorpay standard amount in paise/cents (integer)
            $amountInSubunits = (int)round(((float)$data['amount']) * 100);

            if ($amountInSubunits <= 0) {
                throw new Exception(__("Invalid payment amount."));
            }

            $customerName = !empty($data['fullname']) ? $data['fullname'] : (!empty($data['username']) ? $data['username'] : 'Customer');
            $customerEmail = !empty($data['email']) ? $data['email'] : '';
            $referenceId = 'ORD_' . time() . '_' . rand_string(6);

            $payload = [
                'amount'          => $amountInSubunits,
                'currency'        => $currency,
                'accept_partial'  => false,
                'description'     => !empty($data['plan_name']) ? ('Subscription: ' . $data['plan_name']) : 'WhatsApp Bulk Plan',
                'reference_id'    => $referenceId,
                'callback_url'    => $data['return_url'],
                'callback_method' => 'get',
                'customer'        => [
                    'name'  => $customerName,
                    'email' => $customerEmail,
                ],
                'notify' => [
                    'sms'   => false,
                    'email' => !empty($customerEmail),
                ],
                'notes' => [
                    'plan_id'   => $data['plan_id'] ?? '',
                    'user_id'   => $data['user_id'] ?? '',
                    'plan_name' => $data['plan_name'] ?? '',
                ]
            ];

            // 1. Try Razorpay SDK if available
            if (class_exists(RazorpayApi::class)) {
                try {
                    $api = new RazorpayApi($this->keyId, $this->keySecret);
                    $paymentLink = $api->paymentLink->create($payload);
                    if (!empty($paymentLink['short_url'])) {
                        session([
                            'razorpay_payment_link_id'   => $paymentLink['id'],
                            'razorpay_reference_id'      => $referenceId,
                            'razorpay_expected_amount'   => (float)$data['amount'],
                            'razorpay_expected_currency' => $currency,
                        ]);
                        return $paymentLink['short_url'];
                    }
                } catch (Exception $sdkErr) {
                    // Fall back to REST API
                }
            }

            // 2. Direct REST API via cURL
            $response = $this->callApi('POST', '/payment_links', $payload);

            if (!empty($response['short_url'])) {
                session([
                    'razorpay_payment_link_id'   => $response['id'],
                    'razorpay_reference_id'      => $referenceId,
                    'razorpay_expected_amount'   => (float)$data['amount'],
                    'razorpay_expected_currency' => $currency,
                ]);

                return $response['short_url'];
            }

            // Fallback to Razorpay Orders API
            $orderPayload = [
                'amount'   => $amountInSubunits,
                'currency' => $currency,
                'receipt'  => $referenceId,
                'notes'    => [
                    'plan_id' => $data['plan_id'] ?? '',
                    'user_id' => $data['user_id'] ?? '',
                ]
            ];
            $order = $this->callApi('POST', '/orders', $orderPayload);

            if (!empty($order['id'])) {
                session([
                    'razorpay_order_id'          => $order['id'],
                    'razorpay_expected_amount'   => (float)$data['amount'],
                    'razorpay_expected_currency' => $currency,
                ]);

                return $data['return_url'] . '?razorpay_order_id=' . urlencode($order['id']);
            }

            throw new Exception(__("Unable to create Razorpay payment: ") . json_encode($response));

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Verify payment on success callback.
     */
    public function success()
    {
        try {
            $paymentId = request()->query('razorpay_payment_id') 
                ?: request()->input('razorpay_payment_id') 
                ?: request()->session_id;

            if (empty($paymentId)) {
                $linkId = request()->query('razorpay_payment_link_id') ?: session('razorpay_payment_link_id');
                if ($linkId) {
                    $linkData = $this->callApi('GET', '/payment_links/' . $linkId);
                    if (!empty($linkData['payments']) && count($linkData['payments']) > 0) {
                        $paymentId = $linkData['payments'][0]['payment_id'];
                    }
                }
            }

            if (empty($paymentId)) {
                throw new Exception(__("Razorpay payment ID was not found in the callback request."));
            }

            $payment = null;
            if (class_exists(RazorpayApi::class)) {
                try {
                    $api = new RazorpayApi($this->keyId, $this->keySecret);
                    $paymentObj = $api->payment->fetch($paymentId);
                    if ($paymentObj->status === 'authorized') {
                        $paymentObj->capture(['amount' => $paymentObj->amount, 'currency' => $paymentObj->currency]);
                    }
                    $payment = $paymentObj->toArray();
                } catch (Exception $sdkErr) {
                    // Fall back to REST API
                }
            }

            if (!$payment) {
                $payment = $this->callApi('GET', '/payments/' . $paymentId);

                if (empty($payment['status'])) {
                    throw new Exception(__("Unable to verify payment with Razorpay."));
                }

                if ($payment['status'] === 'authorized') {
                    $captureRes = $this->callApi('POST', '/payments/' . $paymentId . '/capture', [
                        'amount'   => $payment['amount'],
                        'currency' => $payment['currency']
                    ]);
                    $payment = $captureRes;
                }
            }

            if (!in_array($payment['status'], ['captured', 'paid'])) {
                throw new Exception(__("Payment status is :status. Payment was not completed.", ['status' => $payment['status']]));
            }

            $amount = ((float)$payment['amount']) / 100;
            $currency = strtoupper($payment['currency']);

            return [
                'gateway'             => "Razorpay",
                'status'              => 1,
                'transaction_id'      => $payment['id'],
                'amount'              => $amount,
                'currency'            => $currency,
                'transaction_details' => $payment
            ];

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Process refund.
     */
    public function refund(array $data)
    {
        try {
            $paymentId = $data['payment_id'] ?? $data['transaction_id'] ?? null;
            if (empty($paymentId)) {
                throw new Exception(__("Missing payment ID for refund."));
            }

            $payload = [];
            if (isset($data['amount'])) {
                $payload['amount'] = (int)round(((float)$data['amount']) * 100);
            }

            return $this->callApi('POST', '/payments/' . $paymentId . '/refund', $payload);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Helper to call Razorpay REST APIs via cURL.
     */
    protected function callApi(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $this->keyId . ':' . $this->keySecret);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif (strtoupper($method) === 'GET' && !empty($data)) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("cURL error connecting to Razorpay: " . $curlError);
        }

        $result = json_decode($responseBody, true);

        if ($httpCode >= 400) {
            $msg = $result['error']['description'] ?? ($result['error']['message'] ?? ('Razorpay error (' . $httpCode . ')'));
            throw new Exception($msg);
        }

        return is_array($result) ? $result : [];
    }
}
