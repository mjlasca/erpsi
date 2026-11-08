import { useState, useRef, useEffect } from 'react';
import { Link, router } from '@inertiajs/react';
import { ChevronDown, UserRound, LogOut } from 'lucide-react';
import { dashboard, logout } from '@/routes';

interface UserDropdownProps {
    name: string;
    avatarUrl?: string | null;
}

export default function UserDropdown({ name, avatarUrl }: UserDropdownProps) {
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);

    const initials = name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();

    // Cerrar al hacer clic fuera del dropdown
    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (
                dropdownRef.current &&
                !dropdownRef.current.contains(event.target as Node)
            ) {
                setIsOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    const handleLogout = () => {
        router.post(logout.url());
        setIsOpen(false);
    };

    return (
        <div className="relative" ref={dropdownRef}>
            {/* Trigger */}
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                aria-expanded={isOpen}
                aria-haspopup="true"
                className="flex items-center gap-3 rounded-lg px-2 py-1.5 transition-colors hover:bg-gray-50"
            >
                {avatarUrl ? (
                    <img
                        src={avatarUrl}
                        alt={name}
                        className="h-9 w-9 rounded-full object-cover"
                    />
                ) : (
                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                        {initials}
                    </div>
                )}

                <span className="text-sm font-medium text-gray-700">
                    {name}
                </span>

                <ChevronDown
                    size={16}
                    className={`text-gray-400 transition-transform duration-200 ${
                        isOpen ? 'rotate-180' : ''
                    }`}
                />
            </button>

            {/* Dropdown menu */}
            {isOpen && (
                <div className="absolute top-full right-0 z-50 mt-2 w-52 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                    <Link
                        href={dashboard.url()}
                        onClick={() => setIsOpen(false)}
                        className="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        <UserRound size={17} className="text-gray-400" />
                        Ir al panel
                    </Link>

                    <div className="my-1 border-t border-gray-100" />

                    <button
                        type="button"
                        onClick={handleLogout}
                        className="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-red-600 transition-colors hover:bg-red-50"
                    >
                        <LogOut size={17} />
                        Cerrar sesión
                    </button>
                </div>
            )}
        </div>
    );
}
