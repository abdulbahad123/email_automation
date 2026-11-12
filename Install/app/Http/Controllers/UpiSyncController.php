<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UpiAutoVerifyService;
use Illuminate\Support\Facades\Log;

class UpiSyncController extends Controller
{
    /**
     * Webhook endpoint to sync Google Pay / PhonePe / Paytm / Bank SMS notifications
     *
     * URL: POST https://whatsappbulk.saasreselling.com/api/gpay-sync
     */
    public function handleSync(Request $request)
    {
        UpiAutoVerifyService::ensureTableExists();

        // 1. Verify Secret Token
        $expectedToken = UpiAutoVerifyService::getSyncToken();
        $providedToken = $request->input('token') 
            ?: $request->input('sync_token') 
            ?: $request->input('secret') 
            ?: $request->header('X-Sync-Token') 
            ?: $request->bearerToken();

        if (empty($providedToken) || $providedToken !== $expectedToken) {
            return response()->json([
                'status'  => 0,
                'message' => 'Unauthorized: Invalid or missing sync token.',
            ], 401);
        }

        // Test action from browser
        if ($request->input('action') === 'test' || $request->isMethod('get')) {
            return response()->json([
                'status'  => 1,
                'message' => 'Google Pay / UPI Auto-Sync Webhook is active and ready to receive notifications!',
                'time'    => date('Y-m-d H:i:s'),
            ]);
        }

        // 2. Extract Data
        $utr = $request->input('utr') ?: $request->input('reference_id') ?: $request->input('ref_no') ?: $request->input('rrn');
        $amount = (float)($request->input('amount') ?: 0);
        $sender = $request->input('sender') ?: $request->input('sender_name');
        $source = $request->input('source') ?: $request->input('package_name') ?: 'gpay_sync';

        // Check if raw message / notification was passed (from SMS/Notification listener app)
        $rawMessage = $request->input('message') 
            ?: $request->input('text') 
            ?: $request->input('body') 
            ?: $request->input('notification_text') 
            ?: $request->getContent();

        if ((empty($utr) || $amount <= 0) && !empty($rawMessage)) {
            $parsed = UpiAutoVerifyService::parseNotificationText((string)$rawMessage);
            if (empty($utr) && !empty($parsed['utr'])) {
                $utr = $parsed['utr'];
            }
            if ($amount <= 0 && !empty($parsed['amount'])) {
                $amount = $parsed['amount'];
            }
            if (empty($sender) && !empty($parsed['sender'])) {
                $sender = $parsed['sender'];
            }
        }

        if (empty($utr)) {
            Log::warning("UpiSyncController: Failed to find UTR in payload: " . substr((string)$rawMessage, 0, 200));
            return response()->json([
                'status'  => 0,
                'message' => 'Could not detect a 12-digit UPI UTR in the request or notification message.',
            ], 422);
        }

        $res = UpiAutoVerifyService::recordIncomingCredit(
            utr: (string)$utr,
            amount: (float)$amount,
            senderName: $sender,
            rawMessage: is_string($rawMessage) ? $rawMessage : json_encode($rawMessage),
            source: (string)$source
        );

        return response()->json([
            'status'        => 1,
            'message'       => $res['message'],
            'utr'           => $utr,
            'amount'        => $amount,
            'sender'        => $sender,
            'auto_approved' => $res['auto_approved'] ?? false,
        ]);
    }
}
