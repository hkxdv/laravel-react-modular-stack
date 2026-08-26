declare namespace Modules {
  namespace Core {
    namespace Application {
      namespace View {
        export type AuthPageProps = {
          readonly user: Record<string, any> | null;
          readonly staff: Record<string, any> | null;
          readonly impersonate: boolean;
          readonly can: Record<string, boolean>;
        };
        export type GlobalPageProps = {
          readonly breadcrumbs: Modules.Core.Domain.Menu.ResolvedBreadcrumbItem[];
          readonly mainNavItems: Modules.Core.Domain.Menu.ResolvedNavItem[];
          readonly moduleNavItems: Modules.Core.Domain.Menu.ResolvedNavItem[];
          readonly contextualNavItems: Modules.Core.Domain.Menu.ResolvedNavItem[];
          readonly globalNavItems: Modules.Core.Domain.Menu.ResolvedNavItem[];
          readonly passwordChangeRequired: boolean;
          readonly auth: Modules.Core.Application.View.AuthPageProps;
          readonly security: Modules.Core.Application.View.SecurityPageProps;
          readonly notificationPreferences: Record<string, any>;
        };
        export type SecurityPageProps = {
          readonly twoFactorRequired: boolean;
          readonly twoFactorEnabled: boolean;
          readonly twoFactorPending: boolean;
        };
      }
    }
    namespace Domain {
      namespace Addon {
        export type AddonConfig = {
          readonly moduleSlug: string;
          readonly functionalName: string;
          readonly description: string | null;
          readonly authGuard: string | null;
          readonly basePermission: string | null;
          readonly inertiaViewDirectory: string;
          readonly raw: Record<string, any>;
        };
      }
      namespace Menu {
        export type BreadcrumbItem = {
          readonly title: string;
          readonly routeNameSuffix: string;
          readonly dynamicTitleProp: string | null;
        };
        export type BreadcrumbMap = {
          readonly items: Record<string, Modules.Core.Domain.Menu.BreadcrumbItem[]>;
        };
        export type ContextualNavMap = {
          readonly items: Record<
            string,
            (
              | Modules.Core.Domain.Menu.NavComponentLink
              | Modules.Core.Domain.Menu.NavComponentGroup
            )[]
          >;
        };
        export type ModulePageProps = {
          readonly panelItems: Modules.Core.Domain.Menu.ResolvedPanelItem[];
          readonly mainNavItems: Modules.Core.Domain.Menu.ResolvedNavItem[];
          readonly moduleNavItems: Modules.Core.Domain.Menu.ResolvedNavItem[];
          readonly contextualNavItems: Modules.Core.Domain.Menu.ResolvedNavItem[];
          readonly globalNavItems: Modules.Core.Domain.Menu.ResolvedNavItem[];
          readonly breadcrumbs: Modules.Core.Domain.Menu.ResolvedBreadcrumbItem[];
          readonly stats: any[];
          readonly pageTitle: string;
          readonly description: string | null;
          readonly flash: Record<string, any>;
        };
        export type NavComponentGroup = {
          readonly name: string;
          readonly links: Modules.Core.Domain.Menu.NavComponentLink[];
        };
        export type NavComponentLink = {
          readonly key: string;
          readonly title: string;
          readonly routeNameSuffix: string;
          readonly icon: string;
          readonly permission: string | null;
        };
        export type NavItem = {
          readonly title: string;
          readonly routeNameSuffix: string;
          readonly icon: string;
          readonly permission: string | null;
          readonly showInNav: boolean;
          readonly showInMainNav: boolean;
        };
        export type ResolvedBreadcrumbItem = {
          readonly title: string;
          readonly href: string;
        };
        export type ResolvedNavItem = {
          readonly title: string;
          readonly href: string;
          readonly icon: string | null;
          readonly current: boolean;
          readonly permission: string | string[] | null;
        };
        export type ResolvedPanelItem = {
          readonly name: string;
          readonly icon: string | null;
          readonly permission: string | string[] | null;
          readonly route_name: string | null;
          readonly description: string | null;
        };
      }
      namespace Panel {
        export type PanelItem = {
          readonly name: string;
          readonly description: string;
          readonly routeNameSuffix: string;
          readonly icon: string;
          readonly permission: string | null;
        };
      }
      namespace User {
        export type DomainUser = {
          readonly id: undefined;
          readonly name: string;
          readonly email: string;
          readonly roles: string[];
          readonly permissions: undefined;
          readonly emailVerifiedAt: string | null;
          readonly userType: string | null;
          readonly avatar: string | null;
          readonly createdAt: string | null;
          readonly updatedAt: string | null;
        };
      }
    }
  }
}
