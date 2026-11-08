# ERPsi

ERP modular para gestión comercial, inventario, compras, ventas, pagos y arqueo de caja.

## Tecnologías

- **Laravel 13** + PHP 8.5
- **Inertia.js 3** + React 19 + TypeScript
- **Tailwind CSS 4**
- **Wayfinder** para rutas tipadas entre Laravel y React
- **Pest 5** y **Playwright** para pruebas

## Estructura

Monolito modular organizado por dominio:

```text
app/
├── Modules/
│   ├── Administration/  Usuarios, roles, permisos e impuestos
│   ├── Catalog/         Productos, categorías, unidades, variantes y atributos
│   ├── Inventory/       Almacenes, existencias, movimientos, lotes y transferencias
│   ├── Purchasing/      Proveedores y compras
│   ├── Sales/           Clientes y ventas
│   ├── Payments/        Cobros, pagos y aplicaciones
│   └── Cash/            Arqueo diario y flujo de caja
└── Http/                Autenticación y dashboard

resources/js/
├── Pages/               Pantallas Inertia/React
├── Components/          Componentes reutilizables
├── Layouts/             Layout del ERP y autenticación
└── routes/              Rutas generadas por Wayfinder
```

## Funcionalidad

- Autenticación con roles y permisos.
- Catálogo maestro de productos y servicios.
- Control de inventario por almacén, lote y trazabilidad.
- Compras y ventas con edición en borrador.
- Pago de ventas y compras con uno o varios métodos de pago.
- Trazabilidad de pagos y saldos por documento.
- Arqueo diario de caja: movimientos, efectivo esperado, contado y diferencias.
- Dashboard con indicadores operativos.
- Interfaz completamente en español.

## Pruebas

```bash
php artisan test
npm run test:e2e
```
