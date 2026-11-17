# ADR-0001 · Pila tecnológica de la plataforma propia

- **Estado:** ACEPTADA por el propietario el 2026-11-16 ("haz todo lo recomendado")
- **Fecha:** 2026-11-16

## Contexto
El sitio actual corre en Hostinger con PHP 8.2 y MySQL. El propietario pidió empezar ya la versión 1 propia. La visión a largo plazo incluye un núcleo SaaS multitenant.

## Decisión
PHP + Laravel 12, MySQL/MariaDB en el hosting y SQLite para desarrollo y demo. Estilos con Tailwind compilado (sin fuentes ni scripts externos).

## Por qué
- Corre en el hosting que ya se paga (Hostinger permite elegir PHP 8.3 por sitio) y usa la misma base MySQL, así la migración de datos es de base a base.
- Es el mismo lenguaje del legado (plugins propios en PHP), lo que facilita portar reglas.
- Laravel tiene colas, tareas programadas, pruebas y soporte para multitenant sin infraestructura nueva.

## Alternativas
- **TypeScript (Next.js) + PostgreSQL:** buena para el SaaS, pero exige un VPS o nube aparte de Hostinger.
- **Seguir en WooCommerce:** no cumple el objetivo de un activo propio.

## Consecuencias
- Se usa Laravel 12 (PHP 8.2+) para que corra en el mismo Hostinger sin cambiar la versión de PHP de marketcity.co. Al pasar producción a PHP 8.3 se sube a Laravel 13 (cambio menor).
- La demo en el subdominio usa SQLite (un archivo, sin crear base); producción usará MySQL.
- Si el propietario elige otra pila, el modelo de datos y las reglas (`docs/v1/alcance.md`) se conservan; se reescribe el código.
