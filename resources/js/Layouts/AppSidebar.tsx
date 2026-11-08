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
    ChevronLeft,
    ChevronRight,
    Boxes,
    CreditCard,
    Wallet,
    type LucideIcon,
} from 'lucide-react';
import { dashboard } from '@/routes';
import { useCan } from '@/lib/permissions';
import { cn } from '@/lib/utils';
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
import { index as cashIndex } from '@/routes/cash/reconciliations';
import { index as rolesIndex } from '@/routes/admin/roles';
import { index as usersIndex } from '@/routes/admin/users';
import { index as taxRatesIndex } from '@/routes/admin/tax-rates';

interface MenuItem {
    label: string;
    href?: string;
    icon?: LucideIcon;
    permission?: string;
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
            {
                label: 'Productos',
                href: productsIndex.url(),
                permission: 'catalog.products.view',
            },
            {
                label: 'Categorías',
                href: categoriesIndex.url(),
                permission: 'catalog.categories.view',
            },
            {
                label: 'Unidades',
                href: unitsIndex.url(),
                permission: 'catalog.units.view',
            },
            {
                label: 'Variantes',
                href: variantsIndex.url(),
                permission: 'catalog.variants.view',
            },
            {
                label: 'Atributos',
                href: attributesIndex.url(),
                permission: 'catalog.attributes.view',
            },
        ],
    },
    {
        label: 'Inventario',
        icon: Boxes,
        children: [
            {
                label: 'Existencias',
                href: stockIndex.url(),
                permission: 'inventory.stock.view',
            },
            {
                label: 'Movimientos',
                href: movementsIndex.url(),
                permission: 'inventory.movements.view',
            },
            {
                label: 'Lotes',
                href: lotsIndex.url(),
                permission: 'inventory.lots.view',
            },
            {
                label: 'Transferencias',
                href: transferCreate.url(),
                permission: 'inventory.movements.create',
            },
            {
                label: 'Almacenes',
                href: warehousesIndex.url(),
                permission: 'inventory.warehouses.view',
            },
        ],
    },
    {
        label: 'Compras',
        icon: ShoppingCart,
        children: [
            {
                label: 'Compras',
                href: purchasesIndex.url(),
                permission: 'purchasing.purchases.view',
            },
            {
                label: 'Proveedores',
                href: suppliersIndex.url(),
                permission: 'purchasing.suppliers.view',
            },
        ],
    },
    {
        label: 'Ventas',
        icon: Receipt,
        children: [
            {
                label: 'Ventas',
                href: salesIndex.url(),
                permission: 'sales.sales.view',
            },
            {
                label: 'Clientes',
                href: customersIndex.url(),
                permission: 'sales.customers.view',
            },
        ],
    },
    {
        label: 'Pagos',
        href: paymentsIndex.url(),
        icon: CreditCard,
        permission: 'payments.payments.view',
    },
    {
        label: 'Arqueo',
        href: cashIndex.url(),
        icon: Wallet,
        permission: 'cash.reconciliations.view',
    },
    {
        label: 'Administración',
        icon: Settings,
        children: [
            {
                label: 'Usuarios',
                href: usersIndex.url(),
                permission: 'administration.users.view',
            },
            {
                label: 'Roles',
                href: rolesIndex.url(),
                permission: 'administration.roles.view',
            },
            {
                label: 'Impuestos',
                href: taxRatesIndex.url(),
                permission: 'administration.tax_rates.view',
            },
        ],
    },
];

function filterMenu(
    items: MenuItem[],
    can: (permission: string) => boolean,
): MenuItem[] {
    return items.flatMap((item) => {
        const children = item.children
            ? filterMenu(item.children, can)
            : undefined;

        if (item.permission && !can(item.permission)) {
            return [];
        }

        if (item.children && !children?.length) {
            return [];
        }

        return [{ ...item, children }];
    });
}

interface SidebarMenuItemProps {
    item: MenuItem;
    currentUrl: string;
    collapsed: boolean;
    onNavigate: () => void;
}

function SidebarMenuItem({
    item,
    currentUrl,
    collapsed,
    onNavigate,
}: SidebarMenuItemProps) {
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

    if (collapsed) {
        if (!hasChildren && item.href) {
            return (
                <Link
                    href={item.href}
                    onClick={onNavigate}
                    title={item.label}
                    aria-label={item.label}
                    className={cn(
                        'flex justify-center rounded-lg p-2.5 transition-colors',
                        isActive
                            ? 'bg-blue-50 text-blue-700'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                    )}
                >
                    {Icon && <Icon size={20} strokeWidth={1.8} />}
                </Link>
            );
        }

        return (
            <div className="group relative flex justify-center">
                <button
                    type="button"
                    onClick={() => setIsOpen(!isOpen)}
                    title={item.label}
                    aria-label={item.label}
                    className={cn(
                        'rounded-lg p-2.5 transition-colors',
                        isActive
                            ? 'bg-blue-50 text-blue-700'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                    )}
                >
                    {Icon && <Icon size={20} strokeWidth={1.8} />}
                </button>

                {isOpen && (
                    <div className="absolute top-0 left-full z-50 ml-2 w-56 rounded-lg border border-gray-200 bg-white py-2 shadow-lg">
                        <p className="px-4 pb-2 text-xs font-semibold tracking-wide text-gray-400 uppercase">
                            {item.label}
                        </p>
                        <div className="space-y-1 px-2">
                            {item.children?.map((child) => {
                                const active = child.href
                                    ? currentUrl === child.href ||
                                      currentUrl.startsWith(`${child.href}/`)
                                    : false;

                                return (
                                    <Link
                                        key={child.href}
                                        href={child.href ?? '#'}
                                        onClick={() => {
                                            setIsOpen(false);
                                            onNavigate();
                                        }}
                                        className={cn(
                                            'block rounded-md px-3 py-2 text-sm transition-colors',
                                            active
                                                ? 'font-medium text-blue-700'
                                                : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900',
                                        )}
                                    >
                                        {child.label}
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                )}
            </div>
        );
    }

    if (!hasChildren && item.href) {
        return (
            <Link
                href={item.href}
                onClick={onNavigate}
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
                                onClick={onNavigate}
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

export default function AppSidebar({
    mobileOpen,
    collapsed,
    onClose,
    onToggleCollapsed,
}: {
    mobileOpen: boolean;
    collapsed: boolean;
    onClose: () => void;
    onToggleCollapsed: () => void;
}) {
    const { url } = usePage();
    const can = useCan();
    const visibleItems = filterMenu(menuItems, can);
    const isCollapsed = collapsed && !mobileOpen;

    return (
        <>
            {mobileOpen && (
                <button
                    type="button"
                    aria-label="Cerrar menú"
                    className="fixed inset-0 z-30 bg-gray-900/30 lg:hidden"
                    onClick={onClose}
                />
            )}

            <aside
                className={cn(
                    'fixed inset-y-0 left-0 z-40 flex h-screen w-64 shrink-0 flex-col border-r border-gray-200 bg-white transition-all duration-200 lg:static lg:translate-x-0',
                    isCollapsed ? 'lg:w-20' : 'lg:w-64',
                    mobileOpen ? 'translate-x-0' : '-translate-x-full',
                )}
            >
                {/* Logo */}
                <div
                    className={cn(
                        'flex h-16 items-center border-b border-gray-100',
                        isCollapsed ? 'justify-between px-2' : 'gap-3 px-6',
                    )}
                >
                    <div className="flex items-center gap-3">
                        <div
                            className={cn(
                                'flex items-center justify-center rounded-lg bg-blue-600 text-white',
                                isCollapsed ? 'h-8 w-8' : 'h-9 w-9',
                            )}
                        >
                            <Boxes size={isCollapsed ? 18 : 21} />
                        </div>

                        <span
                            className={cn(
                                'text-lg font-bold tracking-tight text-gray-900',
                                isCollapsed && 'lg:hidden',
                            )}
                        >
                            ERPsi
                        </span>
                    </div>

                    <button
                        type="button"
                        onClick={onToggleCollapsed}
                        aria-label={
                            isCollapsed ? 'Expandir menú' : 'Contraer menú'
                        }
                        title={isCollapsed ? 'Expandir menú' : 'Contraer menú'}
                        className="hidden rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900 lg:flex"
                    >
                        {isCollapsed ? (
                            <ChevronRight className="h-4 w-4" />
                        ) : (
                            <ChevronLeft className="h-4 w-4" />
                        )}
                    </button>
                </div>

                {/* Navigation */}
                <nav
                    className={cn(
                        'flex-1 space-y-1 py-5',
                        isCollapsed
                            ? 'overflow-visible px-2'
                            : 'overflow-y-auto px-3',
                    )}
                >
                    <p
                        className={cn(
                            'mb-3 px-3 text-xs font-semibold tracking-wider text-gray-400 uppercase',
                            isCollapsed && 'lg:hidden',
                        )}
                    >
                        Menú principal
                    </p>

                    {visibleItems.map((item) => (
                        <SidebarMenuItem
                            key={item.label}
                            item={item}
                            currentUrl={url}
                            collapsed={isCollapsed}
                            onNavigate={onClose}
                        />
                    ))}
                </nav>

                {/* Footer */}
                <div
                    className={cn(
                        'border-t border-gray-100',
                        isCollapsed ? 'p-2' : 'p-4',
                    )}
                >
                    <div
                        className={cn(
                            'flex items-center gap-3 rounded-lg p-2',
                            isCollapsed && 'justify-center',
                        )}
                    >
                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-600">
                            <Users size={18} />
                        </div>

                        <div className={cn(isCollapsed && 'lg:hidden')}>
                            <p className="text-xs font-medium text-gray-700">
                                Sistema ERP
                            </p>
                            <p className="text-xs text-gray-400">
                                Versión 1.0.0
                            </p>
                        </div>
                    </div>
                </div>
            </aside>
        </>
    );
}
