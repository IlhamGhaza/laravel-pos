<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class PaymentService
{
    protected $serverKey;
    protected $isProduction;
    protected $snapUrl;

    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key');
        $this->isProduction = config('services.midtrans.is_production');
        $this->snapUrl = $this->isProduction
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';

        $this->initializeMidtrans();
    }

    protected function initializeMidtrans()
    {
        Config::$serverKey = $this->serverKey;
        Config::$isProduction = $this->isProduction;
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Create Snap payment page URL
     */
    public function createPayment(Order $order, array $customerData)
    {
        $transactionDetails = [
            'order_id' => $order->id . '-' . time(),
            'gross_amount' => $order->total_amount,
        ];

        $customerDetails = [
            'first_name' => $customerData['name'] ?? 'Customer',
            'email' => $customerData['email'] ?? '',
            'phone' => $customerData['phone'] ?? '',
        ];

        $itemDetails = $order->items->map(function ($item) {
            return [
                'id' => $item->product_id,
                'price' => $item->unit_price,
                'quantity' => $item->quantity,
                'name' => $item->product->name,
            ];
        })->toArray();

        $params = [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
            'item_details' => $itemDetails,
            'callbacks' => [
                'finish' => route('payment.callback')
            ]
        ];

        try {
            $snapToken = Snap::getSnapToken($params);

            // Save payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'payment_method' => 'midtrans',
                'status' => 'pending',
                'transaction_id' => $transactionDetails['order_id'],
                'payment_details' => [
                    'snap_token' => $snapToken,
                    'redirect_url' => null,
                ],
            ]);

            return [
                'snap_token' => $snapToken,
                'payment_url' => "{$this->snapUrl}?snap_token={$snapToken}",
                'payment_id' => $payment->id,
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans payment error: ' . $e->getMessage());
            throw new \Exception('Failed to create payment: ' . $e->getMessage());
        }
    }

    /**
     * Handle Midtrans notification
     */
    public function handleNotification()
    {
        $notification = new Notification();

        $transaction = $notification->transaction_status;
        $type = $notification->payment_type;
        $orderId = $notification->order_id;
        $fraud = $notification->fraud_status;

        // Extract the original order ID (remove the timestamp)
        $orderId = explode('-', $orderId)[0];

        $payment = Payment::where('transaction_id', 'like', "{$orderId}-%")
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$payment) {
            return ['status' => 'error', 'message' => 'Payment not found'];
        }

        $order = $payment->order;

        if ($transaction == 'capture') {
            if ($type == 'credit_card') {
                if ($fraud == 'challenge') {
                    $payment->status = 'challenge';
                    $order->status = 'payment_challenge';
                } else {
                    $payment->status = 'paid';
                    $order->status = 'processing';
                }
            }
        } elseif ($transaction == 'settlement') {
            $payment->status = 'paid';
            $order->status = 'processing';
        } elseif ($transaction == 'pending') {
            $payment->status = 'pending';
            $order->status = 'pending_payment';
        } elseif ($transaction == 'deny' || $transaction == 'expire' || $transaction == 'cancel') {
            $payment->status = 'failed';
            $order->status = 'payment_failed';
        }

        $payment->payment_details = array_merge(
            (array) $payment->payment_details,
            ['notification' => $notification->getResponse()]
        );

        $payment->save();
        $order->save();

        return [
            'status' => 'success',
            'payment_status' => $payment->status,
            'order_status' => $order->status,
        ];
    }

    /**
     * Check payment status
     */
    public function checkStatus($paymentId)
    {
        $payment = Payment::findOrFail($paymentId);

        if ($payment->status === 'paid') {
            return (object) [
                'status' => 'success',
                'payment_status' => 'paid',
                'order_id' => $payment->order_id,
            ];
        }

        try {
            $status = (object) \Midtrans\Transaction::status($payment->transaction_id);

            // Update payment status based on API response
            if (isset($status->transaction_status)) {
                $payment->status = $this->mapStatus($status->transaction_status)->status;
                $payment->save();

                // Update order status if needed
                if ($payment->status === 'paid') {
                    $order = $payment->order;
                    $order->status = 'processing';
                    $order->save();
                }
            }

            return (object) [
                'status' => 'success',
                'payment_status' => $payment->status,
                'order_id' => $payment->order_id,
                'transaction_status' => $status->transaction_status ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans status check error: ' . $e->getMessage());

            return (object) [
                'status' => 'error',
                'message' => 'Payment not found or invalid status',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Map Midtrans status to our status
     */
    protected function mapStatus($status)
    {
        $statusMap = [
            'capture' => 'paid',
            'settlement' => 'paid',
            'pending' => 'pending',
            'deny' => 'failed',
            'expire' => 'expired',
            'cancel' => 'cancelled',
        ];

        return (object) ['status' => $statusMap[$status] ?? 'pending'];
    }
}
