import * as topLevel from '@/routes';
import internalRoutes from '@/routes/internal';
import passwordRoutes from '@/routes/password';
import tenantRoutes from '@/routes/tenant';
import verificationRoutes from '@/routes/verification';

/**
 * Dotted-name resolver for route names that arrive dynamically from the
 * backend (nav config `route_name`/`href`, breadcrumbs, server tables).
 *
 * Static call sites should import generated Wayfinder modules directly
 * (`@/routes/**`, `@/actions/**`) for compile-time safety.
 */

interface WayfinderLeaf {
  url: (args?: unknown, options?: unknown) => string;
}

function isLeaf(node: unknown): node is WayfinderLeaf {
  return typeof node === 'function' && typeof (node as { url?: unknown }).url === 'function';
}

const registry: Record<string, unknown> = {
  welcome: topLevel.welcome,
  login: topLevel.login,
  logout: topLevel.logout,
  password: passwordRoutes,
  verification: verificationRoutes,
  internal: internalRoutes,
  tenant: tenantRoutes,
};

/**
 * Resolves a backend route name (e.g. `internal.staff.admin.users.index`)
 * to its URL string. Throws loudly on unknown names — same contract Ziggy had.
 */
export function resolveRoute(name: string, args?: unknown): string {
  let node: unknown = registry;

  for (const segment of name.split('.')) {
    if (node === null || typeof node !== 'object' || !(segment in node)) {
      throw new Error(`[routing] Unknown route name: ${name}`);
    }
    node = (node as Record<string, unknown>)[segment];
  }

  if (!isLeaf(node)) {
    throw new Error(`[routing] Route name does not resolve to a route: ${name}`);
  }

  return node.url(args);
}
