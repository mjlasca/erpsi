<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Modules\Catalog\Enums\ProductType;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductCategory;
use App\Modules\Catalog\Models\Unit;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->where('slug', 'default')->first()
            ?? Tenant::query()->create([
                'name' => 'Empresa principal',
                'slug' => 'default',
                'locale' => config('app.locale'),
                'timezone' => config('app.timezone'),
            ]);

        app(TenantContext::class)->set($tenant);
        app()->setLocale($tenant->locale);

        if (Unit::query()->count() === 0) {
            $this->call(UnitSeeder::class);
        }

        $categories = collect([
            'Tecnología' => 'Equipos, accesorios y componentes informáticos.',
            'Mobiliario' => 'Muebles y accesorios para espacios de trabajo.',
            'Papelería' => 'Artículos de oficina, escritura y archivo.',
            'Limpieza' => 'Insumos y herramientas para limpieza general.',
            'Empaque' => 'Materiales para embalaje y preparación de envíos.',
            'Herramientas' => 'Herramientas manuales y equipo eléctrico.',
        ])->mapWithKeys(fn (string $description, string $name): array => [
            $name => ProductCategory::query()->firstOrCreate(
                ['name' => $name],
                ['description' => $description, 'is_active' => true],
            ),
        ]);

        $units = Unit::query()->pluck('id', 'abbreviation');
        $products = $this->products();

        foreach ($products as $index => $data) {
            Product::query()->updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'category_id' => $categories->get($data['category'])?->id,
                    'unit_id' => $units->get($data['unit']),
                    'product_type' => ProductType::Goods,
                    'brand' => $data['brand'] ?? null,
                    'barcode' => '7501000000'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'cost_price' => $data['cost'],
                    'sale_price' => $data['price'],
                    'min_stock' => $data['min_stock'],
                    'is_inventory' => true,
                    'track_lots' => false,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function products(): array
    {
        return [
            ['sku' => 'DEMO-001', 'name' => 'Laptop empresarial 14"', 'category' => 'Tecnología', 'unit' => 'EA', 'brand' => 'Lenovo', 'cost' => '14500.00', 'price' => '18990.00', 'min_stock' => '2.000'],
            ['sku' => 'DEMO-002', 'name' => 'Monitor LED 24" Full HD', 'category' => 'Tecnología', 'unit' => 'EA', 'brand' => 'Samsung', 'cost' => '2800.00', 'price' => '3990.00', 'min_stock' => '3.000'],
            ['sku' => 'DEMO-003', 'name' => 'Teclado inalámbrico', 'category' => 'Tecnología', 'unit' => 'EA', 'brand' => 'Logitech', 'cost' => '420.00', 'price' => '699.00', 'min_stock' => '10.000'],
            ['sku' => 'DEMO-004', 'name' => 'Mouse inalámbrico', 'category' => 'Tecnología', 'unit' => 'EA', 'brand' => 'Logitech', 'cost' => '180.00', 'price' => '329.00', 'min_stock' => '15.000'],
            ['sku' => 'DEMO-005', 'name' => 'Docking station USB-C', 'category' => 'Tecnología', 'unit' => 'EA', 'brand' => 'HP', 'cost' => '1500.00', 'price' => '2290.00', 'min_stock' => '3.000'],
            ['sku' => 'DEMO-006', 'name' => 'Cámara web Full HD', 'category' => 'Tecnología', 'unit' => 'EA', 'brand' => 'Logitech', 'cost' => '650.00', 'price' => '1090.00', 'min_stock' => '5.000'],
            ['sku' => 'DEMO-007', 'name' => 'Audífonos con micrófono', 'category' => 'Tecnología', 'unit' => 'EA', 'brand' => 'Jabra', 'cost' => '380.00', 'price' => '649.00', 'min_stock' => '8.000'],
            ['sku' => 'DEMO-008', 'name' => 'Disco SSD 1 TB', 'category' => 'Tecnología', 'unit' => 'EA', 'brand' => 'Kingston', 'cost' => '1250.00', 'price' => '1899.00', 'min_stock' => '5.000'],
            ['sku' => 'DEMO-009', 'name' => 'Memoria USB 64 GB', 'category' => 'Tecnología', 'unit' => 'EA', 'brand' => 'SanDisk', 'cost' => '120.00', 'price' => '249.00', 'min_stock' => '20.000'],
            ['sku' => 'DEMO-010', 'name' => 'Router Wi-Fi 6', 'category' => 'Tecnología', 'unit' => 'EA', 'brand' => 'TP-Link', 'cost' => '1800.00', 'price' => '2790.00', 'min_stock' => '3.000'],

            ['sku' => 'DEMO-011', 'name' => 'Silla ergonómica', 'category' => 'Mobiliario', 'unit' => 'EA', 'brand' => 'ErgoLine', 'cost' => '3200.00', 'price' => '4990.00', 'min_stock' => '2.000'],
            ['sku' => 'DEMO-012', 'name' => 'Escritorio ajustable', 'category' => 'Mobiliario', 'unit' => 'EA', 'brand' => 'ErgoLine', 'cost' => '5200.00', 'price' => '7990.00', 'min_stock' => '1.000'],
            ['sku' => 'DEMO-013', 'name' => 'Lámpara de escritorio LED', 'category' => 'Mobiliario', 'unit' => 'EA', 'brand' => 'Philips', 'cost' => '280.00', 'price' => '499.00', 'min_stock' => '8.000'],

            ['sku' => 'DEMO-014', 'name' => 'Papel bond A4 500 hojas', 'category' => 'Papelería', 'unit' => 'EA', 'brand' => 'Scribe', 'cost' => '95.00', 'price' => '159.00', 'min_stock' => '30.000'],
            ['sku' => 'DEMO-015', 'name' => 'Bolígrafo negro', 'category' => 'Papelería', 'unit' => 'EA', 'brand' => 'Bic', 'cost' => '5.00', 'price' => '15.00', 'min_stock' => '100.000'],
            ['sku' => 'DEMO-016', 'name' => 'Cuaderno profesional', 'category' => 'Papelería', 'unit' => 'EA', 'brand' => 'Scribe', 'cost' => '28.00', 'price' => '59.00', 'min_stock' => '50.000'],
            ['sku' => 'DEMO-017', 'name' => 'Archivero de palanca', 'category' => 'Papelería', 'unit' => 'EA', 'brand' => 'OfficeMax', 'cost' => '180.00', 'price' => '299.00', 'min_stock' => '10.000'],
            ['sku' => 'DEMO-018', 'name' => 'Caja de archivo', 'category' => 'Papelería', 'unit' => 'EA', 'brand' => 'OfficeMax', 'cost' => '45.00', 'price' => '89.00', 'min_stock' => '25.000'],

            ['sku' => 'DEMO-019', 'name' => 'Detergente multiusos 5 L', 'category' => 'Limpieza', 'unit' => 'EA', 'brand' => 'Pinol', 'cost' => '145.00', 'price' => '249.00', 'min_stock' => '12.000'],
            ['sku' => 'DEMO-020', 'name' => 'Desinfectante 1 L', 'category' => 'Limpieza', 'unit' => 'EA', 'brand' => 'Cloralex', 'cost' => '55.00', 'price' => '99.00', 'min_stock' => '20.000'],
            ['sku' => 'DEMO-021', 'name' => 'Jabón líquido para manos', 'category' => 'Limpieza', 'unit' => 'EA', 'brand' => 'Softcare', 'cost' => '48.00', 'price' => '89.00', 'min_stock' => '24.000'],
            ['sku' => 'DEMO-022', 'name' => 'Paños de microfibra', 'category' => 'Limpieza', 'unit' => 'PCS', 'brand' => 'CleanPro', 'cost' => '12.00', 'price' => '29.00', 'min_stock' => '50.000'],
            ['sku' => 'DEMO-023', 'name' => 'Escoba industrial', 'category' => 'Limpieza', 'unit' => 'EA', 'brand' => 'CleanPro', 'cost' => '85.00', 'price' => '149.00', 'min_stock' => '10.000'],
            ['sku' => 'DEMO-024', 'name' => 'Guantes de nitrilo', 'category' => 'Limpieza', 'unit' => 'PCS', 'brand' => 'SafeHands', 'cost' => '120.00', 'price' => '199.00', 'min_stock' => '15.000'],

            ['sku' => 'DEMO-025', 'name' => 'Cinta adhesiva industrial', 'category' => 'Empaque', 'unit' => 'EA', 'brand' => 'Tesa', 'cost' => '22.00', 'price' => '45.00', 'min_stock' => '40.000'],
            ['sku' => 'DEMO-026', 'name' => 'Caja de cartón mediana', 'category' => 'Empaque', 'unit' => 'EA', 'brand' => 'BoxPack', 'cost' => '18.00', 'price' => '39.00', 'min_stock' => '60.000'],
            ['sku' => 'DEMO-027', 'name' => 'Film plástico para embalaje', 'category' => 'Empaque', 'unit' => 'EA', 'brand' => 'BoxPack', 'cost' => '65.00', 'price' => '119.00', 'min_stock' => '20.000'],
            ['sku' => 'DEMO-028', 'name' => 'Etiquetas adhesivas', 'category' => 'Empaque', 'unit' => 'PCS', 'brand' => 'Avery', 'cost' => '35.00', 'price' => '69.00', 'min_stock' => '25.000'],

            ['sku' => 'DEMO-029', 'name' => 'Extensión eléctrica 5 m', 'category' => 'Herramientas', 'unit' => 'EA', 'brand' => 'Voltech', 'cost' => '210.00', 'price' => '349.00', 'min_stock' => '10.000'],
            ['sku' => 'DEMO-030', 'name' => 'Multímetro digital', 'category' => 'Herramientas', 'unit' => 'EA', 'brand' => 'Fluke', 'cost' => '480.00', 'price' => '799.00', 'min_stock' => '5.000'],
        ];
    }
}
