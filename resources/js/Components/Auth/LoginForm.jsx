import { useForm } from '@inertiajs/react';

export default function LoginForm() {

    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post('/login');
    };

    return (
        <form onSubmit={submit} className="space-y-4">

            <div>
                <label>Correo electrónico</label>
                <input
                    type="email"
                    value={data.email}
                    onChange={e => setData('email', e.target.value)}
                    className="w-full border rounded p-2"
                />
                {errors.email && (
                    <p className="text-red-500">{errors.email}</p>
                )}
            </div>

            <div>
                <label>Contraseña</label>
                <input
                    type="password"
                    value={data.password}
                    onChange={e => setData('password', e.target.value)}
                    className="w-full border rounded p-2"
                />
                {errors.password && (
                    <p className="text-red-500">{errors.password}</p>
                )}
            </div>

            <button
                type="submit"
                disabled={processing}
                className="w-full bg-blue-600 text-white p-3 rounded"
            >
                {processing ? 'Ingresando...' : 'Iniciar sesión'}
            </button>

        </form>
    );
}