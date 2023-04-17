# mig

## Instalação

```bash
composer require gaucho/router &&
composer require gaucho/mig
```

## Utilização

```php
use gaucho\global;
use gaucho\Mig;

$pdo=db()->pdo();
$tableDirectory=global\root().'/table';
$dbType='mysql';
$Mig=new Mig($pdo,$tableDirectory,$dbType);
$Mig->mig();
```