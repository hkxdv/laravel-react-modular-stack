import { AppContent } from '@/components/app/app-content';
import { AppShell } from '@/components/app/app-shell';
import { AppSidebar } from '@/components/app/app-sidebar';
import { AppSidebarHeader } from '@/components/app/app-sidebar-header';
import { type BreadcrumbItem, type NavItemDefinition, type User } from '@/types';
import { type ReactNode } from 'react';

interface AppSidebarLayoutProps {
  children: ReactNode;
  breadcrumbs?: BreadcrumbItem[];
  user: User | null;
  mainNavItems?: NavItemDefinition[];
  moduleNavItems?: NavItemDefinition[];
  contextualNavItems?: NavItemDefinition[];
  globalNavItems?: NavItemDefinition[];
  headerActions?: ReactNode;
}

export default function AppSidebarLayout({
  children,
  breadcrumbs = [],
  user,
  mainNavItems = [],
  moduleNavItems = [],
  contextualNavItems = [],
  globalNavItems = [],
  headerActions,
}: Readonly<AppSidebarLayoutProps>) {
  return (
    <AppShell variant="sidebar">
      <AppSidebar
        user={user}
        mainNavItems={mainNavItems}
        moduleNavItems={moduleNavItems}
        contextualNavItems={contextualNavItems}
        globalNavItems={globalNavItems}
      />
      <AppContent variant="sidebar">
        <AppSidebarHeader breadcrumbs={breadcrumbs} headerActions={headerActions} />
        {children}
      </AppContent>
    </AppShell>
  );
}
