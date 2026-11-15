# Plan de implementación: ERPsi como SaaS

## Objetivo

Convertir ERPsi en un SaaS multiempresa, conservando su arquitectura de monolito modular (Laravel + Inertia/React). El alcance incluye aislamiento de datos por empresa; configuraciones de plataforma, empresa y usuario, con idioma inglés/español y zona horaria; devoluciones de ventas y compras; bajas por daño; sesiones de arqueo con fotografías de existencias al abrir y cerrar; gráficos en el dashboard y descarga de CSV.

## Decisiones recomendadas

- Empezar con una base de datos compartida y `tenant_id` obligatorio en los datos empresariales. Mantener la opción de bases separadas como decisión futura, no como requisito inicial.
- Permitir que un usuario pertenezca a más de una empresa mediante membresías; resolver la empresa activa en el servidor y nunca confiar en un `tenant_id` enviado por el cliente.
- Separar configuración de plataforma, configuración de cada empresa y preferencias personales. Los valores de una empresa no deben cambiar la configuración global del proceso Laravel.
- Tratar catálogo, existencias, documentos, pagos, clientes, proveedores, almacenes, lotes y arqueos como datos de la empresa. Si se requiere un catálogo común de plataforma, modelarlo expresamente como tal.
- Mantener en transacciones los cambios de inventario, pagos, devoluciones y apertura/cierre de arqueos. Usar colas para exportaciones grandes y efectos secundarios que no necesiten completar la solicitud.
- Las devoluciones serán documentos independientes; no sustituirán la cancelación de documentos.

## Fases

### 0. Preparación y reglas de negocio

1. Respaldar la base actual y registrar el comportamiento esperado de ventas, compras, pagos, inventario y arqueos.
2. Definir membresías, roles por empresa, empresa activa, moneda/zona horaria, numeración de documentos y alcance de cada caja (almacén asociado).
3. Definir límites de devolución, política de reembolso/crédito y quién puede aprobar ajustes por daño.
4. Definir que la captura de existencias del arqueo incluya cantidades por producto, variante, almacén y, cuando aplique, lote; comparar cantidad disponible y reservada.
5. Definir jerarquía de idioma y zona horaria: preferencia de usuario, valor predeterminado de la empresa y valor predeterminado de plataforma; guardar timestamps en UTC y presentar fechas según la zona horaria correspondiente.

### 1. Núcleo multiempresa

1. Crear `Tenant` y membresías usuario-empresa (`tenant_user`), incluyendo estado, rol y permisos de cada membresía. Separar las cuentas administrativas de plataforma de los administradores de una empresa.
2. Añadir middleware/contexto de tenant, selector de empresa para usuarios con varias membresías y datos de empresa activos compartidos con Inertia.
3. Añadir `tenant_id` a las tablas empresariales. Revisar relaciones y validaciones para impedir enlazar documentos, pagos, productos o almacenes de tenants distintos.
4. Actualizar índices y restricciones únicas para incluir la empresa (por ejemplo, número de factura y códigos de productos únicos por tenant).
5. Migrar los registros existentes a un tenant inicial: primero columnas nullable y backfill; después validar y aplicar `NOT NULL` y claves/índices. Preparar el despliegue para que la aplicación antigua y la nueva no dejen registros huérfanos.
6. Aplicar aislamiento a consultas, route model binding, políticas, búsquedas, dashboard, notificaciones, archivos, tareas programadas y trabajos en cola. Denegar operaciones si falta contexto de tenant, excepto en procesos explícitos de plataforma.

### 2. Configuraciones globales, de empresa y de usuario

1. Separar tres ámbitos:
    - **Plataforma:** idiomas soportados, idioma predeterminado, zona horaria predeterminada para empresas nuevas y valores técnicos por entorno. Credenciales de correo, colas, almacenamiento y facturación se mantienen en variables de entorno/secret manager, no en opciones editables por clientes.
    - **Empresa (tenant):** valores operativos y fiscales que deben compartirse entre sus usuarios.
    - **Usuario:** preferencias personales de idioma y, opcionalmente, zona horaria de presentación; si no están configuradas, hereda los valores de empresa/plataforma.
2. Añadir una pantalla de configuración de empresa, accesible solo con permiso, para mantener nombre legal/comercial, identificador fiscal, dirección, contacto, logo, idioma predeterminado (`es`/`en`), zona horaria, moneda operativa, formatos de fecha/número, inicio de semana, almacén predeterminado y prefijos/secuencias de documentos.
3. Añadir perfil/preferencias para que cada usuario seleccione español o inglés y una zona horaria opcional. Añadir una pantalla restringida de administración de plataforma para cambiar solo valores globales y habilitar idiomas; no permitir a una empresa cambiar valores de otra.
4. Guardar los valores principales en columnas tipadas de `tenants`/`users`; usar una tabla de opciones solo para configuraciones extensibles. Validar códigos ISO de idioma/moneda y zonas IANA. Cachear configuración por tenant con invalidación al modificarla.
5. Implementar resolución por solicitud: idioma del usuario soportado → idioma de empresa → idioma de plataforma; zona horaria de empresa para límites de fechas y reglas de negocio → preferencia de usuario para presentación → zona horaria de plataforma como fallback. No usar estado mutable global de PHP para preferencias de cada tenant.
6. Mantener UTC en almacenamiento de timestamps. Convertir las fechas locales de negocio a UTC en los límites de consultas y presentar las fechas con el locale/zona horaria elegidos. Aplicar esto a arqueos, pagos, reportes, dashboard, notificaciones y jobs.
7. Completar las traducciones en español e inglés para páginas React, validaciones y errores Laravel, estados/enums, navegación, correos, notificaciones, gráficos y encabezados de CSV. Centralizar traducciones del frontend y ofrecer selector de idioma persistente.
8. Definir una sola moneda operativa por empresa en esta fase; el formato monetario depende del locale/moneda, pero habilitar varias monedas por transacción requeriría conversión, tipo de cambio y reglas contables como trabajo separado. Usar las tasas impositivas ya configuradas como datos de empresa, no duplicarlas en preferencias globales.
9. Probar fallback de idioma, cambio de idioma por usuario, tenants con zonas horarias distintas en el mismo proceso/worker, límites de día y cambios de horario estacional, permisos y persistencia de preferencias.

### 3. Sesiones de arqueo y fotografías de inventario

El modelo actual permite un arqueo por fecha y suma pagos del día; debe evolucionar a sesiones individuales por empresa, usuario y caja/almacén.

1. Evolucionar `CashReconciliation` a una sesión de caja —o introducir `CashSession`— con estado abierta/cerrada, usuario y hora de apertura, usuario y hora de cierre, efectivo inicial/contado, diferencia, notas y almacén asociado.
2. Permitir múltiples sesiones por día. Asociar los movimientos de efectivo a la sesión correcta; conservar la atribución de pagos no realizados en efectivo sin incluirlos en el efectivo esperado.
3. Crear cabecera y líneas de instantánea vinculadas a la sesión, con tipo `OPENING` o `CLOSING`, fecha de captura, tenant, almacén, producto, variante/lote y cantidades.
4. Al abrir, capturar las existencias actuales dentro del mismo flujo transaccional y dejar la sesión abierta solo cuando la captura termine. Al cerrar, crear una captura final y cerrar la sesión de forma atómica.
5. Mantener las capturas inmutables. En la vista del arqueo mostrar cantidad inicial, final y diferencia por artículo, incluyendo artículos que aparezcan solo en una de las capturas. Añadir filtros por almacén/producto y enlace a movimientos del periodo.
6. Añadir pruebas de concurrencia, doble apertura/cierre, datos vacíos, varios almacenes y aislamiento entre tenants. No generar estas capturas en segundo plano: deben corresponder al instante efectivo de apertura/cierre.

### 4. Devoluciones de ventas y compras

1. Añadir documentos y líneas de devolución en `Sales` y `Purchasing`, vinculados al documento y a sus líneas originales, con borrador, confirmación y anulación, fecha, motivo y usuario.
2. Validar que solo se devuelvan documentos confirmados/recibidos y que la cantidad acumulada no exceda lo aún no devuelto. Para productos con lotes, conservar el lote de origen cuando sea posible.
3. Devolución de venta: registrar el destino de cada línea: reintegro a stock vendible, cuarentena o descarte. Crear movimientos de inventario trazables al documento de devolución.
4. Devolución de compra: registrar la salida al proveedor y el lote/unidades correspondientes.
5. Definir el efecto financiero separado del movimiento de inventario: reembolso o crédito al cliente para ventas; crédito/reembolso del proveedor para compras. Recalcular saldos y reflejar el efectivo en la sesión de caja si hubo reembolso en efectivo.
6. Mantener las acciones actuales de cancelación para anulaciones completas permitidas por sus reglas; impedir cancelar documentos que ya tengan devoluciones incompatibles.
7. Ejecutar cambios de estado, saldos y stock en transacciones con bloqueo de filas e idempotencia para evitar aplicar una devolución dos veces.

### 5. Daños, vencimientos y bajas de inventario

1. Usar movimientos negativos de ajuste —nunca editar `quantity_on_hand` directamente— con motivo obligatorio (daño, vencimiento, pérdida o corrección), notas, usuario y referencia.
2. Para mercancía que se desecha, crear un ajuste de salida auditable. Para mercancía dañada pendiente de inspección, enviarla a cuarentena/ubicación no vendible para que no pueda confirmarse en una venta.
3. En productos por lote, actualizar la cantidad y el estado del lote de forma coherente. Agregar permisos específicos y aprobación opcional por umbral de cantidad/valor.
4. Mostrar los ajustes en el historial de movimientos y permitir exportarlos junto con el inventario.

### 6. Dashboard con gráficos

1. Añadir filtros por periodo y almacén, siempre dentro del tenant activo.
2. Mostrar como mínimo: ventas frente a compras por día/mes, cobros frente a pagos en efectivo y evolución de existencias/bajas o alertas de stock bajo.
3. Obtener agregados en el servidor, evitando enviar movimientos completos al navegador. No presentar utilidad o valoración de inventario hasta definir formalmente el método de costeo.
4. Usar componentes visuales accesibles y responsive. Como el proyecto no tiene una biblioteca de gráficos identificada, evaluar SVG propio o aprobar una dependencia antes de agregarla.
5. Probar límites de fechas, empresas sin datos, permisos y rendimiento de las consultas agregadas.

### 7. Descarga de archivos CSV

Agregar acciones autorizadas de exportación, respetando el tenant, filtros activos y permisos de lectura:

- **Ventas:** listado filtrado; ofrecer exportación detallada por líneas cuando sea necesario.
- **Compras:** listado filtrado y detalle por líneas/lotes.
- **Arqueos:** sesiones y resumen de efectivo; desde una sesión, CSV independiente con las diferencias de inventario.
- **Productos:** catálogo con estado, unidad, categoría, variantes y campos de inventario pertinentes.

Definir encabezados traducidos al idioma efectivo, formato de fechas/números consistente con locale/moneda, codificación UTF-8 (incluyendo compatibilidad con hojas de cálculo) y escape seguro de valores. Las exportaciones pequeñas pueden ser síncronas; las grandes deben ejecutarse en cola, guardarse temporalmente en almacenamiento privado, verificar autorización al descargarlas y expirar/eliminarse automáticamente. Incluir pruebas de filtros, contenido, permisos y ausencia de datos de otros tenants.

### 8. Colas y operación SaaS

1. El repositorio ya contiene una migración para la tabla `jobs`; verificar la configuración efectiva de colas y configurar conexión, worker, reintentos, timeout y monitoreo en cada entorno.
2. Encolar exportaciones grandes, invitaciones/notificaciones, reportes pesados y otras integraciones externas. Despachar efectos secundarios después del commit.
3. No encolar confirmaciones de venta/compra, ajustes, devoluciones, pagos ni snapshots de arqueo: su resultado debe ser inmediato y transaccional.
4. Añadir registro de errores/reintentos y proceso de limpieza para archivos exportados. Los jobs deben transportar tenant y usuario explícitamente y verificar autorización/alcance al ejecutarse.

### 9. Suscripciones y lanzamiento

1. Después de estabilizar el aislamiento, añadir planes, periodo de prueba, estado de suscripción, límites/entitlements y flujo de suspensión/reactivación.
2. Integrar proveedor de cobro de suscripciones como módulo independiente de los pagos de clientes y proveedores del ERP.
3. Definir onboarding, invitaciones, cambio de plan, cancelación, exportación de datos del tenant, retención y eliminación conforme a requisitos legales.
4. Desplegar primero con tenant inicial y un grupo piloto; monitorear errores de autorización, consultas sin tenant, trabajos fallidos y rendimiento antes de habilitar registro o incorporación masiva.

## Criterios de aceptación

- Ningún usuario puede consultar o modificar datos de otro tenant, incluso mediante URL, búsqueda, exportación o job.
- Los documentos, pagos y movimientos de inventario mantienen saldos y cantidades correctos ante doble envío o concurrencia.
- Cada sesión de arqueo conserva capturas inmutables al abrir y cerrar, y su vista calcula diferencias correctas.
- Devoluciones parciales respetan cantidades pendientes y generan el efecto financiero y de inventario definido.
- Los productos dañados no quedan disponibles para la venta; toda baja tiene motivo y trazabilidad.
- Los gráficos y CSV reflejan filtros, idioma, zona horaria y permisos; exportaciones grandes no bloquean la petición ni exponen archivos públicamente.
- Un usuario puede usar español o inglés sin alterar las preferencias de otros usuarios; fechas de negocio y reportes respetan la zona horaria de la empresa.
- Suite Pest cubre reglas, aislamiento y configuración; Playwright cubre selección de empresa, preferencias, arqueos, devoluciones, gráficos y descargas.
