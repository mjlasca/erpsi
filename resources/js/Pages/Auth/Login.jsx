import { useState } from 'react';
import AuthLayout from '@/Layouts/AuthLayout';
import LoginForm from '@/Components/Auth/LoginForm';
import RegisterForm from '@/Components/Auth/RegisterForm';

export default function Login() {
    const [mode, setMode] = useState('login');

    return (
        <AuthLayout>
            <div className="w-full max-w-md mx-auto">

                <div className="flex mb-6 border-b">
                    <button
                        onClick={() => setMode('login')}
                        className={`w-1/2 py-3 ${
                            mode === 'login'
                                ? 'border-b-2 border-blue-600 font-bold'
                                : ''
                        }`}
                    >
                        Iniciar sesión
                    </button>

                    <button
                        onClick={() => setMode('register')}
                        className={`w-1/2 py-3 ${
                            mode === 'register'
                                ? 'border-b-2 border-blue-600 font-bold'
                                : ''
                        }`}
                    >
                        Registrarse
                    </button>
                </div>

                {mode === 'login' ? (
                    <LoginForm />
                ) : (
                    <RegisterForm />
                )}

            </div>
        </AuthLayout>
    );
}