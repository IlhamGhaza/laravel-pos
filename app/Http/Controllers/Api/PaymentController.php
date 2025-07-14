<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create a new payment
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Validate payment request data
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validatePaymentRequest(array $data)
    {
        return Validator::make($data, [
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:midtrans,cash,transfer',
            'customer' => 'required|array',
            'customer.name' => 'required|string|max:255',
            'customer.email' => 'required|email|max:255',
            'customer.phone' => 'required|string|max:20',
        ], [
            'order_id.required' => 'ID pesanan wajib diisi',
            'order_id.exists' => 'Pesanan tidak ditemukan',
            'payment_method.required' => 'Metode pembayaran wajib dipilih',
            'payment_method.in' => 'Metode pembayaran tidak valid',
            'customer.required' => 'Data pelanggan wajib diisi',
            'customer.name.required' => 'Nama pelanggan wajib diisi',
            'customer.email.required' => 'Email pelanggan wajib diisi',
            'customer.email.email' => 'Format email tidak valid',
            'customer.phone.required' => 'Nomor telepon pelanggan wajib diisi',
        ]);
    }

    /**
     * Process cash payment
     *
     * @param Order $order
     * @return array
     */
    protected function processCashPayment(Order $order): array
    {
        $payment = $order->payments()->create([
            'amount' => $order->total_amount,
            'payment_method' => 'cash',
            'status' => 'paid',
            'transaction_id' => 'CASH-' . time(),
            'payment_details' => [
                'paid_at' => now()->toDateTimeString(),
                'received_by' => Auth::id() ?? 1,
            ],
        ]);

        $order->status = 'processing';
        $order->save();

        return [
            'success' => true,
            'message' => 'Pembayaran tunai berhasil diterima',
            'data' => [
                'payment' => $payment,
                'order' => $order->fresh(),
            ],
        ];
    }

    /**
     * Process Midtrans payment
     *
     * @param Order $order
     * @param array $customerData
     * @return array
     */
    protected function processMidtransPayment(Order $order, array $customerData): array
    {
        $paymentData = $this->paymentService->createPayment($order, $customerData);

        $payment = $order->payments()->create([
            'amount' => $order->total_amount,
            'payment_method' => 'midtrans',
            'status' => 'pending',
            'transaction_id' => $paymentData['transaction_id'] ?? 'MID-' . time(),
            'payment_details' => $paymentData,
        ]);

        $order->status = 'pending_payment';
        $order->save();

        return [
            'success' => true,
            'message' => 'Pembayaran Midtrans berhasil dimulai',
            'data' => [
                'payment' => $payment,
                'payment_data' => $paymentData,
            ],
        ];
    }

    /**
     * Process bank transfer payment
     *
     * @param Order $order
     * @param array $requestData
     * @return array
     */
    protected function processTransferPayment(Order $order, array $requestData): array
    {
        $payment = $order->payments()->create([
            'amount' => $order->total_amount,
            'payment_method' => 'transfer',
            'status' => 'pending',
            'transaction_id' => 'TRF-' . time(),
            'payment_details' => [
                'bank_name' => $requestData['bank_name'] ?? null,
                'account_number' => $requestData['account_number'] ?? null,
                'account_holder' => $requestData['account_holder'] ?? null,
                'transfer_date' => $requestData['transfer_date'] ?? null,
                'transfer_proof' => $requestData['transfer_proof'] ?? null,
            ],
        ]);

        $order->status = 'pending_confirmation';
        $order->save();

        return [
            'success' => true,
            'message' => 'Pembayaran transfer berhasil dibuat',
            'data' => [
                'payment' => $payment,
                'order' => $order->fresh(),
            ],
        ];
    }

    /**
     * Create a new payment
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Validate order for payment processing
     *
     * @param int $orderId
     * @return Order
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function validateOrderForPayment($orderId)
    {
        $order = Order::with('items.product')->find($orderId);

        if (!$order) {
            $response = $this->buildResponse([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ], 404);
            throw new \Illuminate\Http\Exceptions\HttpResponseException($response);
        }

        if (in_array($order->status, ['paid', 'completed'])) {
            $response = $this->buildResponse([
                'success' => false,
                'message' => 'Pesanan ini sudah dibayar sebelumnya'
            ], 422);
            throw new \Illuminate\Http\Exceptions\HttpResponseException($response);
        }

        return $order;
    }

    /**
     * Process payment based on method
     *
     * @param string $method
     * @param Order $order
     * @param array $requestData
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function processPaymentByMethod($method, $order, $requestData)
    {
        switch ($method) {
            case 'cash':
                return $this->processCashPayment($order);
            case 'midtrans':
                return $this->processMidtransPayment($order, $requestData['customer']);
            case 'transfer':
                return $this->processTransferPayment($order, $requestData);
            default:
                throw new \InvalidArgumentException('Metode pembayaran tidak didukung');
        }
    }

    /**
     * Create a new payment
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPayment(Request $request)
    {
        $response = [
            'success' => false,
            'message' => 'Gagal memproses pembayaran',
        ];
        $statusCode = 500;

        try {
            // Validate request data
            $validator = $this->validatePaymentRequest($request->all());
            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }

            DB::beginTransaction();

            // Validate and get order
            $order = $this->validateOrderForPayment($request->order_id);

            // Process payment based on method
            $response = $this->processPaymentByMethod(
                $request->payment_method,
                $order,
                $request->all()
            );

            DB::commit();
            $statusCode = 200;

        } catch (\Illuminate\Validation\ValidationException $e) {
            $response = [
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ];
            $statusCode = 422;
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            // Re-throw the exception as it's already handled
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment processing error: ' . $e->getMessage());
            $response['error'] = config('app.debug') ? $e->getMessage() : null;
        }

        return $this->buildResponse($response, $statusCode);
    }

    /**
     * Build a consistent JSON response
     *
     * @param array $data
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Build a consistent JSON response
     *
     * @param array $data
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function buildResponse(array $data, int $statusCode = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json($data, $statusCode);
    }

    /**
     * Handle payment notification from payment gateway
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handlePaymentNotification(Request $request)
    {
        try {
            $result = $this->paymentService->handleNotification();
            return $this->buildResponse($result);
        } catch (\Exception $e) {
            Log::error('Midtrans notification error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check payment status
     */
    public function checkStatus($paymentId)
    {
        try {
            $result = $this->paymentService->checkStatus($paymentId);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment history
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentHistory(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $payments = \App\Models\Payment::with(['order', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return $this->buildResponse([
                'success' => true,
                'data' => $payments
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch payment history: ' . $e->getMessage());
            
            return $this->buildResponse([
                'success' => false,
                'message' => 'Gagal mengambil riwayat pembayaran',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get payment methods
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentMethods()
    {
        return response()->json([
            'success' => true,
            'data' => [
                [
                    'code' => 'cash',
                    'name' => 'Cash',
                    'description' => 'Pembayaran tunai',
                    'icon' => 'cash',
                ],
                [
                    'code' => 'transfer',
                    'name' => 'Transfer Bank',
                    'description' => 'Transfer ke rekening bank',
                    'icon' => 'bank',
                ],
                [
                    'code' => 'midtrans',
                    'name' => 'Midtrans',
                    'description' => 'Pembayaran online via Midtrans',
                    'icon' => 'credit-card',
                ],
            ],
        ]);
    }
}
