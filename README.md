# orchestra-core
Core repo providing some fundamental functionality to the orchestra plugins and serving as a central project documentation.

## Documentation

- [Architecture](docs/architecture.md)
- [Domain model](docs/domain-model.md)
- [Use cases](docs/use-cases.md)
- [Roles and capabilities](docs/capabilities.md)
- [Persistence](docs/persistence.md)
- [Email service](docs/email.md)
- [Development guidelines](docs/development-guidelines.md)
- [Building a release](BUILD.md)

## Features

- The roles Orchestra Member, Conductor and Organizer, and the registry through which the other
  Orchestra plugins grant their capabilities
- The email service: an **Email** menu for conductors and organizers to write plain-text emails
  to groups defined by the other plugins, sent one per recipient in the background, with a log

## Updates

The plugin updates itself from the release assets of this public GitHub repository.
No token or configuration is required.

## Development

```bash
composer install
composer check   # coding standard, static analysis and tests
```
