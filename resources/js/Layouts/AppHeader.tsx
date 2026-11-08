import UserDropdown from '@/Components/Ui/UserDropdown';

interface AppHeaderProps {
    name: string;
    avatarUrl?: string | null;
}

export default function AppHeader({ name, avatarUrl }: AppHeaderProps) {
    return (
        <header className="flex h-16 items-center justify-end border-b border-gray-200 bg-white px-6">
            <UserDropdown name={name} avatarUrl={avatarUrl} />
        </header>
    );
}
