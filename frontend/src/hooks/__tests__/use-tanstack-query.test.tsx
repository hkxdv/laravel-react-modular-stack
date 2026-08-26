import type { Paginated } from '@/types';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { renderHook } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { usePaginatedQuery } from '../use-tanstack-query';

const createWrapper = () => {
  const queryClient = new QueryClient();
  return function QueryClientWrapper({ children }: { children: React.ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  };
};

describe('usePaginatedQuery', () => {
  const defaultPaginatedData: Paginated<{ id: number }> = {
    data: [{ id: 1 }],
    meta: {
      current_page: 1,
      from: 1,
      last_page: 5,
      links: [],
      path: '/test',
      per_page: 10,
      to: 10,
      total: 47,
    },
    links: {
      first: '/test?page=1',
      last: '/test?page=5',
      next: '/test?page=2',
      prev: null,
    },
  };

  describe('pagination metadata', () => {
    it('returns correct total and lastPage from fetchFn response', async () => {
      const fetchFn = vi.fn().mockResolvedValue(defaultPaginatedData);

      const { result } = renderHook(() => usePaginatedQuery(['test'], fetchFn, 1, 10), {
        wrapper: createWrapper(),
      });

      await vi.waitFor(() => {
        expect(result.current.pagination.total).toBe(47);
        expect(result.current.pagination.lastPage).toBe(5);
      });
    });

    it('returns defaults (total=0, lastPage=1) when data is undefined', async () => {
      const fetchFn = vi.fn().mockResolvedValue(undefined);

      const { result } = renderHook(() => usePaginatedQuery(['test'], fetchFn, 1, 10), {
        wrapper: createWrapper(),
      });

      await vi.waitFor(() => {
        expect(result.current.pagination.total).toBe(0);
        expect(result.current.pagination.lastPage).toBe(1);
      });
    });
  });

  describe('handlePageChange', () => {
    it('does not change page when newPage exceeds lastPage', async () => {
      const fetchFn = vi.fn().mockResolvedValue(defaultPaginatedData);

      const { result } = renderHook(() => usePaginatedQuery(['test'], fetchFn, 1, 10), {
        wrapper: createWrapper(),
      });

      await vi.waitFor(() => {
        expect(fetchFn).toHaveBeenCalled();
      });

      result.current.pagination.setPage(10);

      expect(result.current.pagination.page).toBe(1);
      expect(fetchFn).toHaveBeenCalledTimes(1);
    });

    it('allows navigation to valid page within bounds', async () => {
      const fetchFn = vi.fn().mockResolvedValue(defaultPaginatedData);

      const { result } = renderHook(() => usePaginatedQuery(['test'], fetchFn, 1, 10), {
        wrapper: createWrapper(),
      });

      await vi.waitFor(() => {
        expect(fetchFn).toHaveBeenCalled();
      });

      result.current.pagination.setPage(3);

      await vi.waitFor(() => {
        expect(result.current.pagination.page).toBe(3);
      });
    });

    it('does not change page when newPage is less than 1', async () => {
      const fetchFn = vi.fn().mockResolvedValue(defaultPaginatedData);

      const { result } = renderHook(() => usePaginatedQuery(['test'], fetchFn, 1, 10), {
        wrapper: createWrapper(),
      });

      await vi.waitFor(() => {
        expect(fetchFn).toHaveBeenCalled();
      });

      result.current.pagination.setPage(0);

      expect(result.current.pagination.page).toBe(1);
    });
  });
});
