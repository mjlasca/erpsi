import { Plus, Trash2 } from 'lucide-react';
import {
    Button,
    Card,
    CardHeader,
    Input,
    Select,
} from '@/Components/Ui/Primitives';
import { formatMoney, formatNumber } from '@/lib/format';
import type { Product, TaxRate } from '@/types/models';

export type DetailRow = {
    id?: number;
    product_id: string;
    product_variant_id: string;
    quantity: string;
    unit_price: string;
    discount: string;
    tax_rate_id: string;
};

export function emptyDetail(taxRates: TaxRate[]): DetailRow {
    return {
        product_id: '',
        product_variant_id: '',
        quantity: '1',
        unit_price: '0',
        discount: '0',
        tax_rate_id: taxRates[0] ? String(taxRates[0].id) : '',
    };
}

export function detailTotals(
    row: DetailRow,
    taxRates: TaxRate[],
): { subtotal: number; tax: number; total: number } {
    const quantity = Number(row.quantity || 0);
    const unitPrice = Number(row.unit_price || 0);
    const discount = Number(row.discount || 0);
    const subtotal = Math.max(quantity * unitPrice - discount, 0);
    const rate = Number(
        taxRates.find((taxRate) => String(taxRate.id) === row.tax_rate_id)
            ?.rate ?? 0,
    );
    const tax = subtotal * (rate / 100);

    return { subtotal, tax, total: subtotal + tax };
}

export default function LineItemsEditor({
    title = 'Líneas',
    details,
    onChange,
    products,
    taxRates,
    priceField,
    errors = {},
}: {
    title?: string;
    details: DetailRow[];
    onChange: (details: DetailRow[]) => void;
    products: Product[];
    taxRates: TaxRate[];
    priceField: 'cost_price' | 'sale_price';
    errors?: Record<string, string>;
}) {
    const updateRow = (index: number, changes: Partial<DetailRow>) => {
        onChange(
            details.map((row, current) =>
                current === index ? { ...row, ...changes } : row,
            ),
        );
    };

    const changeProduct = (index: number, productId: string) => {
        const product = products.find((item) => String(item.id) === productId);

        updateRow(index, {
            product_id: productId,
            product_variant_id: '',
            unit_price: product?.[priceField] ?? '0',
        });
    };

    const totals = details.reduce(
        (carry, row) => {
            const line = detailTotals(row, taxRates);

            return {
                subtotal:
                    carry.subtotal +
                    Number(row.quantity || 0) * Number(row.unit_price || 0),
                discount: carry.discount + Number(row.discount || 0),
                tax: carry.tax + line.tax,
                total: carry.total + line.total,
            };
        },
        { subtotal: 0, discount: 0, tax: 0, total: 0 },
    );

    return (
        <Card>
            <CardHeader
                title={title}
                description="Agrega al menos una línea al documento."
                action={
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() =>
                            onChange([...details, emptyDetail(taxRates)])
                        }
                    >
                        <Plus className="h-4 w-4" />
                        Agregar línea
                    </Button>
                }
            />

            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-100 text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                Producto
                            </th>
                            <th className="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                Variante
                            </th>
                            <th className="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                Cantidad
                            </th>
                            <th className="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                Precio
                            </th>
                            <th className="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                Descuento
                            </th>
                            <th className="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                Impuesto
                            </th>
                            <th className="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">
                                Total
                            </th>
                            <th className="px-3 py-2" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {details.length === 0 && (
                            <tr>
                                <td
                                    colSpan={8}
                                    className="px-4 py-8 text-center text-gray-500"
                                >
                                    Agrega la primera línea del documento.
                                </td>
                            </tr>
                        )}

                        {details.map((row, index) => {
                            const product = products.find(
                                (item) => String(item.id) === row.product_id,
                            );
                            const variants = product?.variants ?? [];
                            const line = detailTotals(row, taxRates);
                            const rowError = (field: string) =>
                                errors[`details.${index}.${field}`] ?? '';

                            return (
                                <tr key={row.id ?? index} className="align-top">
                                    <td className="min-w-48 px-3 py-2">
                                        <Select
                                            value={row.product_id}
                                            onChange={(event) =>
                                                changeProduct(
                                                    index,
                                                    event.target.value,
                                                )
                                            }
                                        >
                                            <option value="">
                                                Selecciona producto
                                            </option>
                                            {products.map((item) => (
                                                <option
                                                    key={item.id}
                                                    value={item.id}
                                                >
                                                    {item.name}
                                                </option>
                                            ))}
                                        </Select>
                                        {rowError('product_id') && (
                                            <p className="mt-1 text-xs text-red-600">
                                                {rowError('product_id')}
                                            </p>
                                        )}
                                    </td>
                                    <td className="min-w-40 px-3 py-2">
                                        <Select
                                            value={row.product_variant_id}
                                            disabled={variants.length === 0}
                                            onChange={(event) =>
                                                updateRow(index, {
                                                    product_variant_id:
                                                        event.target.value,
                                                })
                                            }
                                        >
                                            <option value="">
                                                Sin variante
                                            </option>
                                            {variants.map((variant) => (
                                                <option
                                                    key={variant.id}
                                                    value={variant.id}
                                                >
                                                    {variant.name}
                                                </option>
                                            ))}
                                        </Select>
                                    </td>
                                    <td className="w-28 px-3 py-2">
                                        <Input
                                            type="number"
                                            step="0.001"
                                            min="0.001"
                                            value={row.quantity}
                                            onChange={(event) =>
                                                updateRow(index, {
                                                    quantity:
                                                        event.target.value,
                                                })
                                            }
                                        />
                                    </td>
                                    <td className="w-28 px-3 py-2">
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={row.unit_price}
                                            onChange={(event) =>
                                                updateRow(index, {
                                                    unit_price:
                                                        event.target.value,
                                                })
                                            }
                                        />
                                    </td>
                                    <td className="w-28 px-3 py-2">
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={row.discount}
                                            onChange={(event) =>
                                                updateRow(index, {
                                                    discount:
                                                        event.target.value,
                                                })
                                            }
                                        />
                                    </td>
                                    <td className="w-40 px-3 py-2">
                                        <Select
                                            value={row.tax_rate_id}
                                            onChange={(event) =>
                                                updateRow(index, {
                                                    tax_rate_id:
                                                        event.target.value,
                                                })
                                            }
                                        >
                                            <option value="">
                                                Sin impuesto
                                            </option>
                                            {taxRates.map((taxRate) => (
                                                <option
                                                    key={taxRate.id}
                                                    value={taxRate.id}
                                                >
                                                    {taxRate.name} (
                                                    {formatNumber(
                                                        taxRate.rate,
                                                        2,
                                                    )}
                                                    %)
                                                </option>
                                            ))}
                                        </Select>
                                    </td>
                                    <td className="px-3 py-2 text-right font-medium whitespace-nowrap text-gray-900">
                                        {formatMoney(line.total)}
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                onChange(
                                                    details.filter(
                                                        (_, current) =>
                                                            current !== index,
                                                    ),
                                                )
                                            }
                                            className="rounded-lg p-2 text-red-600 hover:bg-red-50"
                                            aria-label="Eliminar línea"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            {errors.details && (
                <p className="px-4 pt-3 text-sm text-red-600">
                    {errors.details}
                </p>
            )}

            <div className="grid grid-cols-2 gap-3 border-t border-gray-100 px-5 py-4 text-sm sm:grid-cols-4">
                <div>
                    <p className="text-xs text-gray-500 uppercase">Subtotal</p>
                    <p className="mt-1 font-semibold text-gray-900">
                        {formatMoney(totals.subtotal)}
                    </p>
                </div>
                <div>
                    <p className="text-xs text-gray-500 uppercase">Descuento</p>
                    <p className="mt-1 font-semibold text-gray-900">
                        {formatMoney(totals.discount)}
                    </p>
                </div>
                <div>
                    <p className="text-xs text-gray-500 uppercase">Impuestos</p>
                    <p className="mt-1 font-semibold text-gray-900">
                        {formatMoney(totals.tax)}
                    </p>
                </div>
                <div>
                    <p className="text-xs text-gray-500 uppercase">Total</p>
                    <p className="mt-1 text-lg font-bold text-gray-900">
                        {formatMoney(totals.total)}
                    </p>
                </div>
            </div>
        </Card>
    );
}
