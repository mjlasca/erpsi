Plan actualizado — CRUD completo, autenticación y UI
Se mantiene el esquema y la arquitectura aprobados. Se agregan controladores, rutas, autorización, acciones de negocio y pantallas Inertia/React.

1. Ajustes adicionales al esquema
   Para soportar los CRUD y las reglas de negocio:

- inventory_movements.inventory_lot_id nullable, para trazar movimientos por lote.
- purchases.paid_amount y purchases.balance, para aplicar pagos a proveedores igual que en ventas.
- LotStatus añade Cancelled, para lotes anulados por reversión.
- Los totales de compras/ventas y sus líneas se recalculan siempre en el servidor.
- Las comparaciones monetarias y de cantidades usarán bcmath con escala fija.

2. Estructura por módulo
   app/Modules/Catalog/
   Controllers/ ProductController, ProductCategoryController, UnitController,
   ProductVariantController, ProductAttributeController
   Requests/ Store/Update por entidad y controlador anidado
   Resources/ ProductResource, ProductCategoryResource, UnitResource, ...
   Policies/ ProductPolicy, ProductCategoryPolicy, UnitPolicy, ...
   Models/ ya definidos
   Enums/ ya definidos

app/Modules/Inventory/
Controllers/ WarehouseController, InventoryStockController,
InventoryMovementController, InventoryLotController
Actions/ RecordInventoryMovement, TransferInventory
Requests/, Resources/, Policies/, Models/, Enums/

app/Modules/Purchasing/
Controllers/ SupplierController, PurchaseController, PurchaseDetailController,
ReceivePurchaseController, CancelPurchaseController
Actions/ ReceivePurchase, CancelPurchase, RecalculatePurchaseTotals
Requests/, Resources/, Policies/, Models/, Enums/

app/Modules/Sales/
Controllers/ CustomerController, SaleController, SaleDetailController,
ConfirmSaleController, CancelSaleController
Actions/ ConfirmSale, CancelSale, RecalculateSaleTotals
Requests/, Resources/, Policies/, Models/, Enums/

app/Modules/Payments/
Controllers/ PaymentController, PaymentAllocationController
Actions/ RecordPayment, AllocatePayment, RemovePaymentAllocation,
RecalculateDocumentBalance
Requests/, Resources/, Policies/, Models/, Enums/

app/Modules/Administration/
Controllers/ UserController, RoleController, TaxRateController
Support/ Permissions
Requests/, Resources/, Policies/, Models/, Enums/

app/Http/Controllers/Auth/LoginController.php
app/Http/Controllers/DashboardController.php
app/Http/Middleware/EnsureUserIsActive.php
app/Console/Commands/CreateAdminUser.php
Se añade AuthorizesRequests al controlador base para usar $this->authorize(). 3. Autenticación y autorización

- Login y logout con sesión, sin registro público. Los usuarios se crean desde Administración.
- LoginRequest con rate limiting, credenciales y verificación de is_active.
- Middleware active: si el usuario fue desactivado, se cierra la sesión.
- Comando php artisan erp:create-admin para crear el primer administrador de forma segura.
- Policies por modelo, registradas con #[UsePolicy(...)] en cada modelo.
- Permisos por recurso y acción: catalog.products.view, catalog.products.create, purchasing.purchases.receive, sales.sales.confirm, payments.allocations.manage, etc.
- Role::hasPermission() soporta * y comodines como catalog.*.
- Roles iniciales:
- Administrator: *.
- Sales: catálogo en lectura, clientes, ventas y cobros.
- Inventory Manager: catálogo en lectura, almacenes, stock, movimientos, lotes y compras.
- El usuario autenticado y sus permisos se comparten en Inertia.
- Cada controlador pasa un objeto can a la página para ocultar acciones no permitidas.

4. CRUD por entidad
   Catálogo
   Entidad Operaciones
   Productos CRUD completo. Borrado bloqueado si tiene stock, movimientos o documentos; se sugiere desactivar.
   Categorías CRUD completo. Borrado bloqueado si tiene productos o subcategorías.
   Unidades CRUD completo. Borrado bloqueado si está en uso.
   Variantes CRUD anidado bajo producto. Borrado bloqueado si tiene historial.
   Atributos CRUD anidado bajo producto o variante. Sin restricciones especiales.
   Inventario
   Entidad Operaciones
   Almacenes CRUD completo. Borrado bloqueado si tiene stock o documentos.
   Existencias Solo index y show. La escritura ocurre exclusivamente por movimientos.
   Movimientos index, show, create, store. Sin update ni destroy. Los tipos manuales permitidos son ajuste, devolución y transferencia.
   Lotes CRUD limitado. Se crean manualmente o al recibir una compra; se eliminan solo si no tienen movimientos ni asignaciones.
   Compras
   Entidad Operaciones
   Proveedores CRUD completo. Borrado bloqueado si tiene compras o pagos.
   Compras CRUD solo en borrador. receive confirma la recepción y genera inventario; cancel revierte si ya estaba recibida. Una compra recibida no se edita ni se borra.
   Detalles de compra CRUD anidado, solo si la compra está en borrador.
   Ventas
   Entidad Operaciones
   Clientes CRUD completo. Borrado bloqueado si tiene ventas o pagos.
   Ventas CRUD solo en borrador. confirm descuenta inventario y asigna lotes; cancel revierte si ya estaba confirmada. Una venta confirmada no se edita ni se borra.
   Detalles de venta CRUD anidado, solo si la venta está en borrador.
   Asignaciones de lotes Se generan al confirmar la venta y se muestran en solo lectura. No se editan ni eliminan manualmente.
   Pagos
   Entidad Operaciones
   Pagos index, show, create, store, edit, update, destroy. Solo se editan o eliminan mientras no tengan aplicaciones.
   Aplicaciones de pago store y destroy anidados bajo el pago. Recalculan saldo y estado del documento.
   Administración
   Entidad Operaciones
   Usuarios CRUD completo. No se puede eliminar el propio usuario ni desactivar al último administrador. Contraseñas hasheadas.
   Roles CRUD completo. No se elimina un rol con usuarios asignados; el rol Administrator está protegido.
   Impuestos CRUD completo. Borrado bloqueado si está referenciado; se sugiere desactivar.
5. Rutas
   Prefijos y nombres:
   / → redirige a dashboard o login
   /login, /logout
   /dashboard

/catalog/products
/catalog/products/{product}/variants
/catalog/products/{product}/attributes
/catalog/categories
/catalog/units

/inventory/warehouses
/inventory/stock
/inventory/movements
/inventory/lots

/purchasing/suppliers
/purchasing/purchases
/purchasing/purchases/{purchase}/details
POST /purchasing/purchases/{purchase}/receive
POST /purchasing/purchases/{purchase}/cancel

/sales/customers
/sales
/sales/{sale}/details
POST /sales/{sale}/confirm
POST /sales/{sale}/cancel

/payments
/payments/{payment}/allocations

/administration/users
/administration/roles
/administration/tax-rates

- Todo el ERP va dentro de auth + active.
- Login y logout usan guest/auth según corresponda.
- Se usarán bindings anidados con scopeBindings() para detalles e hijos.
- Wayfinder generará las funciones TypeScript; no se escribirán URLs a mano.

6. Interfaz Inertia + React
   Layouts:

- resources/js/layouts/app-layout.tsx: sidebar, topbar, menú de usuario, mensajes flash y navegación responsive.
- resources/js/layouts/auth-layout.tsx: login.
  Componentes reutilizables:
- Tabla, paginación, buscador, filtros, botones, inputs, selects, textareas, checkboxes, cards, badges, empty states, diálogo de confirmación y mensajes flash.
- DataTable con paginación server-side, filtros y conservación de estado.
- DeleteButton, StatusBadge, PageHeader, FormField, MoneyDisplay, QuantityDisplay.
- Formularios con useForm tipado; líneas dinámicas para compras y ventas.
- Selectores de productos, clientes y proveedores con búsqueda remota mediante useHttp y endpoints JSON internos.
- Sin instalar nuevas dependencias de UI; Tailwind 4 y los componentes existentes.
  Páginas principales:
  auth/login
  dashboard
  catalog/products/{index,create,edit,show}
  catalog/categories/{index,create,edit}
  catalog/units/{index,create,edit}
  inventory/warehouses/{index,create,edit}
  inventory/stock/{index,show}
  inventory/movements/{index,show,create}
  inventory/lots/{index,show,create,edit}
  purchasing/suppliers/{index,create,edit}
  purchasing/purchases/{index,create,edit,show}
  sales/customers/{index,create,edit}
  sales/sales/{index,create,edit,show}
  payments/payments/{index,create,show}
  administration/users/{index,create,edit}
  administration/roles/{index,create,edit}
  administration/tax-rates/{index,create,edit}
  La UI estará en español; nombres de código, tablas y columnas en inglés.

7. Acciones de negocio
   Se implementarán dentro de transacciones y usando bloqueo de filas:

- RecordInventoryMovement: crea el movimiento, crea la fila de stock si no existe, actualiza cantidades y valida stock no negativo.
- TransferInventory: genera movimiento de salida y entrada entre almacenes.
- ReceivePurchase: solo desde borrador; incrementa stock, crea movimientos, genera lotes para productos trazables y marca la compra como recibida.
- CancelPurchase: borrador pasa a cancelado sin tocar stock; compra recibida genera movimientos de reversión y anula lotes no consumidos.
- ConfirmSale: solo desde borrador; valida stock y lotes disponibles, descuenta inventario, crea movimientos y asignaciones de lotes, y marca la venta como confirmada.
- CancelSale: borrador pasa a cancelado sin tocar stock; venta confirmada genera reversión de movimientos y restaura lotes. Se bloquea si tiene pagos aplicados.
- RecalculatePurchaseTotals y RecalculateSaleTotals: recalculan subtotal, impuestos, total y saldo desde las líneas.
- RecordPayment: crea el pago sin afectar inventario.
- AllocatePayment: valida tipo de pago, saldo del pago y saldo del documento; crea la aplicación y actualiza paid_amount, balance y estado de la venta.
- RemovePaymentAllocation: elimina la aplicación y recalcula saldos.
  Reglas aplicadas en controladores, Form Requests, Policies y acciones:
- Borradores no modifican inventario.
- Documentos confirmados son inmutables.
- Movimientos confirmados no se editan ni eliminan.
- Pagos no modifican inventario.
- No se permite stock negativo.
- No se permite sobreaplicar pagos.
- Los totales enviados por el cliente se ignoran; el servidor recalcula.

8. Validación
   Un Form Request por operación de escritura, con:

- Rule::enum para estados y tipos.
- exists para claves foráneas.
- unique(...)->ignore(...) en actualizaciones.
- Reglas decimales y cantidades mínimas.
- Validación de arreglos anidados para líneas, variantes, atributos y lotes.
- Reglas de contraseña con Password::defaults().
- Validación de permisos contra el catálogo central de permisos.
- Mensajes de validación en español mediante archivos lang/es.

9. Pruebas

- Autenticación: login válido, credenciales inválidas, usuario inactivo, logout, acceso denegado a invitados y rate limiting.
- Autorización: matriz de permisos por rol y Policies de acciones personalizadas.
- CRUD: index, create, store, edit, update y destroy por entidad, incluyendo validación y restricciones de borrado.
- Compras: borrador no mueve stock; recepción incrementa stock y crea lotes; cancelación revierte; documento recibido no se edita.
- Ventas: borrador no mueve stock; confirmación descuenta y asigna lotes; stock insuficiente falla; cancelación revierte; documento confirmado no se edita.
- Inventario: movimientos inmutables, transferencias y ajustes actualizan stock correctamente.
- Pagos: no afectan inventario; aplicaciones actualizan saldos; no se permite sobreaplicar; eliminación recalcula.
- Inertia: páginas index/show renderizan con props y permisos correctos.
- Comando: creación del primer administrador.

10. Orden de implementación
1. Esquema, modelos, enums, factories y seeders.
1. Autenticación, permisos, middleware y layout base.
1. CRUD de catálogo y administración.
1. Almacenes, stock, movimientos y lotes.
1. Compras con recepción y cancelación.
1. Ventas con confirmación y cancelación.
1. Pagos y aplicaciones.
1. Dashboard, búsquedas remotas y pulido de UI.
1. Pruebas, formateo y análisis estático.
1. Verificación final
   php artisan migrate:fresh --seed
   php artisan test --compact
   vendor/bin/pint --dirty --format agent
   vendor/bin/phpstan analyse --memory-limit=1G
   php artisan wayfinder:generate --with-form --no-interaction
   npm run types:check
   npm run build
   Fuera de alcance por ahora

- Registro público, recuperación de contraseña y verificación de correo.
- API pública versionada.
- PDFs, reportes avanzados y gráficos.
- Multi-empresa, multi-moneda y costeo promedio ponderado.
- Reservas de stock para borradores.
