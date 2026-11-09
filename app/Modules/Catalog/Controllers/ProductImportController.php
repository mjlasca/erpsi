<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\ImportProducts;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Requests\ImportProductsRequest;
use App\Modules\Catalog\Support\CsvReader;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportController extends Controller
{
    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Inventory/Stock/Import');
    }

    public function store(
        ImportProductsRequest $request,
        CsvReader $reader,
        ImportProducts $importProducts,
    ): RedirectResponse {
        $this->authorize('create', Product::class);

        try {
            $rows = $reader->read($request->file('file'));
            $count = $importProducts->execute($rows);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        return redirect()
            ->route('catalog.products.index')
            ->with('success', "{$count} productos importados correctamente.");
    }

    public function template(): StreamedResponse
    {
        $this->authorize('create', Product::class);

        $headers = [
            'sku',
            'name',
            'description',
            'category_name',
            'unit_abbreviation',
            'product_type',
            'brand',
            'barcode',
            'cost_price',
            'sale_price',
            'min_stock',
            'is_inventory',
            'track_lots',
            'is_active',
        ];

        $example = [
            'PROD-001',
            'Producto de ejemplo',
            'Descripción opcional',
            '',
            'EA',
            'GOODS',
            'Marca',
            '',
            '10.00',
            '20.00',
            '0',
            '1',
            '0',
            '1',
        ];

        $content = "\xEF\xBB\xBF".implode(',', $headers)."\n".implode(',', $example)."\n";

        return response()->streamDownload(
            fn () => print $content,
            'plantilla-productos.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}
