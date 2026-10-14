# orchestra-core
Core repo providing some fundamental functionality to the orchestra plugins and serving as a central project documentation.

## Documentation

- [Architecture](docs/architecture.md)
- [Domain model](docs/domain-model.md)
- [Use cases](docs/use-cases.md)
- [Roles and capabilities](docs/capabilities.md)
- [Persistence](docs/persistence.md)
- [Development guidelines](docs/development-guidelines.md)
- [Building a release](BUILD.md)

## Updates

The plugin updates itself from the release assets of this public GitHub repository.
No token or configuration is required.

## Development

```bash
composer install
composer check   # coding standard, static analysis and tests
```
