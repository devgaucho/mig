# mig

Sistema básico de migrations em plain text

## Instalação

```bash
composer require gaucho/mig
```

## Utilização

### Formato das migrations:

"table/users" ou "table/users.txt" (a extensão é opcional)

```
id
name_32
email_32
```

Colunas "id" são automaticamente convertidas para AUTO_INCREMENT

Os prefixos numéricos opcionais são convertidos para VARCHAR

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


### Corre Forrest! Corre! 🏃‍♂️

```php
use gaucho\Mig;

$pdo=/* instância do pdo */;
$dbType='mysql';//sqlite
$Mig=new Mig($pdo,$tableDirectory,$dbType);
$Mig->mig();
```
