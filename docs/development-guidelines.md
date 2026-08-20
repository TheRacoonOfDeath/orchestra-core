# Development Guidelines

## General

- Use PHP 8.3+
- Use strict typing
- Follow PSR-12

## WordPress

- Use namespace prefixes
- Never modify WordPress core
- Prefer WordPress APIs

## Namespaces

- Orchestra\Core
- Orchestra\Library
- Orchestra\Projects
- Orchestra\Members

## Plugins

Every plugin owns its data.

Cross-plugin database access is forbidden.

Use public services instead.