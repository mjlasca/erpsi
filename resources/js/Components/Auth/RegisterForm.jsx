import { useForm } from '@inertiajs/react';

export default function RegisterForm() {

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post('/register');
    };

    return (
        <form onSubmit={submit} className="space-y-4">

            <div>
                <label>Nombre</label>
                <input
                    value={data.name}
                    onChange={e => setData('name', e.target.value)}
                    className="w-full border rounded p-2"
                />
                {errors.name && <p className="text-red-500">{errors.name}</p>}
            </div>

            <div>
                <label>Correo electrónico</label>
                <input
                    type="email"
                    value={data.email}
                    onChange={e => setData('email', e.target.value)}
                    className="w-full border rounded p-2"
                />
                {errors.email && <p className="text-red-500">{errors.email}</p>}
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

            <div>
                <label>Confirmar contraseña</label>
                <input
                    type="password"
                    value={data.password_confirmation}
                    onChange={e => setData('password_confirmation', e.target.value)}
                    className="w-full border rounded p-2"
                />
            </div>

            <button
                type="submit"
                disabled={processing}
                className="w-full bg-blue-600 text-white p-3 rounded"
            >
                {processing ? 'Registrando...' : 'Crear cuenta'}
            </button>

        </form>
    );
}