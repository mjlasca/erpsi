<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Enums\ProductType;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductCategory;
use App\Modules\Catalog\Models\Unit;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportProducts
{
    /**
     * @param  array<int, array<string, string>>  $rows
     */
    public function execute(array $rows): int
    {
        $units = Unit::query()
            ->get()
            ->keyBy(fn (Unit $unit): string => mb_strtoupper($unit->abbreviation));

        $categories = ProductCategory::query()
            ->get()
            ->keyBy(fn (ProductCategory $category): string => mb_strtolower($category->name));

        $existingSkus = Product::query()->pluck('sku')
            ->map(fn (string $sku): string => mb_strtolower($sku))
            ->all();
        $existingBarcodes = Product::query()->whereNotNull('barcode')->pluck('barcode')
            ->map(fn (string $barcode): string => mb_strtolower($barcode))
            ->all();

        $seenSkus = [];
        $seenBarcodes = [];
        $products = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $prefix = "Fila {$line}: ";

            $sku = trim($row['sku'] ?? '');
            $name = trim($row['name'] ?? '');
            $unitAbbreviation = mb_strtoupper(trim($row['unit_abbreviation'] ?? ''));
            $type = ProductType::tryFrom(mb_strtoupper(trim($row['product_type'] ?? '')));
            $categoryName = mb_strtolower(trim($row['category_name'] ?? ''));
            $barcode = trim($row['barcode'] ?? '');

            if ($sku === '') {
                $errors[] = $prefix.'el SKU es obligatorio.';

                continue;
            }

            $skuKey = mb_strtolower($sku);

            if (isset($seenSkus[$skuKey])) {
                $errors[] = $prefix."el SKU {$sku} está repetido en el archivo.";
            }

            if (in_array($skuKey, $existingSkus, true)) {
                $errors[] = $prefix."el SKU {$sku} ya existe.";
            }

            $seenSkus[$skuKey] = true;

            if ($name === '') {
                $errors[] = $prefix.'el nombre es obligatorio.';
            }

            if (! $type instanceof ProductType) {
                $errors[] = $prefix.'product_type debe ser GOODS, SERVICE o BUNDLE.';
            }

            $unit = $units->get($unitAbbreviation);

            if ($unit === null) {
                $errors[] = $prefix."la unidad {$unitAbbreviation} no existe.";
            }

            $category = null;

            if ($categoryName !== '') {
                $category = $categories->get($categoryName);

                if ($category === null) {
                    $errors[] = $prefix."la categoría {$categoryName} no existe.";
                }
            }

            $costPrice = trim($row['cost_price'] ?? '');
            $salePrice = trim($row['sale_price'] ?? '');
            $minimumStock = trim($row['min_stock'] ?? '');

            if (! is_numeric($costPrice) || (float) $costPrice < 0) {
                $errors[] = $prefix.'cost_price debe ser un número mayor o igual a cero.';
            }

            if (! is_numeric($salePrice) || (float) $salePrice < 0) {
                $errors[] = $prefix.'sale_price debe ser un número mayor o igual a cero.';
            }

            if ($minimumStock !== '' && (! is_numeric($minimumStock) || (float) $minimumStock < 0)) {
                $errors[] = $prefix.'min_stock debe ser un número mayor o igual a cero.';
            }

            $isInventory = $this->boolean(
                $row['is_inventory'] ?? '',
                $type !== ProductType::Service,
            );
            $trackLots = $this->boolean($row['track_lots'] ?? '', false);
            $isActive = $this->boolean($row['is_active'] ?? '', true);

            if ($type === ProductType::Service && $isInventory) {
                $errors[] = $prefix.'un servicio no puede manejar inventario.';
            }

            if ($trackLots && ! $isInventory) {
                $errors[] = $prefix.'solo un producto inventariable puede manejar lotes.';
            }

            if ($barcode !== '') {
                $barcodeKey = mb_strtolower($barcode);

                if (isset($seenBarcodes[$barcodeKey]) || in_array($barcodeKey, $existingBarcodes, true)) {
                    $errors[] = $prefix."el código de barras {$barcode} ya está registrado.";
                }

                $seenBarcodes[$barcodeKey] = true;
            }

            if ($type instanceof ProductType && $unit !== null && is_numeric($costPrice) && is_numeric($salePrice)) {
                $products[] = [
                    'sku' => $sku,
                    'name' => $name,
                    'description' => trim($row['description'] ?? '') ?: null,
                    'category_id' => $category?->id,
                    'unit_id' => $unit->id,
                    'product_type' => $type,
                    'brand' => trim($row['brand'] ?? '') ?: null,
                    'barcode' => $barcode !== '' ? $barcode : null,
                    'cost_price' => Decimal::round2($costPrice),
                    'sale_price' => Decimal::round2($salePrice),
                    'min_stock' => Decimal::round3($minimumStock !== '' ? $minimumStock : '0'),
                    'is_inventory' => $isInventory,
                    'track_lots' => $trackLots,
                    'is_active' => $isActive,
                ];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'file' => implode(' ', array_slice($errors, 0, 5)),
            ]);
        }

        DB::transaction(function () use ($products): void {
            foreach ($products as $product) {
                Product::query()->create($product);
            }
        });

        return count($products);
    }

    protected function boolean(string $value, bool $default): bool
    {
        $value = mb_strtolower(trim($value));

        if ($value === '') {
            return $default;
        }

        return in_array($value, ['1', 'true', 'si', 'sí', 'yes'], true);
    }
}
