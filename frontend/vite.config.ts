import tailwindcss from '@tailwindcss/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import { loadEnv } from 'vite';
import { defineConfig } from 'vitest/config';

export default defineConfig(({ mode }) => {
  const envDir = path.resolve(import.meta.dirname, '../.envs');
  const env = loadEnv(mode, envDir, '');

  const isProduction = mode === 'production';
  const isDocker = env['APP_RUNNING_IN_CONTAINER'] === 'true';
  const host = isProduction || isDocker ? '0.0.0.0' : 'localhost';

  // Obtener la URL de la aplicación desde las variables de entorno para HMR
  const appUrl = env['VITE_APP_URL'] ?? 'http://localhost:8080';
  // Extraer el hostname de la URL para usarlo en la configuración de HMR
  const appHostname = new URL(appUrl).hostname;

  return {
    envDir,
    server: {
      host,
      port: 5173,
      hmr: {
        // En test-producción o cuando Vite sirve para Docker/LAN, usar el hostname de APP_URL
        host: isProduction || isDocker ? appHostname : 'localhost',
      },
      watch: {
        usePolling: true,
      },
    },
    preview: {
      host,
      port: 5173,
    },
    build: {
      emptyOutDir: true,
      chunkSizeWarningLimit: 1024,
      rollupOptions: {
        output: {
          // Vite 8 (rolldown) only accepts manualChunks as a function,
          // not the Rollup object shorthand.
          manualChunks: (moduleId: string): string | undefined => {
            if (!moduleId.includes('node_modules')) return;

            if (
              moduleId.includes('react') ||
              moduleId.includes('react-dom') ||
              moduleId.includes('react-day-picker')
            ) {
              return 'react-vendor';
            }
            if (
              moduleId.includes('@radix-ui') ||
              moduleId.includes('lucide-react') ||
              moduleId.includes('sonner') ||
              moduleId.includes('class-variance-authority') ||
              moduleId.includes('clsx') ||
              moduleId.includes('tailwind-merge')
            ) {
              return 'ui-vendor';
            }
            if (moduleId.includes('@tanstack')) {
              return 'tanstack-vendor';
            }
            if (moduleId.includes('@inertiajs') || moduleId.includes('axios')) {
              return 'inertia-vendor';
            }
            if (moduleId.includes('motion') || moduleId.includes('tailwindcss-animate')) {
              return 'motion-vendor';
            }

            // Required by tsconfig noImplicitReturns; not redundant.
            // eslint-disable-next-line sonarjs/no-redundant-jump
            return;
          },
        },
      },
    },
    plugins: [
      ...(mode === 'test'
        ? []
        : [
            laravel({
              input: 'src/app.tsx',
              publicDirectory: '../backend/public',
              refresh: true,
            }),
            wayfinder({
              formVariants: true,
              path: 'src',
              command: 'php ../backend/artisan wayfinder:generate',
            }),
          ]),
      react(),
      tailwindcss(),
    ],
    test: {
      environment: 'jsdom',
      setupFiles: ['./src/test/setup.ts'],
    },
    resolve: {
      alias: {
        '@': path.resolve(import.meta.dirname, 'src'),
        '/fonts': path.resolve(import.meta.dirname, '../backend/public/fonts'),
      },
      extensions: ['.tsx', '.ts', '.js', '.json'],
    },
  };
});
