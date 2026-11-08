import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    LayoutDashboard,
    Package,
    ShoppingCart,
    Receipt,
    Users,
    Settings,
    ChevronDown,
    Boxes,
    CreditCard,
    type LucideIcon,
} from 'lucide-react';
import { dashboard } from '@/routes';
import { index as productsIndex } from '@/routes/catalog/products';
import { index as categoriesIndex } from '@/routes/catalog/categories';
import { index as unitsIndex } from '@/routes/catalog/units';
import { index as variantsIndex } from '@/routes/catalog/variants';
import { index as attributesIndex } from '@/routes/catalog/attributes';
import { index as stockIndex } from '@/routes/inventory/stock';
import { index as movementsIndex } from '@/routes/inventory/movements';
import { index as lotsIndex } from '@/routes/inventory/lots';
import { index as warehousesIndex } from '@/routes/inventory/warehouses';
import { create as transferCreate } from '@/routes/inventory/transfers';
import { index as purchasesIndex } from '@/routes/purchasing/purchases';
import { index as suppliersIndex } from '@/routes/purchasing/suppliers';
import { index as salesIndex } from '@/routes/sales/sales';
import { index as customersIndex } from '@/routes/sales/customers';
import { index as paymentsIndex } from '@/routes/payments/payments';
import { index as rolesIndex } from '@/routes/admin/roles';
import { index as usersIndex } from '@/routes/admin/users';
import { index as taxRatesIndex } from '@/routes/admin/tax-rates';

interface MenuItem {
    label: string;
    href?: string;
    icon?: LucideIcon;
    children?: MenuItem[];
}

const menuItems: MenuItem[] = [
    {
        label: 'Dashboard',
        href: dashboard.url(),
        icon: LayoutDashboard,
    },
    {
        label: 'Catálogo',
        icon: Package,
        children: [
            { label: 'Productos', href: productsIndex.url() },
            { label: 'Categorías', href: categoriesIndex.url() },
            { label: 'Unidades', href: unitsIndex.url() },
            { label: 'Variantes', href: variantsIndex.url() },
            { label: 'Atributos', href: attributesIndex.url() },
        ],
    },
    {
        label: 'Inventario',
        icon: Boxes,
        children: [
            { label: 'Existencias', href: stockIndex.url() },
            { label: 'Movimientos', href: movementsIndex.url() },
            { label: 'Lotes', href: lotsIndex.url() },
            { label: 'Transferencias', href: transferCreate.url() },
            { label: 'Almacenes', href: warehousesIndex.url() },
        ],
    },
    {
        label: 'Compras',
        icon: ShoppingCart,
        children: [
            { label: 'Compras', href: purchasesIndex.url() },
            { label: 'Proveedores', href: suppliersIndex.url() },
        ],
    },
    {
        label: 'Ventas',
        icon: Receipt,
        children: [
            { label: 'Ventas', href: salesIndex.url() },
            { label: 'Clientes', href: customersIndex.url() },
        ],
    },
    {
        label: 'Pagos',
        href: paymentsIndex.url(),
        icon: CreditCard,
    },
    {
        label: 'Administración',
        icon: Settings,
        children: [
            { label: 'Usuarios', href: usersIndex.url() },
            { label: 'Roles', href: rolesIndex.url() },
            { label: 'Impuestos', href: taxRatesIndex.url() },
        ],
    },
];

interface SidebarMenuItemProps {
    item: MenuItem;
    currentUrl: string;
}

function SidebarMenuItem({ item, currentUrl }: SidebarMenuItemProps) {
    const hasChildren = !!item.children?.length;

    const childIsActive =
        item.children?.some(
            (child) => child.href && currentUrl.startsWith(child.href),
        ) ?? false;

    const isActive = item.href
        ? currentUrl === item.href || currentUrl.startsWith(`${item.href}/`)
        : childIsActive;

    const [isOpen, setIsOpen] = useState(childIsActive);

    const Icon = item.icon;

    if (!hasChildren && item.href) {
        return (
            <Link
                href={item.href}
                className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors ${
                    isActive
                        ? 'bg-blue-50 font-semibold text-blue-700'
                        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                }`}
            >
                {Icon && <Icon size={19} strokeWidth={1.8} />}
                <span>{item.label}</span>
            </Link>
        );
    }

    return (
        <div>
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className={`flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm transition-colors ${
                    isActive
                        ? 'font-semibold text-blue-700'
                        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                }`}
            >
                <span className="flex items-center gap-3">
                    {Icon && <Icon size={19} strokeWidth={1.8} />}
                    {item.label}
                </span>

                <ChevronDown
                    size={16}
                    className={`transition-transform duration-200 ${
                        isOpen ? 'rotate-180' : ''
                    }`}
                />
            </button>

            {isOpen && (
                <div className="mt-1 ml-6 space-y-1 border-l border-gray-200 pl-3">
                    {item.children?.map((child) => {
                        const active = child.href
                            ? currentUrl === child.href ||
                              currentUrl.startsWith(`${child.href}/`)
                            : false;

                        return (
                            <Link
                                key={child.href}
                                href={child.href ?? '#'}
                                className={`block rounded-md px-3 py-2 text-sm transition-colors ${
                                    active
                                        ? 'font-medium text-blue-700'
                                        : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900'
                                }`}
                            >
                                {child.label}
                            </Link>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

export default function AppSidebar() {
    const { url } = usePage();

    return (
        <aside className="flex h-screen w-64 shrink-0 flex-col border-r border-gray-200 bg-white">
            {/* Logo */}
            <div className="flex h-16 items-center gap-3 border-b border-gray-100 px-6">
                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-white">
                    <Boxes size={21} />
                </div>

                <span className="text-lg font-bold tracking-tight text-gray-900">
                    ERPsi
                </span>
            </div>

            {/* Navigation */}
            <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-5">
                <p className="mb-3 px-3 text-xs font-semibold tracking-wider text-gray-400 uppercase">
                    Menú principal
                </p>

                {menuItems.map((item) => (
                    <SidebarMenuItem
                        key={item.label}
                        item={item}
                        currentUrl={url}
                    />
                ))}
            </nav>

            {/* Footer */}
            <div className="border-t border-gray-100 p-4">
                <div className="flex items-center gap-3 rounded-lg p-2">
                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-600">
                        <Users size={18} />
                    </div>

                    <div>
                        <p className="text-xs font-medium text-gray-700">
                            Sistema ERP
                        </p>
                        <p className="text-xs text-gray-400">Versión 1.0.0</p>
                    </div>
                </div>
            </div>
        </aside>
    );
}
