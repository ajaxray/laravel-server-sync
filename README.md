# Laravel Server Sync

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ajaxray/laravel-server-sync.svg?style=flat-square)](https://packagist.org/packages/ajaxray/laravel-server-sync)
[![Total Downloads](https://img.shields.io/packagist/dt/ajaxray/laravel-server-sync.svg?style=flat-square)](https://packagist.org/packages/ajaxray/laravel-server-sync)
[![License](https://img.shields.io/packagist/l/ajaxray/laravel-server-sync.svg?style=flat-square)](https://packagist.org/packages/ajaxray/laravel-server-sync)

A Laravel package to easily sync your production/staging database and storage files to your local environment. Perfect for debugging production issues or keeping your local environment up to date with production data.

> **Note**: This package is now stable and ready for production use. It supports Laravel 10.x and 11.x.

![Laravel Server Sync Banner](/arts/laravel_server_sync_banner.jpg)


## Features

- 🔄 One-command sync of both database and files
- 🔒 Secure SSH-based file transfer
- 📁 Smart file syncing with rsync
- 🗄️ Database dump and restore
- ⚡ Progress indicators for long operations
- 🛠️ Highly configurable

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- SSH access to production server
- MySQL/MariaDB client installed locally
- rsync installed on both local and production servers

## Installation

```bash
composer require ajaxray/laravel-server-sync
```

To install for dev environments
```bash
composer require --dev ajaxray/laravel-server-sync
```

After installation, publish the configuration file:

```bash
php artisan vendor:publish --provider="Ajaxray\ServerSync\ServerSyncServiceProvider"
```

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
PROD_SSH_HOST=your-production-server.com
PROD_SSH_USER=your-ssh-username
PROD_SSH_PATH=/path/to/laravel
PROD_SSH_KEY=/path/to/your/private/key
```

### Configuration File

The published config file `config/server-sync.php` allows you to:

- Configure default server settings
- Exclude specific tables from database sync
- Configure file sync paths and exclusions
- Customize dump location

### SSH Key Authentication

For enhanced security, you can specify SSH private key files for authentication:

**Via Environment Variables:**
```env
PROD_SSH_KEY=/home/user/.ssh/production_key
STAGING_SSH_KEY=/home/user/.ssh/staging_key
```

**Via Command Line:**
```bash
php artisan sync:pull --key=/path/to/private/key
```

**Via Configuration File:**
```php
'production' => [
    'host' => 'prod.example.com',
    'user' => 'deploy',
    'path' => '/var/www/app',
    'key' => '/home/user/.ssh/production_key',
],
```

> **Note**: Ensure your private key file has proper permissions (600) and that the corresponding public key is added to the server's `~/.ssh/authorized_keys` file.

## Usage

### Basic Usage

Sync both database and files:

```bash
php artisan sync:pull
```

### Database Sync Options

```bash
# Skip database sync entirely
php artisan sync:pull --skip-db

# Exclude specific tables
php artisan sync:pull --exclude-tables=logs,cache,sessions

# Only sync specific tables
php artisan sync:pull --only-tables=users,products,orders
```

### File Sync Options

```bash
# Skip file sync entirely
php artisan sync:pull --skip-files

# Sync files with deletion (removes local files that don't exist in production)
php artisan sync:pull --delete
```

### Server Configuration Options
By default, it'll take host, user, path etc. from the production server definition in config file.

Also we can specify which remote server definition to use (see config file), if we have defined multiple servers. If any config is specified as inline option, it'll get precidence over config values.

```bash
php artisan sync:pull --remote=staging

# Override server details inline
php artisan sync:pull --host=prod.example.com --user=deploy --path=/var/www/app

# Specify SSH key file for authentication
php artisan sync:pull --key=/path/to/private/key

# All options can be specified inline to overwrite config values
php artisan sync:pull --host=prod.example.com --user=deploy --key=~/.ssh/production_key --exclude-tables=logs,migrations 
```

### Safety Features

```bash
# Force sync in production environment (use with caution!)
php artisan sync:pull --force
```

> **Note**: The `--force` option should be used with extreme caution as it overrides the production environment safety check.

## Security

- Uses SSH for secure file transfer
- Supports SSH key-based authentication (recommended)
- Can specify custom SSH private key files via `--key` option or configuration
- Temporary files are automatically cleaned up
- Database credentials are never stored locally

## Troubleshooting

### SSH Connection Issues

- Verify SSH key-based authentication is set up
- Check if you can manually SSH into the server using the key: `ssh -i /path/to/key user@host`
- Ensure proper permissions for the SSH user and private key file (600 for key file)
- Use `--key` option or configure SSH key path in config file if using custom key location

### Database Sync Issues

- Verify MySQL client installation
- Check database credentials in both environments
- Ensure sufficient privileges for database operations

### File Sync Issues

- Verify rsync installation
- Check storage directory permissions
- Use stable internet connection for large files

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

- [Anis Uddin Ahmad](https://github.com/ajaxray)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information. 