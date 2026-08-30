# Hooks de Estado — Criterios de Uso

Guía para elegir entre `useForm`, `useServerTable` y los hooks de TanStack Query.

## Objetivo

Centralizar el criterio de cuándo usar hooks de estado de servidor de TanStack Query y cuándo preferir alternativas del stack Inertia.

## Cuándo usar cada hook

| Hook             | Cuándo usar                                                     | Cuándo NO usar                                  | Canal de datos               |
| ---------------- | --------------------------------------------------------------- | ----------------------------------------------- | ---------------------------- |
| `useForm`        | Formularios simples de creación o edición                       | Listas paginadas, consultas de solo lectura     | Props de Inertia             |
| `useServerTable` | Tablas con paginación, ordenamiento y búsqueda en servidor      | Forms, mutaciones, consultas que no sean tablas | Props de Inertia + Wayfinder |
| TanStack Query   | Solo para Sanctum (API REST de alto volumen, externo a Inertia) | Módulos que reciben props de Inertia            | Fetch directo a Sanctum      |

## Por qué los hooks de TanStack Query están reservados

Sanctum consume APIs REST que no siguen la convención de props de Inertia. Estas APIs:

- Requieren autenticación por token (no sesión cookie).
- Pueden generar alto volumen de peticiones.
- No se benefician del renderizado SSR de Inertia.

## Cliente HTTP para Sanctum

`lib/http.ts` ya está configurado con:

- `withCredentials: true` — cookies CSRF de Sanctum.
- Retry automático en código 419 (CSRF expirado).
- Header `X-Requested-With: XMLHttpRequest`.
- `baseURL` desde `VITE_APP_URL`.

No es necesario reconfigurar el cliente para nuevas consultas de Sanctum.
