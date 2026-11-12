<?php

namespace App\Services;

use Exception;

class StandaloneRazorpayService
{
    protected const BASE_URL = 'https://api.razorpay.com/v1';

    /**
     * Resolve Razorpay API key and secret from admin options or .env.
     */
    public static function getCredentials(): array
    {
        $keyId = function_exists('get_option') ? trim((string) get_option('razorpay_key_id', '')) : '';
        $keySecret = function_exists('get_option') ? trim((string) get_option('razorpay_key_secret', '')) : '';

        if ($keyId === '') {
            $keyId = trim((string) env('RAZORPAY_KEY_ID', 'rzp_test_T9UaATIMf1qeO8'));
        }
        if ($keySecret === '') {
            $keySecret = trim((string) env('RAZORPAY_KEY_SECRET', 'BQ9Z865NgRQrrIMCusfzmskZ'));
        }

        return [
            'key_id'     => $keyId,
            'key_secret' => $keySecret,
        ];
    }

    /**
     * Create a Razorpay Order for in-page Checkout.
     */
    public static function createOrder(float $amount, string $currency = 'INR', string $receipt = '', array $notes = []): array
    {
        $creds = self::getCredentials();
        if (empty($creds['key_id']) || empty($creds['key_secret'])) {
            throw new Exception(__('Razorpay API credentials are not configured.'));
        }

        $amountInSubunits = (int) round($amount * 100);
        if ($amountInSubunits <= 0) {
            throw new Exception(__('Invalid payment amount.'));
        }

        $currency = strtoupper(trim($currency)) ?: 'INR';
        $receipt = $receipt ?: ('WB_' . time() . '_' . substr(md5(uniqid('', true)), 0, 6));

        $payload = [
            'amount'   => $amountInSubunits,
            'currency' => $currency,
            'receipt'  => substr($receipt, 0, 40),
            'notes'    => $notes,
        ];

        $order = self::callApi('POST', '/orders', $payload, $creds['key_id'], $creds['key_secret']);
        if (empty($order['id'])) {
            throw new Exception(__('Failed to create Razorpay order.'));
        }

        return [
            'key_id'   => $creds['key_id'],
            'order_id' => $order['id'],
            'amount'   => $order['amount'],
            'currency' => $order['currency'] ?? $currency,
        ];
    }

    /**
     * Verify Razorpay signature and live payment status.
     */
    public static function verifyPayment(string $paymentId, ?string $orderId = null, ?string $signature = null): array
    {
        $creds = self::getCredentials();
        if (empty($creds['key_id']) || empty($creds['key_secret'])) {
            throw new Exception(__('Razorpay API credentials are not configured.'));
        }

        $paymentId = trim($paymentId);
        if ($paymentId === '') {
            throw new Exception(__('Missing Razorpay payment ID.'));
        }

        if (!empty($orderId) && !empty($signature)) {
            $expectedSig = hash_hmac('sha256', trim($orderId) . '|' . $paymentId, $creds['key_secret']);
            if (!hash_equals($expectedSig, trim($signature))) {
                throw new Exception(__('Razorpay payment signature verification failed.'));
            }
        }

        $payment = self::callApi('GET', '/payments/' . urlencode($paymentId), [], $creds['key_id'], $creds['key_secret']);
        if (empty($payment['status'])) {
            throw new Exception(__('Unable to verify Razorpay payment status.'));
        }

        if ($payment['status'] === 'authorized') {
            $payment = self::callApi('POST', '/payments/' . urlencode($paymentId) . '/capture', [
                'amount'   => $payment['amount'],
                'currency' => $payment['currency'],
            ], $creds['key_id'], $creds['key_secret']);
        }

        if (!in_array($payment['status'] ?? '', ['captured', 'paid'], true)) {
            throw new Exception(__('Payment was not completed (status: :status).', ['status' => $payment['status'] ?? 'unknown']));
        }

        return [
            'verified'   => true,
            'payment_id' => $payment['id'] ?? $paymentId,
            'order_id'   => $payment['order_id'] ?? $orderId,
            'amount'     => ((float) ($payment['amount'] ?? 0)) / 100,
            'currency'   => strtoupper((string) ($payment['currency'] ?? 'INR')),
        ];
    }

    protected static function callApi(string $method, string $endpoint, array $data, string $keyId, string $keySecret): array
    {
        $url = self::BASE_URL . $endpoint;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $keyId . ':' . $keySecret);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('cURL error connecting to Razorpay: ' . $curlError);
        }

        $decoded = json_decode((string) $body, true);
        if ($httpCode >= 400) {
            $msg = $decoded['error']['description'] ?? ($decoded['error']['message'] ?? ("Razorpay error ({$httpCode})"));
            throw new Exception($msg);
        }

        return is_array($decoded) ? $decoded : [];
    }
}

