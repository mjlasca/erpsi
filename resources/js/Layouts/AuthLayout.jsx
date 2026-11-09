export default function AuthLayout({ children }) {
    return (
        <main className="flex min-h-screen items-center justify-center bg-gray-100 p-4">
            <div className="w-full max-w-md rounded-xl bg-white p-8 shadow-lg">
                <div className="mb-8 text-center">
                    <div className="mx-auto mb-4 flex max-w-[100px] items-center justify-center">
                        <img src="erpsimple.svg" alt="Logo ErpSi" />
                    </div>
                    <p className="text-gray-500">Sistema de gestión</p>
                </div>

                {children}
            </div>
        </main>
    );
}
