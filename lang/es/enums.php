<?php

return [
    'return_status' => [
        'draft' => 'Borrador',
        'confirmed' => 'Confirmada',
        'cancelled' => 'Anulada',
    ],
    'return_disposition' => [
        'restock' => 'Reintegrar a inventario',
        'quarantine' => 'Enviar a cuarentena',
        'discard' => 'Descartar',
    ],
    'cash_session' => [
        'open' => 'Abierta',
        'closed' => 'Cerrada',
    ],
    'sale_status' => [
        'draft' => 'Borrador',
        'confirmed' => 'Confirmada',
        'partial' => 'Pago parcial',
        'paid' => 'Pagada',
        'cancelled' => 'Cancelada',
    ],
    'purchase_status' => [
        'draft' => 'Borrador',
        'received' => 'Recibida',
        'paid' => 'Pagada',
        'cancelled' => 'Cancelada',
    ],
    'movement_type' => [
        'purchase' => 'Compra',
        'sale' => 'Venta',
        'return' => 'Devolución',
        'adjustment' => 'Ajuste',
        'transfer' => 'Transferencia',
        'quarantine' => 'Cuarentena',
        'damage' => 'Daño o baja',
    ],
    'lot_status' => [
        'available' => 'Disponible',
        'reserved' => 'Reservado',
        'partial' => 'Parcial',
        'sold' => 'Vendido',
        'cancelled' => 'Anulado',
        'quarantine' => 'En cuarentena',
    ],
    'payment_type' => [
        'customer_payment' => 'Cobro a cliente',
        'supplier_payment' => 'Pago a proveedor',
        'customer_refund' => 'Reembolso a cliente',
        'supplier_refund' => 'Reembolso de proveedor',
    ],
    'payment_method' => [
        'cash' => 'Efectivo',
        'card' => 'Tarjeta',
        'bank_transfer' => 'Transferencia bancaria',
        'check' => 'Cheque',
    ],
    'product_type' => [
        'goods' => 'Producto',
        'service' => 'Servicio',
        'bundle' => 'Paquete',
    ],
    'unit_type' => [
        'quantity' => 'Cantidad',
        'area' => 'Área',
        'length' => 'Longitud',
        'weight' => 'Peso',
        'volume' => 'Volumen',
        'other' => 'Otro',
    ],
];
