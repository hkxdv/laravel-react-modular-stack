import { resolveRoute } from '@/lib/routing';
import { describe, expect, it } from 'vitest';

describe('resolveRoute', () => {
  it('pasa intactas las URLs relativas (root-relative)', () => {
    expect(resolveRoute('/internal/staff/dashboard')).toBe('/internal/staff/dashboard');
  });

  it('pasa intactas las URLs absolutas', () => {
    expect(resolveRoute('https://example.com/path')).toBe('https://example.com/path');
    expect(resolveRoute('http://example.com/path')).toBe('http://example.com/path');
  });

  it('resuelve un nombre de ruta punteado conocido', () => {
    // 'internal.staff.dashboard' → '/internal/staff/dashboard'
    expect(resolveRoute('internal.staff.dashboard')).toBe('/internal/staff/dashboard');
  });

  it('lanza error con nombre de ruta desconocido', () => {
    expect(() => resolveRoute('does.not.exist')).toThrow(/Unknown route name/);
  });
});
