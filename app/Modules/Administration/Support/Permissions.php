<?php

namespace App\Modules\Administration\Support;

/**
 * Central catalogue of permissions used by policies and the role editor.
 */
final class Permissions
{
    /**
     * @var array<string, array<string, string>>
     */
    public const GROUPS = [
        'Catálogo' => [
            'catalog.products.view' => 'Ver productos',
            'catalog.products.create' => 'Crear productos',
            'catalog.products.update' => 'Editar productos',
            'catalog.products.delete' => 'Eliminar productos',
            'catalog.categories.view' => 'Ver categorías',
            'catalog.categories.create' => 'Crear categorías',
            'catalog.categories.update' => 'Editar categorías',
            'catalog.categories.delete' => 'Eliminar categorías',
            'catalog.units.view' => 'Ver unidades',
            'catalog.units.create' => 'Crear unidades',
            'catalog.units.update' => 'Editar unidades',
            'catalog.units.delete' => 'Eliminar unidades',
            'catalog.variants.view' => 'Ver variantes',
            'catalog.variants.create' => 'Crear variantes',
            'catalog.variants.update' => 'Editar variantes',
            'catalog.variants.delete' => 'Eliminar variantes',
            'catalog.attributes.view' => 'Ver atributos',
            'catalog.attributes.create' => 'Crear atributos',
            'catalog.attributes.update' => 'Editar atributos',
            'catalog.attributes.delete' => 'Eliminar atributos',
        ],
        'Inventario' => [
            'inventory.warehouses.view' => 'Ver almacenes',
            'inventory.warehouses.create' => 'Crear almacenes',
            'inventory.warehouses.update' => 'Editar almacenes',
            'inventory.warehouses.delete' => 'Eliminar almacenes',
            'inventory.stock.view' => 'Ver existencias',
            'inventory.movements.view' => 'Ver movimientos',
            'inventory.movements.create' => 'Registrar movimientos',
            'inventory.lots.view' => 'Ver lotes',
            'inventory.lots.create' => 'Crear lotes',
            'inventory.lots.update' => 'Editar lotes',
            'inventory.lots.delete' => 'Eliminar lotes',
        ],
        'Compras' => [
            'purchasing.suppliers.view' => 'Ver proveedores',
            'purchasing.suppliers.create' => 'Crear proveedores',
            'purchasing.suppliers.update' => 'Editar proveedores',
            'purchasing.suppliers.delete' => 'Eliminar proveedores',
            'purchasing.purchases.view' => 'Ver compras',
            'purchasing.purchases.create' => 'Crear compras',
            'purchasing.purchases.update' => 'Editar compras',
            'purchasing.purchases.delete' => 'Eliminar compras',
            'purchasing.purchases.pay' => 'Pagar compras',
            'purchasing.purchases.cancel' => 'Cancelar compras',
        ],
        'Ventas' => [
            'sales.customers.view' => 'Ver clientes',
            'sales.customers.create' => 'Crear clientes',
            'sales.customers.update' => 'Editar clientes',
            'sales.customers.delete' => 'Eliminar clientes',
            'sales.sales.view' => 'Ver ventas',
            'sales.sales.create' => 'Crear ventas',
            'sales.sales.update' => 'Editar ventas',
            'sales.sales.delete' => 'Eliminar ventas',
            'sales.sales.pay' => 'Pagar ventas',
            'sales.sales.cancel' => 'Cancelar ventas',
        ],
        'Pagos' => [
            'payments.payments.view' => 'Ver pagos',
            'payments.payments.create' => 'Registrar pagos',
            'payments.payments.update' => 'Editar pagos',
            'payments.payments.delete' => 'Eliminar pagos',
            'payments.allocations.manage' => 'Aplicar y quitar pagos',
        ],
        'Caja' => [
            'cash.reconciliations.view' => 'Ver arqueos',
            'cash.reconciliations.create' => 'Crear arqueos',
            'cash.reconciliations.update' => 'Editar arqueos',
            'cash.reconciliations.delete' => 'Eliminar arqueos',
        ],
        'Administración' => [
            'administration.users.view' => 'Ver usuarios',
            'administration.users.create' => 'Crear usuarios',
            'administration.users.update' => 'Editar usuarios',
            'administration.users.delete' => 'Eliminar usuarios',
            'administration.roles.view' => 'Ver roles',
            'administration.roles.create' => 'Crear roles',
            'administration.roles.update' => 'Editar roles',
            'administration.roles.delete' => 'Eliminar roles',
            'administration.tax_rates.view' => 'Ver impuestos',
            'administration.tax_rates.create' => 'Crear impuestos',
            'administration.tax_rates.update' => 'Editar impuestos',
            'administration.tax_rates.delete' => 'Eliminar impuestos',
        ],
    ];

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_merge(...array_values(array_map(
            fn (array $group): array => array_keys($group),
            self::GROUPS,
        )));
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        return self::GROUPS;
    }
}
