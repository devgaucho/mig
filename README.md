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

$Mig=new Mig();
$tableDirectory=global\root().'/table';
$dbType='mysql';
$Mig->mig($pdo,$tableDirectory,$dbType);
```