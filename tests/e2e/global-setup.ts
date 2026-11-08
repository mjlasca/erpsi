import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const database = path.resolve(root, 'database/e2e.sqlite');

export default function globalSetup(): void {
    fs.mkdirSync(path.dirname(database), { recursive: true });

    if (fs.existsSync(database)) {
        fs.unlinkSync(database);
    }

    fs.writeFileSync(database, '');

    const env = {
        ...process.env,
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: database,
        SESSION_DRIVER: 'file',
        CACHE_STORE: 'array',
        QUEUE_CONNECTION: 'sync',
    };

    execSync('php artisan config:clear', { cwd: root, env, stdio: 'inherit' });
    execSync('php artisan migrate:fresh --seed --no-interaction', {
        cwd: root,
        env,
        stdio: 'inherit',
    });
    execSync('php artisan db:seed --class=E2eSeeder --no-interaction', {
        cwd: root,
        env,
        stdio: 'inherit',
    });
    execSync('npm run build', { cwd: root, env, stdio: 'inherit' });
}
