<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;

class UpiAutoVerifyService
{
    const TABLE_NAME = 'upi_incoming_credits';
    const DEFAULT_SYNC_TOKEN = 'gpay_sync_launchshop_secure_token_2026';

    /**
     * Auto-ensure database table exists
     */
    public static function ensureTableExists(): void
    {
        try {
            if (!Schema::hasTable(self::TABLE_NAME)) {
                Schema::create(self::TABLE_NAME, function (Blueprint $table) {
                    $table->id();
                    $table->string('utr_number', 64)->unique();
                    $table->decimal('amount', 10, 2);
                    $table->string('sender_name', 255)->nullable();
                    $table->string('sender_upi', 255)->nullable();
                    $table->string('source', 50)->default('gpay_sync');
                    $table->text('raw_message')->nullable();
                    $table->tinyInteger('is_used')->default(0);
                    $table->unsignedBigInteger('used_by_user_id')->nullable();
                    $table->timestamp('used_at')->nullable();
                    $table->timestamps();

                    $table->index(['utr_number', 'is_used']);
                });
            }
        } catch (\Throwable $e) {
            Log::error("UpiAutoVerifyService::ensureTableExists error: " . $e->getMessage());
        }
    }

    /**
     * Get the configured secret sync token
     */
    public static function getSyncToken(): string
    {
        if (function_exists('get_option')) {
            $token = get_option('gpay_sync_token', '');
            if (!empty($token)) {
                return $token;
            }
        }
        return env('GPAY_SYNC_TOKEN', self::DEFAULT_SYNC_TOKEN);
    }

    /**
     * Parse raw notification or SMS text from Google Pay / PhonePe / Paytm / Bank
     */
    public static function parseNotificationText(string $text): array
    {
        $result = [
            'utr'    => null,
            'amount' => null,
            'sender' => null,
        ];

        // 1. Extract 12-digit UTR / Reference ID
        if (preg_match('/(?:UPI(?:\s*Ref(?:\s*No)?|\s*Txn(?:\s*Id)?|\s*Transaction\s*Id)?|Ref\s*No|UTR)[:\s#-]*([0-9]{12})/i', $text, $m)) {
            $result['utr'] = $m[1];
        } elseif (preg_match('/\b([0-9]{12})\b/', $text, $m)) {
            $result['utr'] = $m[1];
        }

        // 2. Extract Amount (e.g. ₹89, Rs. 89.00, Rs 89)
        if (preg_match('/(?:(?:Rs\.?|INR|₹)\s*([\d,]+(?:\.\d{1,2})?)|([\d,]+(?:\.\d{1,2})?)\s*(?:credited|received))/i', $text, $m)) {
            $val = !empty($m[1]) ? $m[1] : (!empty($m[2]) ? $m[2] : null);
            if ($val !== null) {
                $result['amount'] = (float)str_replace(',', '', $val);
            }
        }

        // 3. Extract Sender Name
        if (preg_match('/(?:from|by)\s+([A-Za-z\s]+?)(?:\s*(?:\(|\.|\band\b|via|with|Ref|UTR|$))/i', $text, $m)) {
            $sender = trim($m[1]);
            if (strlen($sender) > 2 && strlen($sender) < 60) {
                $result['sender'] = $sender;
            }
        }

        return $result;
    }

    /**
     * Record an incoming UPI payment and check if there is an existing pending signup waiting for it
     */
    public static function recordIncomingCredit(string $utr, float $amount, ?string $senderName = null, ?string $rawMessage = null, string $source = 'gpay_sync'): array
    {
        self::ensureTableExists();

        $cleanUtr = preg_replace('/\s+/', '', $utr);
        if (empty($cleanUtr) || strlen($cleanUtr) < 6) {
            return [
                'status'  => 0,
                'message' => 'Invalid UTR format. Must be at least 6 digits.',
            ];
        }

        // Check if already recorded
        $existing = DB::table(self::TABLE_NAME)->where('utr_number', $cleanUtr)->first();
        if ($existing) {
            return [
                'status'   => 1,
                'message'  => 'Credit already logged.',
                'id'       => $existing->id,
                'is_used'  => (bool)$existing->is_used,
            ];
        }

        $creditId = DB::table(self::TABLE_NAME)->insertGetId([
            'utr_number'   => $cleanUtr,
            'amount'       => $amount,
            'sender_name'  => $senderName,
            'source'       => $source,
            'raw_message'  => $rawMessage,
            'is_used'      => 0,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // ── Auto-Match against any pending PaymentManual record ──
        $autoApproved = false;
        try {
            $pendingPayment = \Modules\AdminManualPayments\Models\PaymentManual::where('status', 0)
                ->where(function ($q) use ($cleanUtr) {
                    $q->where('payment_id', $cleanUtr)
                      ->orWhere('payment_info', 'LIKE', '%' . $cleanUtr . '%');
                })
                ->first();

            if ($pendingPayment) {
                // Verify amount matches approximately (difference < 1)
                $priceDiff = abs((float)$pendingPayment->amount - $amount);
                if ($priceDiff < 1.0) {
                    $pendingPayment->status = 1; // Approved
                    $pendingPayment->payment_info = 'Auto-verified via Google Pay Sync | UTR: ' . $cleanUtr;
                    $pendingPayment->changed = time();
                    $pendingPayment->save();

                    // Activate user's subscription plan!
                    if ($pendingPayment->plan_id && $pendingPayment->uid) {
                        if (class_exists('\Plan') && method_exists('\Plan', 'updatePlanForTeam')) {
                            \Plan::updatePlanForTeam($pendingPayment->plan_id, $pendingPayment->uid);
                        }
                    }

                    try {
                        \App\Models\User::where('id', $pendingPayment->uid)->update([
                            'status'         => 2,
                            'payment_status' => 'approved'
                        ]);
                    } catch (\Throwable $e) {}

                    // Mark credit as used
                    DB::table(self::TABLE_NAME)->where('id', $creditId)->update([
                        'is_used'         => 1,
                        'used_by_user_id' => $pendingPayment->uid,
                        'used_at'         => now(),
                        'updated_at'      => now(),
                    ]);

                                        $autoApproved = true;
                    Log::info("UpiAutoVerifyService: Auto-approved PaymentManual #{$pendingPayment->id} for User #{$pendingPayment->uid} with UTR {$cleanUtr}");
                }
            }

            // Check WebsiteBuilder pending template purchases only when not in standalone mode
            if (!filter_var(env('STANDALONE_MODE', true), FILTER_VALIDATE_BOOLEAN) && \Illuminate\Support\Facades\Schema::hasTable('wb_template_purchases')) {
                $wbPurchase = \App\Models\WebsiteBuilder\WbTemplatePurchase::where('status', 'Pending Verification')
                    ->where(function($q) use ($cleanUtr) {
                        $q->where('transaction_id', $cleanUtr)
                          ->orWhere('transaction_id', 'LIKE', '%' . $cleanUtr . '%')
                          ->orWhere('razorpay_payment_id', 'LIKE', '%' . $cleanUtr . '%');
                    })
                    ->first();

                if ($wbPurchase) {
                    $wbPriceDiff = abs((float)$wbPurchase->amount - $amount);
                    if ($wbPriceDiff < 1.0) {
                        $wbPurchase->status = 'completed';
                        $wbPurchase->save();

                        // Activate customer account if needed
                        if (!empty($wbPurchase->customer_id)) {
                            $wbCust = \App\Models\WebsiteBuilder\WbCustomer::find($wbPurchase->customer_id);
                            if ($wbCust) {
                                $wbCust->status = 1;
                                $wbCust->save();
                            }
                        }

                        // Mark incoming credit as used
                        DB::table(self::TABLE_NAME)->where('id', $creditId)->update([
                            'is_used'    => 1,
                            'used_at'    => now(),
                            'updated_at' => now(),
                        ]);

                        $autoApproved = true;
                        Log::info("UpiAutoVerifyService: Auto-approved WebsiteBuilder Purchase #{$wbPurchase->id} for {$wbPurchase->customer_name} with UTR {$cleanUtr}");
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("UpiAutoVerifyService match pending error: " . $e->getMessage());
        }

        return [
            'status'        => 1,
            'message'       => 'Credit recorded successfully.',
            'id'            => $creditId,
            'auto_approved' => $autoApproved,
        ];
    }

    /**
     * Check if a UTR has already been received in our incoming credits table
     * Used during user signup to grant instant approval
     */
    public static function checkAndClaimCredit(string $utr, float $expectedAmount, ?int $userId = null): array
    {
        self::ensureTableExists();

        $cleanUtr = preg_replace('/\s+/', '', $utr);
        if (empty($cleanUtr) || strlen($cleanUtr) < 6) {
            return [
                'verified' => false,
                'message'  => 'Invalid UTR format.',
            ];
        }

        $credit = DB::table(self::TABLE_NAME)
            ->where('utr_number', $cleanUtr)
            ->where('is_used', 0)
            ->first();

        if (!$credit) {
            return [
                'verified' => false,
                'message'  => 'No matching incoming bank credit found yet.',
            ];
        }

        // Check amount match with 1 rupee tolerance
        if ($expectedAmount > 0) {
            $diff = abs((float)$credit->amount - (float)$expectedAmount);
            if ($diff >= 1.0) {
                return [
                    'verified' => false,
                    'message'  => "Amount mismatch. Expected ₹{$expectedAmount}, found ₹{$credit->amount}.",
                ];
            }
        }

        // Claim credit
        if ($userId) {
            DB::table(self::TABLE_NAME)->where('id', $credit->id)->update([
                'is_used'         => 1,
                'used_by_user_id' => $userId,
                'used_at'         => now(),
                'updated_at'      => now(),
            ]);
        }

        return [
            'verified' => true,
            'credit'   => $credit,
            'message'  => 'Payment successfully auto-verified with received bank credit!',
        ];
    }
}
