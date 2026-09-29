<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    // public function collection()
    // {
    //     return Product::select(
    //         'name',
    //         'hsn_code',
    //         'gst_rate',
    //         'mrp',
    //         'rate',
    //         'reorder_level',
    //         'description',
    //         'current_stock'
    //     )->get();
    // }
    public function collection()
{
    return Product::select(
        'name',
        'hsn_code',
        'gst_rate',
        'mrp',
        'rate',
        'reorder_level',
        'description',
        'current_stock'
    )->get()->map(function ($product) {
        $product->gst_rate = number_format((float) $product->gst_rate, 2, '.', '');
        return $product;
    });
}

    public function headings(): array
    {
        return [
            'name',
            'hsn_code',
            'gst_rate',
            'mrp',
            'rate',
            'reorder_level',
            'description',
            'current_stock',
        ];
    }
}