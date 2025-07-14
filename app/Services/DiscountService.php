<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DiscountService
{
    /**
     * Apply discounts to order items
     *
     * @param Collection $items
     * @param array $discounts
     * @return array
     */
    public function applyDiscounts(Collection $items, array $discounts): array
    {
        $discounts = collect($discounts);
        $appliedDiscounts = [];
        $totalDiscount = 0;

        // Process each discount
        foreach ($discounts as $discountData) {
            $discount = Discount::find($discountData['id']);
            if (!$discount || !$this->isDiscountValid($discount)) {
                continue;
            }

            $discountResult = $this->applyDiscount($items, $discount);

            if ($discountResult['discount_amount'] > 0) {
                $appliedDiscounts[] = [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'type' => $discount->type,
                    'value' => $discount->value,
                    'discount_amount' => $discountResult['discount_amount'],
                    'items_affected' => $discountResult['items_affected']
                ];
                $totalDiscount += $discountResult['discount_amount'];
            }
        }

        return [
            'applied_discounts' => $appliedDiscounts,
            'total_discount' => $totalDiscount
        ];
    }

    /**
     * Check if discount is valid based on its conditions
     *
     * @param Discount $discount
     * @return bool
     */
    protected function isDiscountValid(Discount $discount): bool
    {
        $now = Carbon::now();
        
        if (!$this->isWithinValidDateRange($discount, $now)) {
            return false;
        }
        
        if (!$this->isWithinValidTimeRange($discount, $now)) {
            return false;
        }
        
        if ($this->hasExceededUsageLimit($discount)) {
            return false;
        }
        
        return $discount->status === 'active';
    }
    
    /**
     * Check if current time is within discount's valid date range
     */
    protected function isWithinValidDateRange(Discount $discount, Carbon $now): bool
    {
        if ($discount->start_date && $now->lt($discount->start_date)) {
            return false;
        }
        
        if ($discount->expired_date && $now->gt($discount->expired_date)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if current time is within discount's valid time range
     */
    protected function isWithinValidTimeRange(Discount $discount, Carbon $now): bool
    {
        if (!$discount->start_time || !$discount->end_time) {
            return true;
        }
        
        $currentTime = $now->format('H:i:s');
        return !($currentTime < $discount->start_time || $currentTime > $discount->end_time);
    }
    
    /**
     * Check if discount has exceeded its usage limit
     */
    protected function hasExceededUsageLimit(Discount $discount): bool
    {
        return $discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit;
    }

    /**
     * Apply a single discount to order items
     *
     * @param Collection $items
     * @param Discount $discount
     * @return array
     */
    protected function applyDiscount(Collection $items, Discount $discount): array
    {
        $applicableItems = $this->getApplicableItems($items, $discount);
        
        if ($applicableItems->isEmpty()) {
            return ['discount_amount' => 0, 'items_affected' => []];
        }

        $discountHandlers = [
            'fixed' => 'applyFixedDiscount',
            'percentage' => 'applyPercentageDiscount',
            'buy_x_get_y' => 'applyBuyXGetYDiscount',
            'quantity_based' => 'applyQuantityBasedDiscount',
            'bulk_discount' => 'applyBulkDiscount',
        ];

        $discountAmount = 0;
        $itemsAffected = [];

        if (isset($discountHandlers[$discount->type])) {
            $handler = $discountHandlers[$discount->type];
            $discountData = $this->$handler($applicableItems, $discount);
            $discountAmount = $discountData['discount_amount'];
            $itemsAffected = $discountData['items_affected'];
        }
        
        return [
            'discount_amount' => $discountAmount,
            'items_affected' => $itemsAffected
        ];
    }

    /**
     * Get items that are applicable for the discount
     *
     * @param Collection $items
     * @param Discount $discount
     * @return Collection
     */
    protected function getApplicableItems(Collection $items, Discount $discount): Collection
    {
        if ($discount->apply_to === 'all') {
            return $items;
        }

        $applicableItemIds = $discount->applicable_items;
        
        if (empty($applicableItemIds)) {
            return collect();
        }

        $itemIds = $this->getApplicableItemIds($discount, $applicableItemIds);
        
        if (empty($itemIds)) {
            return collect();
        }

        return $this->filterItemsByApplicability($items, $discount, $itemIds);
    }
    
    /**
     * Get applicable item IDs based on discount type
     */
    protected function getApplicableItemIds(Discount $discount, array $applicableItemIds): array
    {
        $key = $discount->apply_to === 'category' ? 'category_ids' : 'product_ids';
        return $applicableItemIds[$key] ?? [];
    }
    
    /**
     * Filter items based on their applicability to the discount
     */
    protected function filterItemsByApplicability(Collection $items, Discount $discount, array $itemIds): Collection
    {
        return $items->filter(function ($item) use ($discount, $itemIds) {
            if ($discount->apply_to === 'category') {
                return $item->product && in_array($item->product->category_id, $itemIds);
            }
            
            return in_array($item->product_id, $itemIds);
        });
    }

    /**
     * Apply fixed amount discount to items
     */
    protected function applyFixedDiscount(Collection $items, Discount $discount): array
    {
        $discountAmount = 0;
        $itemsAffected = [];
        
        foreach ($items as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $itemDiscount = min($discount->value, $itemTotal);
            $discountAmount += $itemDiscount;
            
            $itemsAffected[] = [
                'product_id' => $item['product_id'],
                'original_price' => $itemTotal,
                'discount_amount' => $itemDiscount,
                'final_price' => $itemTotal - $itemDiscount
            ];
        }
        
        return [
            'discount_amount' => $discountAmount,
            'items_affected' => $itemsAffected
        ];
    }
    
    /**
     * Apply percentage discount to items
     */
    protected function applyPercentageDiscount(Collection $items, Discount $discount): array
    {
        $discountAmount = 0;
        $itemsAffected = [];
        
        foreach ($items as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $itemDiscount = $itemTotal * ($discount->value / 100);
            $discountAmount += $itemDiscount;
            
            $itemsAffected[] = [
                'product_id' => $item['product_id'],
                'original_price' => $itemTotal,
                'discount_amount' => $itemDiscount,
                'final_price' => $itemTotal - $itemDiscount
            ];
        }
        
        return [
            'discount_amount' => $discountAmount,
            'items_affected' => $itemsAffected
        ];
    }
    
    /**
     * Apply buy X get Y discount
     */
    protected function applyBuyXGetYDiscount(Collection $items, Discount $discount): array
    {
        if (!$discount->buy_quantity || !$discount->get_quantity) {
            return ['discount_amount' => 0, 'items_affected' => []];
        }
        
        $discountAmount = 0;
        $itemsAffected = [];
        
        foreach ($items as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $sets = floor($item['quantity'] / ($discount->buy_quantity + $discount->get_quantity));
            $discountedItems = $sets * $discount->get_quantity;
            $itemDiscount = $discountedItems * $item['price'];
            $discountAmount += $itemDiscount;
            
            $itemsAffected[] = [
                'product_id' => $item['product_id'],
                'original_price' => $itemTotal,
                'discount_amount' => $itemDiscount,
                'final_price' => $itemTotal - $itemDiscount,
                'details' => [
                    'buy_quantity' => $discount->buy_quantity,
                    'get_quantity' => $discount->get_quantity,
                    'sets_applied' => $sets
                ]
            ];
        }
        
        return [
            'discount_amount' => $discountAmount,
            'items_affected' => $itemsAffected
        ];
    }
    
    /**
     * Apply quantity based discount
     */
    protected function applyQuantityBasedDiscount(Collection $items, Discount $discount): array
    {
        if (!$discount->min_quantity) {
            return ['discount_amount' => 0, 'items_affected' => []];
        }
        
        $discountAmount = 0;
        $itemsAffected = [];
        
        foreach ($items as $item) {
            if ($item['quantity'] < $discount->min_quantity) {
                continue;
            }
            
            $itemTotal = $item['price'] * $item['quantity'];
            $itemDiscount = $discount->type === 'percentage' 
                ? $itemTotal * ($discount->value / 100)
                : $discount->value * $item['quantity'];
                
            $discountAmount += $itemDiscount;
            
            $itemsAffected[] = [
                'product_id' => $item['product_id'],
                'original_price' => $itemTotal,
                'discount_amount' => $itemDiscount,
                'final_price' => $itemTotal - $itemDiscount,
                'min_quantity' => $discount->min_quantity
            ];
        }
        
        return [
            'discount_amount' => $discountAmount,
            'items_affected' => $itemsAffected
        ];
    }
    
    /**
     * Apply bulk discount tiers
     */
    protected function applyBulkDiscount(Collection $items, Discount $discount): array
    {
        $tiers = $discount->quantity_tiers;
        if (empty($tiers) || !is_array($tiers)) {
            return ['discount_amount' => 0, 'items_affected' => []];
        }
        
        // Sort tiers by min_quantity in descending order
        usort($tiers, function($a, $b) {
            return $b['min_quantity'] <=> $a['min_quantity'];
        });
        
        $discountAmount = 0;
        $itemsAffected = [];
        
        foreach ($items as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $itemDiscount = 0;
            $appliedTier = null;
            
            // Find the highest applicable tier
            foreach ($tiers as $tier) {
                if ($item['quantity'] >= $tier['min_quantity']) {
                    $appliedTier = $tier;
                    break;
                }
            }
            
            if ($appliedTier) {
                $itemDiscount = $appliedTier['type'] === 'percentage'
                    ? $itemTotal * ($appliedTier['value'] / 100)
                    : $appliedTier['value'] * $item['quantity'];
                    
                $discountAmount += $itemDiscount;
                
                $itemsAffected[] = [
                    'product_id' => $item['product_id'],
                    'original_price' => $itemTotal,
                    'discount_amount' => $itemDiscount,
                    'final_price' => $itemTotal - $itemDiscount,
                    'tier' => $appliedTier
                ];
            }
        }
        
        return [
            'discount_amount' => $discountAmount,
            'items_affected' => $itemsAffected
        ];
    }
}
