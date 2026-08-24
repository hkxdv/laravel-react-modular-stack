import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/utils/cn';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState, type PropsWithChildren } from 'react';

interface ProfileNavItem {
  title: string;
  href: string;
  icon?: unknown;
  current?: boolean;
  permission?: unknown;
}

interface ProfileLayoutProps {
  children: React.ReactNode;
  contextualNavItems?: ProfileNavItem[];
}

// ponytail: hardcoded fallback — remove after backend wiring verified
const defaultNavItems: ProfileNavItem[] = [
  { title: 'Perfil', href: route('internal.staff.profile.edit') },
  { title: 'Contraseña', href: route('internal.staff.password.edit') },
  { title: 'Apariencia', href: route('internal.staff.appearance') },
  { title: 'Seguridad', href: route('internal.staff.security.edit') },
  { title: 'Notificaciones', href: route('internal.staff.notifications.edit') },
];

export default function ProfileLayout({
  children,
  contextualNavItems,
}: Readonly<PropsWithChildren<ProfileLayoutProps>>) {
  const [currentPath, setCurrentPath] = useState('');

  // Consume contextualNavItems from backend page props as fallback
  const pageProps = usePage().props;
  const navItems: ProfileNavItem[] =
    contextualNavItems ??
    (pageProps.contextualNavItems as ProfileNavItem[] | undefined) ??
    defaultNavItems;

  useEffect(() => {
    setCurrentPath(globalThis.window.location.pathname);
  }, []);

  return (
    <div className="px-4 py-6">
      <Heading title="Perfil" description="Gestiona tu perfil y la configuración de tu cuenta" />

      <div className="flex flex-col space-y-8 lg:flex-row lg:space-y-0 lg:space-x-12">
        <aside className="w-full max-w-xl lg:w-48">
          <nav className="flex flex-col space-y-1 space-x-0">
            {navItems.map((item) => (
              <Button
                key={item.href}
                size="default"
                variant="ghost"
                asChild
                className={cn('w-full justify-start', {
                  'bg-muted': currentPath === item.href,
                })}
              >
                <Link href={item.href}>{item.title}</Link>
              </Button>
            ))}
          </nav>
        </aside>

        <Separator className="my-6 md:hidden" />

        <div className="w-full flex-1 lg:max-w-none">
          <section className="w-full space-y-12">{children}</section>
        </div>
      </div>
    </div>
  );
}
