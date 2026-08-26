---
name: es-doc-style
description: "Trigger: escribir o editar PHPDoc, comentarios de código, documentación markdown, README, guías o PR descriptions en español. Aplica registro técnico neutro y evita anglicismos y términos formales."
license: MIT
metadata:
  author: foundry-stack
  version: "1.0"
  project: foundry-stack
---

# Spanish Doc Style — Registro técnico neutro

## Activation Contract

Aplica SIEMPRE que escribas o edites prosa en español dentro del proyecto: bloques PHPDoc (`@param`, `@return`, `@throws`), comentarios inline, archivos `.md` (README, `docs/**`, guías) y descripciones de PR. NO aplica a identificadores de código.

## Hard Rules

- Estándar bilingüe del repo (docs/CODE_QUALITY_LOCAL.md): código 100% en inglés (naming, clases, métodos, archivos); prosa de documentación e interfaz en español neutro.
- Registro neutro profesional: nunca formal-jurídico ("asevera"), nunca coloquial.
- Verbos preferidos: valida, convierte, filtra, devuelve, obtiene, lanza, normaliza.
- Para type narrowing escribe "PHPStan considera la variable original como X", no "acota".
- Cero anglicismos en prosa (ver tabla). El término inglés solo se conserva si es nombre propio de API, tipo del lenguaje, o carece de traducción establecida (ej.: string, mixed, nullable, offset, middleware).
- NUNCA traduzcas nombres de clases, métodos, parámetros ni tipos.
- Frases completas en tiempo presente, con puntuación; cada descripción `@param`/`@return` termina en punto.
- Tipos exactos en PHPDoc: evita `mixed` cuando el tipo real es conocido.
- Sin referencias externas dentro del bloque PHPDoc (las guías de estilo viven en `docs/`, no en el código).
- Al editar, elimina comentarios obsoletos o redundantes.

## Tabla normativa (prohibido → usa)

| Prohibido                      | Usa                                   |
| ------------------------------ | ------------------------------------- |
| asevera                        | valida                                |
| acota el tipo                  | considera la variable original como X |
| se coercen / coerción aplicada | se convierten automáticamente         |
| el default                     | el valor por defecto                  |
| falla rápido / fail fast       | falla inmediatamente                  |
| factories de DTO               | al construir DTOs                     |
| parsear                        | interpretar / leer (según contexto)   |

## Decision Gates

| Situación                            | Regla                                                      |
| ------------------------------------ | ---------------------------------------------------------- |
| Duda entre dos términos              | Elige el más común en documentación técnica en español     |
| Concepto solo existente en inglés    | Conserva el inglés, no fuerces traducción                  |
| Regionalismo detectado               | Reemplaza por equivalente neutro                           |
| Término ya usado así en todo el repo | Mantén consistencia con el repo sobre preferencia personal |

## Execution Steps

1. Redacta la prosa usando los verbos preferidos y la tabla normativa.
2. Antes de terminar, escanea la búsqueda de términos prohibidos.
3. Si tocaste archivos PHP, corre `composer pint:test` desde `backend/`.

## Output Contract

Documentación en español neutro. Si dudaste entre términos, menciónalo brevemente en tu respuesta final.

## References

- `docs/CODE_QUALITY_LOCAL.md` — fuente normativa: estándar "código bilingüe con interfaz localizada" y requisitos de calidad PHPDoc. Esta skill implementa su sección de documentación.
- `.opencode/skills/laravel-foundry/SKILL.md` — sección "PHPDoc (español técnico neutro)", convención base del proyecto.
