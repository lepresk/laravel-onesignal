# Contributing

Contributions are welcome and will be fully credited.

We accept contributions via Pull Requests on [Github](https://github.com/lepresk/laravel-onesignal).

## Pull Requests

- **Code Style** - Please follow the existing code style. We use Laravel Pint for formatting.
  ```bash
  composer format
  ```

- **Add tests** - Your patch won't be accepted if it doesn't have tests.
  ```bash
  composer test
  ```

- **Static Analysis** - Run PHPStan to ensure code quality.
  ```bash
  composer analyse
  ```

- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.

- **Consider our release cycle** - We try to follow [SemVer v2.0.0](https://semver.org/). Randomly breaking public APIs is not an option.

- **Create feature branches** - Don't ask us to pull from your master branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](https://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.

## Running Tests

```bash
composer test
```

## Code Quality Tools

### Format Code
```bash
composer format
```

### Static Analysis
```bash
composer analyse
```

### Test Coverage
```bash
composer test-coverage
```

## Reporting Issues

When reporting issues, please include:

1. Laravel version
2. PHP version
3. Steps to reproduce the issue
4. Expected behavior
5. Actual behavior
6. Error messages or stack traces

## Security Vulnerabilities

If you discover a security vulnerability, please email lepresk@gmail.com instead of using the issue tracker.

## Coding Standards

This project follows PSR-12 coding standard and PSR-4 autoloading standard.

## Development Workflow

1. Fork the repository
2. Create a new branch for your feature
3. Write tests for your feature
4. Implement your feature
5. Run tests and code quality tools
6. Commit your changes
7. Push to your fork
8. Submit a pull request
