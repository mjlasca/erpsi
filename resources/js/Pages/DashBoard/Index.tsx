
import DashBoard from '@/Layouts/DashBoard';

interface Props {
    auth: {
        user: {
            name: string;
            avatar?: string | null;
        };
    };
}

export default function Index({ auth }: Props) {
    return (
        <DashBoard
            user={{
                name: auth.user.name,
                avatarUrl: auth.user.avatar,
            }}
        >
            <div>
                <h1 className="text-2xl font-bold text-gray-900">
                    Dashboard
                </h1>

                <p className="mt-1 text-sm text-gray-500">
                    Bienvenido a tu sistema de gestión.
                </p>
            </div>
        </DashBoard>
    );
}
