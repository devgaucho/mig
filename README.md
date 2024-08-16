# mig

Sistema básico de migrations em plain text

## Instalação

```bash
composer require gaucho/mig
```

## Utilização

### Formato das migrations:

Cada tabela é representada através de um arquivo TXT com as colunas.

#### Exemplo:

Arquivo "table/users" ou "table/users.txt" (a extensão é opcional)

```
id
name_32
email_32
```

Colunas "id" são automaticamente convertidas para AUTO_INCREMENT

Os prefixos numéricos opcionais são convertidos para VARCHAR

### Para apenas um diretório:

```php
$table_dir=__DIR__.'/table';
```

### Para vários diretórios:

```php
$table_dir=[
__DIR__.'/dir1',
__DIR__.'/dir2'
];
```


### Corre Forrest! Corre! 🏃‍♂️

```php
use gaucho\mig;

$pdo=/* instância do pdo */;
$db_type='mysql';//sqlite
$gaucho_mig=new mig($pdo,$table_dir,$db_type);
$gaucho_mig->mig();
```
