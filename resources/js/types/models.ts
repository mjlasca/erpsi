export type Unit = {
    id: number;
    name: string;
    abbreviation: string;
    unit_type?: string | null;
    unit_type_label?: string | null;
    allows_decimal: boolean;
    is_active: boolean;
};

export type ProductCategory = {
    id: number;
    parent_id?: number | null;
    name: string;
    description?: string | null;
    is_active: boolean;
    parent?: ProductCategory | null;
    children?: ProductCategory[];
    products_count?: number;
    children_count?: number;
};

export type ProductVariant = {
    id: number;
    product_id: number;
    sku: string;
    name: string;
    barcode?: string | null;
    cost_price?: string | null;
    sale_price?: string | null;
    is_active: boolean;
    product?: Product;
};

export type ProductAttribute = {
    id: number;
    product_id: number;
    product_variant_id?: number | null;
    unit_id?: number | null;
    name: string;
    value: string;
    sort_order?: number | null;
    unit?: Unit | null;
    product?: Product;
    variant?: ProductVariant | null;
};

export type Product = {
    id: number;
    sku: string;
    name: string;
    description?: string | null;
    category_id?: number | null;
    unit_id: number;
    product_type: string;
    product_type_label: string;
    brand?: string | null;
    barcode?: string | null;
    cost_price: string;
    sale_price: string;
    min_stock: string;
    is_inventory: boolean;
    track_lots: boolean;
    is_active: boolean;
    category?: ProductCategory | null;
    unit?: Unit | null;
    variants?: ProductVariant[];
    attributes?: ProductAttribute[];
    variants_count?: number;
};

export type Warehouse = {
    id: number;
    code: string;
    name: string;
    address?: string | null;
    description?: string | null;
    is_active: boolean;
    stock_items_count?: number;
    movements_count?: number;
};

export type InventoryStock = {
    id: number;
    product_id: number;
    product_variant_id?: number | null;
    warehouse_id: number;
    quantity_on_hand: string;
    quantity_reserved: string;
    quantity_minimum: string;
    quantity_available: string;
    is_low_stock: boolean;
    product?: Product;
    variant?: ProductVariant | null;
    warehouse?: Warehouse;
};

export type InventoryLot = {
    id: number;
    product_id: number;
    product_variant_id?: number | null;
    warehouse_id: number;
    purchase_detail_id?: number | null;
    parent_lot_id?: number | null;
    code: string;
    lot_number?: string | null;
    serial_number?: string | null;
    quantity_initial: string;
    quantity_available: string;
    unit_cost: string;
    status: string;
    status_label: string;
    received_at?: string | null;
    notes?: string | null;
    metadata?: Record<string, unknown> | null;
    product?: Product;
    variant?: ProductVariant | null;
    warehouse?: Warehouse;
};

export type InventoryMovement = {
    id: number;
    product_id: number;
    product_variant_id?: number | null;
    warehouse_id: number;
    inventory_lot_id?: number | null;
    movement_type: string;
    movement_type_label: string;
    quantity: string;
    unit_cost?: string | null;
    reference_type?: string | null;
    reference_id?: number | null;
    movement_date: string;
    notes?: string | null;
    created_by?: number | null;
    product?: Product;
    variant?: ProductVariant | null;
    warehouse?: Warehouse;
    lot?: InventoryLot | null;
    creator?: {
        id: number;
        name: string;
    };
};

export type Supplier = {
    id: number;
    name: string;
    company_name?: string | null;
    tax_id?: string | null;
    email?: string | null;
    phone?: string | null;
    address?: string | null;
    contact_person?: string | null;
    notes?: string | null;
    is_active: boolean;
    purchases_count?: number;
    payments_count?: number;
};

export type Customer = {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    company_name?: string | null;
    email?: string | null;
    phone?: string | null;
    billing_address?: string | null;
    shipping_address?: string | null;
    tax_id?: string | null;
    notes?: string | null;
    is_active: boolean;
    sales_count?: number;
    payments_count?: number;
};

export type TaxRate = {
    id: number;
    name: string;
    rate: string;
    tax_type?: string | null;
    jurisdiction?: string | null;
    is_active: boolean;
};

export type DocumentDetail = {
    id: number;
    product_id: number;
    product_variant_id?: number | null;
    quantity: string;
    unit_price: string;
    discount: string;
    tax_rate_id?: number | null;
    tax_rate: string;
    tax_amount: string;
    subtotal: string;
    total: string;
    product?: Product;
    variant?: ProductVariant | null;
    tax_rate_model?: TaxRate | null;
};

export type PaymentAllocation = {
    id: number;
    payment_id: number;
    sale_id?: number | null;
    purchase_id?: number | null;
    amount: string;
    sale?: Sale;
    purchase?: Purchase;
};

export type Purchase = {
    id: number;
    purchase_number: string;
    supplier_id: number;
    supplier_invoice?: string | null;
    warehouse_id: number;
    purchase_date: string;
    subtotal: string;
    discount: string;
    tax_amount: string;
    total: string;
    paid_amount: string;
    balance: string;
    status: string;
    status_label: string;
    notes?: string | null;
    created_by?: number | null;
    received_at?: string | null;
    cancelled_at?: string | null;
    supplier?: Supplier;
    warehouse?: Warehouse;
    creator?: {
        id: number;
        name: string;
    };
    details?: DocumentDetail[];
    allocations?: PaymentAllocation[];
    details_count?: number;
};

export type SaleDetail = DocumentDetail & {
    sale_id: number;
    lot_allocations?: Array<{
        id: number;
        sale_detail_id: number;
        inventory_lot_id: number;
        quantity: string;
        notes?: string | null;
        lot?: InventoryLot;
    }>;
};

export type Sale = {
    id: number;
    invoice_number: string;
    customer_id: number;
    warehouse_id: number;
    sale_date: string;
    due_date?: string | null;
    subtotal: string;
    discount: string;
    tax_amount: string;
    total: string;
    paid_amount: string;
    balance: string;
    status: string;
    status_label: string;
    notes?: string | null;
    created_by?: number | null;
    confirmed_at?: string | null;
    cancelled_at?: string | null;
    customer?: Customer;
    warehouse?: Warehouse;
    creator?: {
        id: number;
        name: string;
    };
    details?: SaleDetail[];
    allocations?: PaymentAllocation[];
    details_count?: number;
};

export type Payment = {
    id: number;
    payment_type: string;
    payment_type_label: string;
    customer_id?: number | null;
    supplier_id?: number | null;
    payment_date: string;
    amount: string;
    allocated_amount: string;
    unallocated_amount: string;
    payment_method: string;
    payment_method_label: string;
    reference?: string | null;
    notes?: string | null;
    created_by?: number | null;
    customer?: Customer | null;
    supplier?: Supplier | null;
    creator?: {
        id: number;
        name: string;
    };
    allocations?: PaymentAllocation[];
};

export type Role = {
    id: number;
    name: string;
    permissions: string[];
    permission_groups?: Record<string, Record<string, string>>;
    is_active: boolean;
    users_count?: number;
};

export type AdminUser = {
    id: number;
    name: string;
    email: string;
    role_id?: number | null;
    is_active: boolean;
    role?: Role | null;
    created_at?: string;
};

export type SelectOption = {
    value: string;
    label: string;
};
