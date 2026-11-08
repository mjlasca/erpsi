export default function AuthLayout({ children }) {
    return (
        <main className="min-h-screen flex items-center justify-center bg-gray-100 p-4">

            <div className="bg-white rounded-xl shadow-lg p-8 w-full max-w-md">

                <div className="text-center mb-8">
                    <h1 className="text-2xl font-bold">
                        Cabinets United Pro
                    </h1>

                    <p className="text-gray-500">
                        Sistema de gestión empresarial
                    </p>
                </div>

                {children}

            </div>

        </main>
    );
}