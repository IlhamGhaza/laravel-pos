<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity_ordered',      // Sesuai dengan migrasi 'quantity_ordered'
        'quantity_received',     // Tambahkan jika bisa diisi langsung
        'unit_cost_price',       // Sesuai dengan migrasi 'unit_cost_price'
        'subtotal',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'unit_cost_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * Boot method
     */
    protected static function booted(): void
    {
        static::saving(function (PurchaseOrderItem $item) {
            if ($item->isDirty('quantity_ordered') || $item->isDirty('unit_cost_price')) {
                $item->subtotal = $item->quantity_ordered * $item->unit_cost_price;
            }
        });

        static::saved(function (PurchaseOrderItem $item) {
            // Update Purchase Order Total
            if ($item->purchaseOrder) { // Pastikan relasi ada
                $item->purchaseOrder->calculateTotal();
            }

            // Buat Inventory Log jika quantity_received berubah dan positif (menandakan penerimaan)
            // Diasumsikan 'quantity_received' adalah pemicu stok masuk.
            // Perhatikan: Jika 'quantity_received' bisa diupdate berkali-kali (penerimaan parsial),
            // logika 'quantityChange' ini penting.
            if ($item->isDirty('quantity_received') && $item->quantity_received > 0) {
                $originalQuantityReceived = $item->getOriginal('quantity_received') ?? 0;
                $quantityChange = $item->quantity_received - $originalQuantityReceived;

                if ($quantityChange > 0) { // Hanya catat jika ada penambahan aktual
                    InventoryLog::create([
                        'product_id' => $item->product_id,
                        'purchase_order_item_id' => $item->id,
                        //user_id = current user saat ubah
                        'user_id' =>auth()->user_id,
                        'type' => InventoryLog::TYPE_RESTOCK, // Atau tipe yang lebih spesifik seperti 'purchase_receipt'
                        'quantity_change' => $quantityChange,
                        // 'stock_before_change' => null, // Isi jika relevan dan bisa didapatkan
                        // 'stock_after_change' => null,  // Isi jika relevan dan bisa didapatkan
                        'reason' => 'Penerimaan barang untuk PO: ' . ($item->purchaseOrder->po_number ?? 'N/A'),
                    ]);

                    // Jika aplikasi ini MENGELOLA stok secara lokal (yang mana TIDAK sesuai skenario kita):
                    // if ($item->product) {
                    //     $item->product->addStock($quantityChange);
                    // }
                }
            }
        });
    }
}
