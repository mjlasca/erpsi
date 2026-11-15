<?php

return [
    'return_status' => [
        'draft' => 'Draft',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
    ],
    'return_disposition' => [
        'restock' => 'Return to stock',
        'quarantine' => 'Send to quarantine',
        'discard' => 'Discard',
    ],
    'cash_session' => [
        'open' => 'Open',
        'closed' => 'Closed',
    ],
    'sale_status' => [
        'draft' => 'Draft',
        'confirmed' => 'Confirmed',
        'partial' => 'Partially paid',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    ],
    'purchase_status' => [
        'draft' => 'Draft',
        'received' => 'Received',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    ],
    'movement_type' => [
        'purchase' => 'Purchase',
        'sale' => 'Sale',
        'return' => 'Return',
        'adjustment' => 'Adjustment',
        'transfer' => 'Transfer',
        'quarantine' => 'Quarantine',
        'damage' => 'Damage / write-off',
    ],
    'lot_status' => [
        'available' => 'Available',
        'reserved' => 'Reserved',
        'partial' => 'Partial',
        'sold' => 'Sold',
        'cancelled' => 'Cancelled',
        'quarantine' => 'Quarantined',
    ],
    'payment_type' => [
        'customer_payment' => 'Customer payment',
        'supplier_payment' => 'Supplier payment',
        'customer_refund' => 'Customer refund',
        'supplier_refund' => 'Supplier refund',
    ],
    'payment_method' => [
        'cash' => 'Cash',
        'card' => 'Card',
        'bank_transfer' => 'Bank transfer',
        'check' => 'Check',
    ],
    'product_type' => [
        'goods' => 'Product',
        'service' => 'Service',
        'bundle' => 'Bundle',
    ],
    'unit_type' => [
        'quantity' => 'Quantity',
        'area' => 'Area',
        'length' => 'Length',
        'weight' => 'Weight',
        'volume' => 'Volume',
        'other' => 'Other',
    ],
];
