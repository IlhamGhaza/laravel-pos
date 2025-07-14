<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    /**
     * Display a listing of the deliveries.
     */
    public function index()
    {
        $deliveries = Delivery::with(['order', 'driver'])->get();
        return response()->json(['data' => $deliveries]);
    }

    /**
     * Store a newly created delivery in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'driver_id' => 'required|exists:users,id',
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'recipient_address' => 'required|string',
            'recipient_city' => 'required|string|max:100',
            'recipient_state' => 'required|string|max:100',
            'recipient_postal_code' => 'required|string|max:20',
            'scheduled_delivery_datetime' => 'required|date',
            'total_weight' => 'nullable|numeric|min:0',
            'requires_special_handling' => 'boolean',
            'delivery_notes_internal' => 'nullable|string',
            'delivery_notes_customer' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if order already has a delivery
        if (Delivery::where('order_id', $request->order_id)->exists()) {
            return response()->json([
                'message' => 'A delivery already exists for this order',
            ], 400);
        }

        $delivery = Delivery::create([
            'order_id' => $request->order_id,
            'driver_id' => $request->driver_id,
            'tracking_number' => 'DLV' . now()->format('YmdHis') . $request->order_id,
            'recipient_name' => $request->recipient_name,
            'recipient_phone' => $request->recipient_phone,
            'recipient_address' => $request->recipient_address,
            'recipient_city' => $request->recipient_city,
            'recipient_state' => $request->recipient_state,
            'recipient_postal_code' => $request->recipient_postal_code,
            'scheduled_delivery_datetime' => $request->scheduled_delivery_datetime,
            'status' => Delivery::STATUS_SCHEDULED,
            'total_weight' => $request->total_weight,
            'requires_special_handling' => $request->boolean('requires_special_handling', false),
            'delivery_notes_internal' => $request->delivery_notes_internal,
            'delivery_notes_customer' => $request->delivery_notes_customer,
        ]);

        return response()->json([
            'message' => 'Delivery created successfully',
            'data' => $delivery->load(['order', 'driver'])
        ], 201);
    }

    /**
     * Display the specified delivery.
     */
    public function show(Delivery $delivery)
    {
        return response()->json([
            'data' => $delivery->load(['order', 'driver', 'order.customer'])
        ]);
    }

    /**
     * Update the specified delivery in storage.
     */
    public function update(Request $request, Delivery $delivery)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'exists:users,id',
            'recipient_name' => 'string|max:255',
            'recipient_phone' => 'string|max:20',
            'recipient_address' => 'string',
            'recipient_city' => 'string|max:100',
            'recipient_state' => 'string|max:100',
            'recipient_postal_code' => 'string|max:20',
            'scheduled_delivery_datetime' => 'date',
            'status' => [
                'string',
                Rule::in([
                    Delivery::STATUS_PENDING,
                    Delivery::STATUS_SCHEDULED,
                    Delivery::STATUS_DISPATCHED,
                    Delivery::STATUS_IN_TRANSIT,
                    Delivery::STATUS_DELIVERED,
                    Delivery::STATUS_FAILED,
                    Delivery::STATUS_RESCHEDULED,
                ]),
            ],
            'proof_of_delivery_image_path' => 'nullable|string',
            'total_weight' => 'nullable|numeric|min:0',
            'requires_special_handling' => 'boolean',
            'delivery_notes_internal' => 'nullable|string',
            'delivery_notes_customer' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle status updates
        if ($request->has('status')) {
            switch ($request->status) {
                case Delivery::STATUS_DISPATCHED:
                    $delivery->dispatched_at = now();
                    break;
                case Delivery::STATUS_DELIVERED:
                    $delivery->delivery_at = now();
                    break;
            }
        }

        $delivery->update($request->except(['status', 'dispatched_at', 'delivery_at']));
        
        if ($request->has('status')) {
            $delivery->status = $request->status;
            $delivery->save();
        }

        return response()->json([
            'message' => 'Delivery updated successfully',
            'data' => $delivery->fresh(['order', 'driver'])
        ]);
    }

    /**
     * Remove the specified delivery from storage.
     */
    public function destroy(Delivery $delivery)
    {
        if ($delivery->status === Delivery::STATUS_DELIVERED) {
            return response()->json([
                'message' => 'Cannot delete a completed delivery',
            ], 400);
        }

        $delivery->delete();

        return response()->json([
            'message' => 'Delivery deleted successfully'
        ]);
    }

    /**
     * Update delivery status to dispatched
     */
    public function markAsDispatched(Delivery $delivery)
    {
        if ($delivery->status === Delivery::STATUS_DELIVERED) {
            return response()->json([
                'message' => 'Cannot update status for a delivered delivery',
            ], 400);
        }

        $delivery->update([
            'status' => Delivery::STATUS_DISPATCHED,
            'dispatched_at' => now()
        ]);

        return response()->json([
            'message' => 'Delivery marked as dispatched',
            'data' => $delivery->fresh(['order', 'driver'])
        ]);
    }

    /**
     * Update delivery status to delivered
     */
    public function markAsDelivered(Request $request, Delivery $delivery)
    {
        $validator = Validator::make($request->all(), [
            'proof_of_delivery_image_path' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $delivery->update([
            'status' => Delivery::STATUS_DELIVERED,
            'delivery_at' => now(),
            'proof_of_delivery_image_path' => $request->proof_of_delivery_image_path,
            'delivery_notes_customer' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Delivery marked as delivered',
            'data' => $delivery->fresh(['order', 'driver'])
        ]);
    }

    /**
     * Get delivery status by ID
     */
    public function getStatus(Delivery $delivery)
    {
        return response()->json([
            'status' => $delivery->status,
            'status_description' => $this->getStatusDescription($delivery->status),
            'delivery_id' => $delivery->id,
            'order_id' => $delivery->order_id,
            'tracking_number' => $delivery->tracking_number,
            'scheduled_delivery_datetime' => $delivery->scheduled_delivery_datetime,
            'dispatched_at' => $delivery->dispatched_at,
            'delivery_at' => $delivery->delivery_at
        ]);
    }

    /**
     * Get status description
     */
    private function getStatusDescription($status)
    {
        $descriptions = [
            Delivery::STATUS_PENDING => 'Menunggu diproses',
            Delivery::STATUS_SCHEDULED => 'Dijadwalkan',
            Delivery::STATUS_DISPATCHED => 'Dikirim',
            Delivery::STATUS_IN_TRANSIT => 'Dalam perjalanan',
            Delivery::STATUS_DELIVERED => 'Terkirim',
            Delivery::STATUS_FAILED => 'Gagal dikirim',
            Delivery::STATUS_RESCHEDULED => 'Dijadwalkan ulang',
        ];

        return $descriptions[$status] ?? 'Status tidak diketahui';
    }

    /**
     * Get deliveries by status
     */
    public function getByStatus($status)
    {
        $validStatuses = [
            Delivery::STATUS_PENDING,
            Delivery::STATUS_SCHEDULED,
            Delivery::STATUS_DISPATCHED,
            Delivery::STATUS_IN_TRANSIT,
            Delivery::STATUS_DELIVERED,
            Delivery::STATUS_FAILED,
            Delivery::STATUS_RESCHEDULED,
        ];

        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'message' => 'Invalid delivery status',
            ], 400);
        }

        $deliveries = Delivery::with(['order', 'driver'])
            ->where('status', $status)
            ->get();

        return response()->json(['data' => $deliveries]);
    }
}
