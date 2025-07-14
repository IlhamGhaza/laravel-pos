<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    protected $syncService;

    // Models that support sync
    protected $syncableModels = [
        'products' => \App\Models\Product::class,
        'customers' => \App\Models\Customer::class,
        'suppliers' => \App\Models\Supplier::class,
        'orders' => \App\Models\Order::class,
        'purchase_orders' => \App\Models\PurchaseOrder::class,
    ];

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Get last sync timestamp for each model
     */
    public function getLastSync(Request $request)
    {
        $models = $request->input('models', array_keys($this->syncableModels));
        
        $result = [];
        
        foreach ($models as $model) {
            if (!isset($this->syncableModels[$model])) {
                continue;
            }
            
            $modelClass = $this->syncableModels[$model];
            $lastSync = $modelClass::max('updated_at');
            
            $result[$model] = [
                'last_sync' => $lastSync ? (is_string($lastSync) ? $lastSync : $lastSync->toIso8601String()) : null,
                'count' => $modelClass::count(),
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Push local changes to server
     */
    public function pushChanges(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'changes' => 'required|array',
            'changes.*.model' => 'required|in:' . implode(',', array_keys($this->syncableModels)),
            'changes.*.data' => 'required|array',
            'changes.*.data.*' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $results = [];
        
        foreach ($request->changes as $change) {
            $modelClass = $this->syncableModels[$change['model']];
            
            try {
                $results[$change['model']] = $this->syncService->syncChanges(
                    $change['data'],
                    $modelClass
                );
            } catch (\Exception $e) {
                $results[$change['model']] = [
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Pull changes from server
     */
    public function pullChanges(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'models' => 'required|array',
            'models.*.name' => 'required|in:' . implode(',', array_keys($this->syncableModels)),
            'models.*.last_sync' => 'nullable|date',
            'models.*.synced_ids' => 'nullable|array',
            'models.*.synced_ids.*' => 'numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $results = [];
        
        foreach ($request->models as $model) {
            $modelName = $model['name'];
            $modelClass = $this->syncableModels[$modelName];
            
            $results[$modelName] = $this->syncService->getUnsyncedData(
                $modelClass,
                $model['synced_ids'] ?? [],
                $model['last_sync'] ?? null
            );
        }
        
        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Resolve sync conflicts
     */
    public function resolveConflict(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'model' => 'required|in:' . implode(',', array_keys($this->syncableModels)),
            'local_data' => 'required|array',
            'server_data' => 'required|array',
            'resolution' => 'required|in:server,client,merge',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $modelClass = $this->syncableModels[$request->model];
        
        try {
            $result = $this->syncService->resolveConflict(
                $modelClass,
                $request->local_data,
                $request->server_data,
                $request->resolution
            );
            
            return response()->json([
                'success' => $result['success'] ?? false,
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch sync multiple models
     */
    public function batchSync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sync_data' => 'required|array',
            'sync_data.*' => 'required|array',
            'sync_data.*.model' => 'required|in:' . implode(',', array_keys($this->syncableModels)),
            'sync_data.*.data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $syncData = [];
        
        foreach ($request->sync_data as $item) {
            $modelClass = $this->syncableModels[$item['model']];
            $syncData[$modelClass] = $item['data'];
        }
        
        $results = $this->syncService->batchSync($syncData);
        
        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
