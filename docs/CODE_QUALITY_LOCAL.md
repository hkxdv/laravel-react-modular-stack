# Guía de Calidad de Código Bilingüe con Interfaz Localizada

Esta guía define el estándar "código bilingüe con interfaz localizada" a aplicar en el proyecto. El objetivo es mantener el código fuente al 100% en inglés (naming, clases, métodos, archivos) mientras la documentación técnica (PHPDoc) y toda la interfaz de usuario (mensajes, vistas, notificaciones) se entregan en español neutro.

## Principios

- Código 100% en inglés: nombres de clases, métodos, variables, archivos y rutas internas.
- Documentación en español técnico: PHPDoc/JSDoc y comentarios explicativos complejos.
- Interfaz localizada en español: respuestas de API, textos de vistas y notificaciones.
- Guardrails de costo y calidad para integración LLM y webhooks.

## Convenciones Laravel 13 con Interfaz Localizada

- Routes/Controllers/Models deben seguir las convenciones estándar de Laravel 13.
- Usar `declare(strict_types=1);` en todos los archivos PHP.
- Tipado estricto en parámetros y retornos; propiedades con tipos.
- Formato PSR-12: aplicar `composer pint` para validar.

## Tipado y PSR-12 con Interfaz Localizada

- Siempre declarar tipos en métodos, propiedades y retornos.
- Aplicar PSR-12 con `composer pint` y ejecutar `composer rector:dry` para validar Rector sin modificar archivos.

## Checklist por archivo

- `strict_types` declarado.
- Nombres en inglés (clases, métodos, variables, archivos, rutas internas).
- PHPDoc en español técnico con tipos explícitos.
- Mensajes de interfaz en español.
- Tipos en propiedades y métodos; retornos no-nullable cuando aplique.
- Cumple PSR-12 (pint) y lint (rector, pint:test).
- Si integra LLM/MCP/webhooks: alias y guardrails validados; límites de tokens y captura de `usage` documentados.
- Pruebas en Pest (Feature/Unit) actualizadas.

## Uso

- Al crear o refactorizar archivos, sigue este documento como referencia práctica.
- Mantén el naming en inglés y la documentación/interfaz en español de forma consistente.

## Ciclo QA continuo (CI local)

- Ejecuta el ciclo QA en cada cambio del backend para asegurar formato, tipos y pruebas.
- Comando recomendado desde la raíz del proyecto: `bun run be qa`.
- El ciclo incluye:
  - `composer pint:test` para validar PSR-12.
  - `composer test:types` para análisis de tipos con PHPStan.
  - `composer test` para ejecutar Pest 5 sobre PHPUnit 13 (Unit/Feature).
  - `composer rector:dry` para validar Rector sin modificar archivos.
