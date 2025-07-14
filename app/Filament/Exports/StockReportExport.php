<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class StockReportExport extends BaseExport implements WithTitle, WithColumnFormatting
{
    protected $title = 'Laporan Stok Produk';
    protected $headings = [
        'Kode',
        'Nama Produk',
        'Kategori',
        'Stok Tersedia',
        'Satuan',
        'Harga Beli',
        'Harga Jual',
        'Status Stok',
        'Terakhir Diupdate'
    ];

    public function __construct($query)
    {
        parent::__construct($query->with(['category']));
    }

    public function map($product): array
    {
        return [
            $product->code,
            $product->name,
            $product->category ? $product->category->name : '-',
            $product->stock_quantity,
            $product->unit,
            $product->purchase_price,
            $product->selling_price,
            $this->getStockStatus($product->stock_quantity, $product->minimum_stock_level),
            $product->updated_at->format('d/m/Y H:i'),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '#,##0.00',
            'F' => '\"Rp\"#,##0.00',
            'G' => '\"Rp\"#,##0.00',
        ];
    }

    protected function getStockStatus($currentStock, $minimumStock): string
    {
        if ($currentStock <= 0) {
            return 'Habis';
        }

        if ($currentStock <= $minimumStock) {
            return 'Hampir Habis';
        }

        return 'Tersedia';
    }
}
