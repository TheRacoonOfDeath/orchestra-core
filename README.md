# orchestra-core
Core repo providing some fundamental functionality to the orchestra plugins and serving as a central project documentation.

## GitHub update token

To avoid GitHub API rate limits, either add a token in WordPress admin at
`Settings -> Orchestra Core` or define it in `wp-config.php`:

```php
define('ORCHESTRA_GITHUB_TOKEN', 'your_github_token_here');
```

A fine-grained token with read-only access to this repository is sufficient.
If both are set, the `wp-config.php` constant takes precedence.
