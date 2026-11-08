import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';

const database = path.resolve(process.cwd(), 'database/e2e.sqlite');

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    timeout: 60_000,
    expect: {
        timeout: 10_000,
    },
    reporter: [['list']],
    globalSetup: './tests/e2e/global-setup.ts',
    use: {
        baseURL: 'http://127.0.0.1:8001',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8001 --no-reload',
        url: 'http://127.0.0.1:8001/login',
        reuseExistingServer: false,
        timeout: 120_000,
        env: {
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: database,
            SESSION_DRIVER: 'file',
            CACHE_STORE: 'array',
            QUEUE_CONNECTION: 'sync',
        },
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
