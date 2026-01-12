# Development Container

This directory contains the development container configuration for the Static Site Exporter plugin.

## What's Included

### VS Code Extensions

The devcontainer automatically installs the following extensions:

- **Intelephense** - PHP code intelligence
- **PHP Debug** - Debug support for PHP
- **PHP Sniffer** - Integrates PHP_CodeSniffer for WordPress coding standards
- **PHPUnit** - PHPUnit test integration
- **EditorConfig** - EditorConfig support for consistent coding styles
- **GitHub Copilot** - AI-powered code completion
- **GitHub Pull Requests** - Manage GitHub PRs and issues

### Features

- **Git** - Latest version with PPA support
- **GitHub CLI** - Command-line tool for GitHub

### Port Forwarding

- **Port 8088** - WordPress development server
- **Port 3306** - MySQL database

### Automatic Setup

When you open the project in a devcontainer, it will automatically:

1. Start WordPress and MySQL services via Docker Compose
2. Install Composer dependencies
3. Mount the project as a WordPress plugin

## Usage

### Using with VS Code

1. Install the [Dev Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers)
2. Open the project in VS Code
3. Click "Reopen in Container" when prompted (or use Command Palette: "Dev Containers: Reopen in Container")
4. Wait for the container to build and dependencies to install
5. Access WordPress at http://localhost:8088

### Using with GitHub Codespaces

1. Click "Code" → "Codespaces" → "Create codespace on branch"
2. Wait for the environment to be set up
3. Access WordPress via the forwarded port

## Docker Services

The devcontainer uses the main `docker-compose.yml` with overrides from `.devcontainer/docker-compose.yml`:

- **wordpress** - WordPress development environment (default service)
- **db** - MySQL database
- **wpcli** - WordPress CLI tools

## Configuration Files

- `devcontainer.json` - Main devcontainer configuration
- `docker-compose.yml` - Docker Compose overrides for devcontainer
- `../.editorconfig` - Editor configuration for consistent code style

## WordPress Coding Standards

The devcontainer is pre-configured to follow WordPress coding standards:

- PHP files use tabs (4 spaces width)
- PHP_CodeSniffer is configured with the WordPress ruleset
- Files are configured to have final newlines and trimmed trailing whitespace

## Running Tests

```bash
# Run all tests
script/cibuild

# Run PHPUnit tests only
script/cibuild-phpunit

# Run PHPCS linting only
script/cibuild-phpcs
```

## Installing Dependencies

```bash
# Composer dependencies (automatically installed on container creation)
composer install

# Update dependencies
composer update
```
