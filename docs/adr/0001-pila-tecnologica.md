# ADR-0001 · Pila tecnológica de la plataforma propia

- **Estado:** PROPUESTA (pendiente de decisión del propietario)
- **Fecha:** 2026-11-16

## Contexto
El sitio actual corre en Hostinger con PHP 8.2 y MySQL. El propietario pidió empezar ya la versión 1 propia. La visión a largo plazo incluye un núcleo SaaS multitenant.

## Decisión propuesta
PHP 8.3 + Laravel 13, MySQL/MariaDB en el hosting y SQLite para desarrollo y demo. Estilos con Tailwind compilado (sin fuentes ni scripts externos).

## Por qué
- Corre en el hosting que ya se paga (Hostinger permite elegir PHP 8.3 por sitio) y usa la misma base MySQL, así la migración de datos es de base a base.
- Es el mismo lenguaje del legado (plugins propios en PHP), lo que facilita portar reglas.
- Laravel tiene colas, tareas programadas, pruebas y soporte para multitenant sin infraestructura nueva.

## Alternativas
- **TypeScript (Next.js) + PostgreSQL:** buena para el SaaS, pero exige un VPS o nube aparte de Hostinger.
- **Seguir en WooCommerce:** no cumple el objetivo de un activo propio.

## Consecuencias
- Hay que subir el sitio de la plataforma a PHP 8.3 en hPanel (Laravel 13 lo exige).
- Si el propietario elige otra pila, el modelo de datos y las reglas (`docs/v1/alcance.md`) se conservan; se reescribe el código.
