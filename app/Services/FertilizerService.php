<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;

class FertilizerService
{
    // Conversion rates (kg to other units)
    protected $conversionRates = [
        'kg' => 1,
        'gr' => 1000,        // 1 kg = 1000 gr
        'ons' => 10,         // 1 kg = 10 ons
        'kwintal' => 0.01,   // 1 kg = 0.01 kwintal
        'ton' => 0.001,      // 1 kg = 0.001 ton
        'sak' => 0.05,       // 1 sak = 50 kg
        'karung' => 0.05,    // 1 karung = 50 kg
    ];


    /**
     * Check for expired or near-expiry products
     */
    public function checkProductExpiry($daysBefore = 30)
    {
        $today = Carbon::today();
        $thresholdDate = $today->copy()->addDays($daysBefore);

        return [
            'expired' => Product::whereNotNull('expiry_date')
                ->where('expiry_date', '<', $today)
                ->where('stock_quantity', '>', 0)
                ->get(),
                
            'near_expiry' => Product::whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [$today, $thresholdDate])
                ->where('stock_quantity', '>', 0)
                ->get(),
                
            'expired_count' => Product::whereNotNull('expiry_date')
                ->where('expiry_date', '<', $today)
                ->where('stock_quantity', '>', 0)
                ->count(),
                
            'near_expiry_count' => Product::whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [$today, $thresholdDate])
                ->where('stock_quantity', '>', 0)
                ->count(),
        ];
    }

    /**
     * Convert between different units
     */
    public function convertUnit(float $value, string $fromUnit, string $toUnit): float
    {
        $fromUnit = strtolower($fromUnit);
        $toUnit = strtolower($toUnit);

        if (!isset($this->conversionRates[$fromUnit]) || !isset($this->conversionRates[$toUnit])) {
            throw new \InvalidArgumentException("Unsupported unit conversion: $fromUnit to $toUnit");
        }

        // Convert to base unit (kg) first, then to target unit
        $baseValue = $value / $this->conversionRates[$fromUnit];
        return $baseValue * $this->conversionRates[$toUnit];
    }

    /**
     * Get recommended fertilizers based on plant type
     */
    public function getRecommendations(string $plantType, string $soilType = null, string $growthStage = null): array
    {
        // This is a simplified example. In a real application, this would likely query a database
        $recommendations = [
            'padi' => [
                ['product_id' => 1, 'name' => 'Urea', 'recommended_amount' => 200, 'unit' => 'kg/ha'],
                ['product_id' => 2, 'name' => 'SP-36', 'recommended_amount' => 100, 'unit' => 'kg/ha'],
                ['product_id' => 3, 'name' => 'KCl', 'recommended_amount' => 50, 'unit' => 'kg/ha'],
            ],
            'jagung' => [
                ['product_id' => 1, 'name' => 'Urea', 'recommended_amount' => 300, 'unit' => 'kg/ha'],
                ['product_id' => 4, 'name' => 'NPK Phonska', 'recommended_amount' => 200, 'unit' => 'kg/ha'],
            ],
            'kedelai' => [
                ['product_id' => 2, 'name' => 'SP-36', 'recommended_amount' => 100, 'unit' => 'kg/ha'],
                ['product_id' => 5, 'name' => 'Kaptan', 'recommended_amount' => 200, 'unit' => 'kg/ha'],
            ],
        ];

        $plantType = strtolower($plantType);
        return $recommendations[$plantType] ?? [];
    }

    /**
     * Calculate fertilizer requirement for a given area
     */
    public function calculateRequirement(int $productId, float $area, string $areaUnit = 'ha'): array
    {
        // In a real application, this would query the product and its recommended usage
        // This is a simplified example
        $product = Product::find($productId);
        
        if (!$product) {
            throw new \InvalidArgumentException("Product not found");
        }

        // Default recommendation (kg/ha)
        $recommendation = 200; // Default value, should come from product or recommendation system
        
        // Convert area to hectares if needed
        $areaInHectares = $this->convertAreaToHectares($area, $areaUnit);
        
        // Calculate required amount
        $requiredAmount = $recommendation * $areaInHectares;
        
        // Convert to product's selling unit
        $sellingUnit = $product->unit ?? 'kg';
        $requiredInSellingUnit = $this->convertUnit($requiredAmount, 'kg', $sellingUnit);
        
        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'area' => $area,
            'area_unit' => $areaUnit,
            'recommendation_rate' => $recommendation . ' kg/ha',
            'required_amount' => $requiredInSellingUnit,
            'required_unit' => $sellingUnit,
            'available_stock' => $product->stock_quantity,
            'sufficient' => $product->stock_quantity >= $requiredInSellingUnit,
            'deficit' => max(0, $requiredInSellingUnit - $product->stock_quantity),
        ];
    }
    
    /**
     * Convert area to hectares
     */
    protected function convertAreaToHectares(float $value, string $unit): float
    {
        $conversion = [
            'ha' => 1,
            'm2' => 0.0001,      // 1 m² = 0.0001 ha
            'are' => 0.01,       // 1 are = 0.01 ha
            'km2' => 100,        // 1 km² = 100 ha
            'hectare' => 1,
        ];
        
        $unit = strtolower($unit);
        
        if (!isset($conversion[$unit])) {
            throw new \InvalidArgumentException("Unsupported area unit: $unit");
        }
        
        return $value * $conversion[$unit];
    }
}
