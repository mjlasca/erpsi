export default function AuthLayout({ children }) {
    return (
        <main className="flex min-h-screen items-center justify-center bg-gray-100 p-4">
            <div className="w-full max-w-md rounded-xl bg-white p-8 shadow-lg">
                <div className="mb-8 text-center">
                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-xl font-bold text-white">
                        E
                    </div>
                    <h1 className="text-2xl font-bold text-gray-900">ERPsi</h1>
                    <p className="text-gray-500">
                        Sistema de gestión empresarial
                    </p>
                </div>

                {children}
            </div>
        </main>
    );
}
