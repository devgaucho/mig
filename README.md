# mig

## Instalação

```bash
composer require gaucho/mig
```

## Utilização

```php
use gaucho\Mig;

$pdo=/* instância do pdo */;
$dbType='mysql';
```

### Para apenas um diretório:

```php
$tableDirectory=__DIR__.'/table';
```

### Para vários diretórios:

```php
$tableDirectory=[
__DIR__.'/dir1',
__DIR__.'/dir2'
];
```

### Rodando, rodando:

```php
$Mig=new Mig($pdo,$tableDirectory,$dbType);
$Mig->mig();
```