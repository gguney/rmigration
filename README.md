# RMigration - Reverse Migration Library

Reverse Migration Library for Laravel

### Requirements

- RMigration works with PHP 5.6 or above.

### Installation

```bash
$ composer require gguney/rmigration
```
Add package's service provider to your config/app.php

```php
...
        GGuney\RMigration\RMigraionServiceProvider::class,
...
```
### Usage
```bash
$ php artisan make:reverseMigration --create=users
```
or
```bash
$ php artisan make:reverseMigration --all
```

### Author

Gökhan Güney - <gokhanguneygg@gmail.com><br />

### License

RMigration is licensed under the MIT License - see the `LICENSE` file for details
