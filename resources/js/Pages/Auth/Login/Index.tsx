import AuthLayout from '@/Layouts/AuthLayout';
import LoginForm from '@/Components/Auth/LoginForm';

export default function Login({ status }: { status?: string | null }) {
    return (
        <AuthLayout>
            <div className="mx-auto w-full max-w-md">
                {status && (
                    <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {status}
                    </div>
                )}

                <LoginForm />
            </div>
        </AuthLayout>
    );
}
