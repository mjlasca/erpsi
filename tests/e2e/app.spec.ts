import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';

const credentials = {
    admin: ['admin@erpsi.test', 'password'],
    sales: ['sales@erpsi.test', 'password'],
} as const;

async function login(
    page: Page,
    email: string = credentials.admin[0],
    password: string = credentials.admin[1],
): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Correo electrónico').fill(email);
    await page.getByLabel('Contraseña').fill(password);
    await page.getByRole('button', { name: 'Iniciar sesión' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
}

async function chooseRemote(
    page: Page,
    label: string,
    search: string,
    option: RegExp,
): Promise<void> {
    const input = page.getByLabel(label, { exact: true });
    await input.click();
    await input.fill(search);
    await page.getByRole('option', { name: option }).click();
}

test('inicia y cierra sesión con el usuario administrador', async ({
    page,
}) => {
    await login(page);

    await expect(
        page.getByRole('heading', { name: 'Panel de control' }),
    ).toBeVisible();

    await page.getByRole('button', { name: /Administrador/ }).click();
    await page.getByRole('button', { name: 'Cerrar sesión' }).click();

    await expect(page).toHaveURL(/\/login$/);
    await expect(
        page.getByRole('heading', { name: 'Iniciar sesión' }),
    ).toHaveCount(0);
    await expect(
        page.getByRole('button', { name: 'Iniciar sesión' }),
    ).toBeVisible();
});

test('oculta módulos y acciones sin permisos', async ({ page }) => {
    await login(page, ...credentials.sales);

    await expect(page.getByText('Administración', { exact: true })).toHaveCount(
        0,
    );
    await expect(
        page.getByRole('button', { name: 'Compras', exact: true }),
    ).toHaveCount(0);

    await page.goto('/catalog/products');

    await expect(
        page.getByRole('heading', { name: 'Productos' }),
    ).toBeVisible();
    await expect(
        page.getByRole('link', { name: 'Nuevo producto' }),
    ).toHaveCount(0);
    await expect(page.getByRole('link', { name: 'Editar' })).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Eliminar' })).toHaveCount(0);

    const response = await page.goto('/administration/users');
    expect(response?.status()).toBe(403);
});

test('abre y cierra el menú lateral en móvil', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await login(page);

    const sidebar = page.locator('aside');
    await expect(sidebar).toHaveClass(/-translate-x-full/);

    await page.getByRole('button', { name: 'Abrir menú' }).click();
    await expect(sidebar).toHaveClass(/translate-x-0/);

    await page.getByRole('button', { name: 'Catálogo' }).click();
    await expect(
        page
            .locator('aside')
            .getByRole('link', { name: 'Productos', exact: true }),
    ).toBeVisible();
});

test('activa la búsqueda remota a partir de 3 caracteres', async ({ page }) => {
    await login(page);
    await page.goto('/purchasing/purchases/create');

    const input = page.getByLabel('Proveedor', { exact: true });
    await input.click();
    await input.fill('Pr');

    await expect(
        page.getByText('Escribe al menos 3 caracteres para buscar.'),
    ).toBeVisible();
    await expect(
        page.getByRole('option', { name: /Proveedor E2E/ }),
    ).toHaveCount(0);

    await input.fill('Pro');
    await expect(
        page.getByRole('option', { name: /Proveedor E2E/ }),
    ).toBeVisible();
});

test('reduce el sidebar a solo iconos y permite expandirlo', async ({
    page,
}) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await login(page);

    const sidebar = page.locator('aside');
    await expect(sidebar).toHaveClass(/lg:w-64/);

    await page.getByRole('button', { name: 'Contraer menú' }).click();
    await expect(sidebar).toHaveClass(/lg:w-20/);
    await expect(page.getByText('Menú principal')).toBeHidden();
    await expect(
        sidebar.getByRole('link', { name: 'Dashboard', exact: true }),
    ).toBeVisible();

    await sidebar
        .getByRole('button', { name: 'Catálogo', exact: true })
        .click();
    await expect(
        sidebar.getByRole('link', { name: 'Productos', exact: true }),
    ).toBeVisible();

    await page.getByRole('button', { name: 'Expandir menú' }).click();
    await expect(sidebar).toHaveClass(/lg:w-64/);
    await expect(page.getByText('ERPsi', { exact: true })).toBeVisible();
});

test('crea una compra, la recibe y luego confirma una venta', async ({
    page,
}) => {
    await login(page);

    await page.goto('/purchasing/purchases/create');

    await chooseRemote(page, 'Proveedor', 'Proveedor E2E', /Proveedor E2E/);
    await page
        .getByLabel('Almacén de destino')
        .selectOption({ label: 'MAIN · Almacén principal' });
    await chooseRemote(page, 'Producto', 'Producto E2E', /Producto E2E/);
    await page.getByLabel('Cantidad').fill('3');
    await page.getByRole('button', { name: 'Crear compra' }).click();

    await expect(page).toHaveURL(/\/purchasing\/purchases\/\d+$/);
    await expect(page.getByText('Borrador', { exact: true })).toBeVisible();

    await page.getByRole('button', { name: 'Recibir', exact: true }).click();
    const receiveDialog = page.getByRole('dialog');
    await expect(
        receiveDialog.getByRole('heading', { name: 'Recibir compra' }),
    ).toBeVisible();
    await receiveDialog.getByRole('button', { name: 'Recibir' }).click();
    await expect(page.getByText('Recibida', { exact: true })).toBeVisible();

    await page.goto('/inventory/stock');
    const stockAfterPurchase = page.getByRole('row', { name: /Producto E2E/ });
    await expect(stockAfterPurchase).toBeVisible();
    await expect(stockAfterPurchase).toContainText('3');

    await page.goto('/sales/create');
    await chooseRemote(page, 'Cliente', 'Cliente E2E', /Cliente E2E/);
    await page
        .getByLabel('Almacén de salida')
        .selectOption({ label: 'MAIN · Almacén principal' });
    await chooseRemote(page, 'Producto', 'Producto E2E', /Producto E2E/);
    await page.getByLabel('Cantidad').fill('1');
    await page.getByRole('button', { name: 'Crear venta' }).click();

    await expect(page).toHaveURL(/\/sales\/\d+$/);
    await expect(page.getByText('Borrador', { exact: true })).toBeVisible();

    await page.getByRole('button', { name: 'Confirmar', exact: true }).click();
    const confirmDialog = page.getByRole('dialog');
    await expect(
        confirmDialog.getByRole('heading', { name: 'Confirmar venta' }),
    ).toBeVisible();
    await confirmDialog.getByRole('button', { name: 'Confirmar' }).click();
    await expect(page.getByText('Confirmada', { exact: true })).toBeVisible();

    await page.goto('/inventory/stock');
    const stockAfterSale = page.getByRole('row', { name: /Producto E2E/ });
    await expect(stockAfterSale).toBeVisible();
    await expect(stockAfterSale).toContainText('2');
});

test('pide confirmación antes de eliminar un registro', async ({ page }) => {
    await login(page);
    await page.goto('/purchasing/suppliers');

    const row = page.getByRole('row', { name: /Proveedor Borrar/ });
    await expect(row).toBeVisible();

    await row.getByRole('button', { name: 'Eliminar' }).click();
    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button', { name: 'Cancelar' }).click();
    await expect(dialog).toHaveCount(0);
    await expect(row).toBeVisible();

    await row.getByRole('button', { name: 'Eliminar' }).click();
    await page
        .getByRole('dialog')
        .getByRole('button', { name: 'Eliminar' })
        .click();

    await expect(
        page.getByRole('row', { name: /Proveedor Borrar/ }),
    ).toHaveCount(0);
});
