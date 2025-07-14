<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SyncService
{
    /**
     * Sync local changes with server
     */
    public function syncChanges(array $localChanges, string $modelClass): array
    {
        if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            throw new \InvalidArgumentException("Invalid model class: {$modelClass}");
        }

        $results = [
            'created' => 0,
            'updated' => 0,
            'errors' => [],
            'conflicts' => [],
            'synced_ids' => [],
        ];

        foreach ($localChanges as $change) {
            try {
                $isUpdate = isset($change['id']);
                
                if ($isUpdate) {
                    // Handle update
                    $model = $modelClass::find($change['id']);
                    
                    if (!$model) {
                        $results['errors'][] = [
                            'id' => $change['id'],
                            'error' => 'Record not found on server',
                        ];
                        continue;
                    }
                    
                    // Check for conflicts if needed
                    if (isset($change['updated_at']) && $model->updated_at->gt(Carbon::parse($change['updated_at']))) {
                        $results['conflicts'][] = [
                            'local' => $change,
                            'server' => $model->toArray(),
                        ];
                        continue;
                    }
                    
                    $model->fill($change);
                    $model->save();
                    $results['updated']++;
                } else {
                    // Handle create
                    $model = $modelClass::create($change);
                    $results['created']++;
                }
                
                $results['synced_ids'][] = $model->id;
                
            } catch (\Exception $e) {
                Log::error("Sync error for {$modelClass}: " . $e->getMessage());
                $results['errors'][] = [
                    'data' => $change,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get changes that need to be synced
     */
    public function getUnsyncedData(string $modelClass, array $syncedIds = [], string $lastSync = null): array
    {
        if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            throw new \InvalidArgumentException("Invalid model class: {$modelClass}");
        }

        $query = $modelClass::query();
        
        // Exclude already synced records
        if (!empty($syncedIds)) {
            $query->whereNotIn('id', $syncedIds);
        }
        
        // Only get records created/updated since last sync
        if ($lastSync) {
            $query->where(function($q) use ($lastSync) {
                $q->where('created_at', '>', $lastSync)
                  ->orWhere('updated_at', '>', $lastSync);
            });
        }
        
        // For large datasets, consider chunking
        return $query->get()->toArray();
    }

    /**
     * Resolve sync conflicts
     */
    public function resolveConflict(string $modelClass, array $localData, array $serverData, string $resolution): array
    {
        if (!in_array($resolution, ['server', 'client', 'merge'])) {
            throw new \InvalidArgumentException("Invalid resolution type. Must be 'server', 'client', or 'merge'.");
        }

        $model = $modelClass::find($serverData['id']);
        
        if (!$model) {
            throw new \RuntimeException("Server record not found");
        }

        try {
            DB::beginTransaction();
            
            switch ($resolution) {
                case 'server':
                    // Keep server version, do nothing
                    break;
                    
                case 'client':
                    // Overwrite with client version
                    $model->fill($localData);
                    $model->save();
                    break;
                    
                case 'merge':
                    // Merge changes, with client changes taking precedence
                    $merged = array_merge($serverData, $localData);
                    $model->fill($merged);
                    $model->save();
                    break;
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'data' => $model->toArray(),
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Batch sync multiple models
     */
    public function batchSync(array $syncData): array
    {
        $results = [];
        
        foreach ($syncData as $modelClass => $changes) {
            try {
                $results[$modelClass] = $this->syncChanges($changes, $modelClass);
            } catch (\Exception $e) {
                $results[$modelClass] = [
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }
}
