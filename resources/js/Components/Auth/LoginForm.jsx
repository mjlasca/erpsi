import { useForm } from '@inertiajs/react';
import { store } from '@/routes/login';

export default function LoginForm() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(store.url());
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <div>
                <label>Correo electrónico</label>
                <input
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    className="w-full rounded border p-2"
                />
                {errors.email && <p className="text-red-500">{errors.email}</p>}
            </div>

            <div>
                <label>Contraseña</label>
                <input
                    type="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    className="w-full rounded border p-2"
                />
                {errors.password && (
                    <p className="text-red-500">{errors.password}</p>
                )}
            </div>

            <button
                type="submit"
                disabled={processing}
                className="w-full rounded bg-blue-600 p-3 text-white"
            >
                {processing ? 'Ingresando...' : 'Iniciar sesión'}
            </button>
        </form>
    );
}
