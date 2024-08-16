<?php
namespace gaucho;
use PDO;
use PDOException;
class mig{
	var $conn;
	var $tableDirectory;
	var $dbType;
	var $debug;	
	var $tamanhos;
	function __construct($conn,$tableDirectory,$dbType,$debug=1){
		$this->conn=$conn;
		$this->tableDirectory=$tableDirectory;
		$this->dbType=$dbType;
		$this->debug=$debug;
	}
	function createColumn($columnName,$tableName){
		$tableColumns=$this->getTableColumns($tableName);
		if(in_array($columnName,$tableColumns)){
			return true;
		}else{
			switch ($this->dbType){
				case 'mysql':
				if($columnName=='id'){
					$sufix='bigint(20) ';
					$sufix.='UNSIGNED NOT NULL ';
					$sufix.='AUTO_INCREMENT';
				}else{
					$tamanho=@$this
					->tamanhos
					[$tableName][$columnName];
					if($tamanho){
						$sufix='VARCHAR(';
						$sufix.=$tamanho.')';
					}else{
						$sufix='LONGTEXT ';
						$sufix.='NULL';
					}
				}
				$sql='ALTER TABLE `';
				$sql.=$tableName.'` ADD `';
				$sql.=$columnName.'` '.$sufix.'; ';
				break;
				case 'sqlite':
				if($columnName=='id'){
					$sufix='INTEGER PRIMARY ';
					$sufix.='KEY ';
					$sufix.='AUTOINCREMENT';
				}else{
					$tamanho=@$this
					->tamanhos
					[$tableName][$columnName];
					if($tamanho){
						$sufix='VARCHAR(';
						$sufix.=$tamanho.')';
					}else{
						$sufix='TEXT';
					}
				}
				$sql='ALTER TABLE `'.$tableName;
				$sql.='` ADD COLUMN `';
				$sql.=$columnName.'` '.$sufix.';';
				break;
			}
			return $this->query($sql);
		}
	}
	function createTable($tableName,$columnNames){
		if(!in_array('id',$columnNames)){
			$columnNames[]='id';
		}
		$sql='CREATE TABLE IF NOT EXISTS `';
		$sql.=$tableName.'` ('.PHP_EOL;
		switch ($this->dbType){
			case 'mysql':
			$sql=$sql;
			$id='bigint(20) UNSIGNED NOT NULL ';
			$id.='AUTO_INCREMENT,'.PHP_EOL;
			$id.=chr(9).'PRIMARY KEY (id)';
			$text='LONGTEXT NULL';
			$sufix=' ENGINE=InnoDB;';
			break;

			case 'sqlite':
			$id='INTEGER PRIMARY KEY AUTOINCREMENT';
			$text='TEXT';
			$sufix=';';
			break;
		}
		$end=end($columnNames);
		foreach ($columnNames as $columnName){
			if($columnName=='id'){
				$type=$id;
			}else{
				$tamanho=@$this
				->tamanhos[$tableName][$columnName];
				if($tamanho){
					$type='VARCHAR(';
					$type.=$tamanho.')';
				}else{
					$type=$text;
				}
			}
			$sql.=chr(9).'`'.$columnName.'` '.$type;
			$comma=null;
			if($columnName != $end){
				$comma=',';
			}
			$sql.=$comma.PHP_EOL;
		}
		$sql.=')'.$sufix;
		return $this->query($sql);
	}
	function debug($str){
		if($this->debug){
			print $str;
		}
	}
	function dropColumn($columnName,$tableName){
		switch ($this->dbType){
			case 'mysql':
			$sql='ALTER TABLE `'.$tableName;
			$sql.='` DROP `'.$columnName.'`;';
			return $this->query($sql);
			break;
			case 'sqlite':
			// pega as colunas da tabela antiga
			$columnNames=$this->getTableColumns(
				$tableName
			);
			$columnNames=array_flip($columnNames);
			// remove a coluna a ser deletada
			unset($columnNames[$columnName]);
			$columnNames=array_flip($columnNames);
			// cria a tabela temporária
			$tmpTableName='mig_tmp_table';
			$this->dropTable($tmpTableName);
			$this->createTable(
				$tmpTableName,$columnNames
			);
			$columnNamesInline='`';
			$columnNamesInline.=implode(
				'`,`',$columnNames
			);
			$columnNamesInline.='`';
			$sql='INSERT INTO '.$tmpTableName;
			$sql.=' SELECT ';
			$sql.=$columnNamesInline.' FROM `';
			$sql.=$tableName.'`;';
			$this->query($sql);
			$this->dropTable($tableName);
			$this->renameTable($tmpTableName,$tableName);
			return true;
			break;
		}
	}
	function dropTable($tableName){
		$sql='DROP TABLE IF EXISTS `'.$tableName.'`;';
		return $this->query($sql);
	}
	function extrairOsTamanhos($migrations){
		$tamanhos=[];
		foreach ($migrations as $migration=>$cols){
			foreach ($cols as $key=>$colName){
				$arr=explode('_',$colName);
				$end=end($arr);
				if(is_numeric($end)){
					$tamanho=$end;
					$chaveDaColuna=key($arr);
					unset($arr[$chaveDaColuna]);
					$colName=implode('_',$arr);
					$migrations
					[$migration][$key]=$colName;
					$tamanhos[$migration]
					[$colName]=$tamanho;
				}
			}
		}
		$this->tamanhos=$tamanhos;
		return $migrations;
	}
	function getMigrations(){
		if(is_array($this->tableDirectory)){
			$migrationsFiles=[];
			foreach ($this->tableDirectory as $value){
				$glob=glob($value.'/*');
				$migrationsFiles=array_merge(
					$migrationsFiles,$glob
				);
			}
		}else{
			$migrationsFiles=glob(
				$this->tableDirectory.'/*'
			);
		}
		$migrations=[];
		foreach ($migrationsFiles as $filename){
			$str=trim(file_get_contents($filename));
			$columnNames=array_map(
				'trim',explode(PHP_EOL,$str)
			);
			sort($columnNames);
			$migrationName=basename($filename);
			$migrations[$migrationName]=$columnNames;
		}
		$migrations=$this->extrairOsTamanhos($migrations);
		return $migrations;
	}
	function getTableColumns($tableName){
		switch ($this->dbType){
			case 'mysql':
			$sql='SHOW COLUMNS FROM `'.$tableName.'`;';
			$column=0;
			break;

			case 'sqlite':
			$sql='PRAGMA table_info('.$tableName.');';
			$column=1;
			break;
		}
		$arr=$this->query($sql)
		->fetchAll(PDO::FETCH_COLUMN,$column);
		sort($arr);
		return $arr;
	}
	function getTableList(){
		switch ($this->dbType){
			case 'mysql':
			$sql='SHOW TABLES;';
			break;

			case 'sqlite':
			$sql='SELECT name FROM sqlite_master ';
			$sql.='WHERE type="table" AND name NOT ';
			$sql.='LIKE "sqlite_%";';
			break;
		}
		$arr=$this->query($sql)
		->fetchAll(PDO::FETCH_COLUMN,0);
		sort($arr);
		return $arr;
	}
	function getTables(){
		$tableList=$this->getTableList();
		$tables=[];
		foreach ($tableList as $tableName){
			$tables[$tableName]=$this
			->getTableColumns($tableName);
		}
		return $tables;
	}
	function mig(){
		// pegar as migrations limpas a partir do arquivos
		$migrations=$this->getMigrations();
		// extrair o nome das tabelas dos arquivos
		$migrationsList=array_keys($migrations);
		// pega as tabelas do banco
		$tables=$this->getTables();
		// extrai o nome das tabelas do banco
		$tablesList=array_keys($tables);
		// pega a lista de tabelas para serem excluídas
		$tablesToDelete=array_diff(
			$tablesList,$migrationsList
		);
		// apagar as tabelas que não existem nas migrations
		foreach ($tablesToDelete as $tableName){
			$this->dropTable($tableName);
			unset($tables[$tableName]);
			$msg='tabela "'.$tableName;
			$msg.='" apagada com sucesso'.PHP_EOL;
			$this->debug($msg);
		}
		// pega a lista de tabelas para serem criadas
		$tablesToCreate=array_diff(
			$migrationsList,$tablesList
		);
		// cria as tabelas que não existem
		foreach ($tablesToCreate as $tableName){
			$columnNames=$migrations[$tableName];
			$this->createTable($tableName,$columnNames);
			unset($migrations[$tableName]);
			$msg='tabela "'.$tableName;
			$msg.='" criada com sucesso'.PHP_EOL;
			$this->debug($msg);
		}
		// criar e a apagar as colunas e mudar o tamanho
		foreach ($tables as $tableName=>$tableColumns){
			$migrationColumns=$migrations[$tableName];
			$columnsToDelete=array_diff(
				$tableColumns,$migrationColumns
			);
			// apaga as colunas que
			// não existem nas migrations
			foreach ($columnsToDelete as $columnName){
				$this->dropColumn(
					$columnName,$tableName
				);
				$msg='coluna "'.$columnName;
				$msg.='" da tabela "'.$tableName;
				$msg.='" apagada com sucesso';
				$msg.=PHP_EOL;
				$this->debug($msg);
			}
			$columnsToCreate=array_diff(
				$migrationColumns,$tableColumns
			);
			// cria as colunas que não existem no banco
			foreach ($columnsToCreate as $columnName){
				$this->createColumn(
					$columnName,$tableName
				);
				$msg='coluna "'.$columnName;
				$msg.='" da tabela "'.$tableName;
				$msg.='" criada com sucesso'.PHP_EOL;
				$this->debug($msg);
			}
			// mudar o tamanho
			foreach ($migrationColumns as $columnName){
				if(
					isset(
						$this
						->tamanhos
						[$tableName]
						[$columnName]
					)
				){
					$tamanho=$this
					->tamanhos
					[$tableName][$columnName];
					$this->mudarOTamanho(
						$tableName,
						$columnName,
						$tamanho
					);
				}
			}
		}
		$msg='migrations executadas com sucesso'.PHP_EOL;
		$this->debug($msg);
	}
	function mudarOTamanho($tableName,$columnName,$tamanho){
		if($this->dbType=='mysql'){
			$sql='ALTER TABLE `'.$tableName.'` CHANGE `';
			$sql.=$columnName.'` `'.$columnName;
			$sql.='` VARCHAR(';
			$sql.=$tamanho.');';
			return $this->query($sql);
		}
	}
	function query($sql){
		try {
			return $this->conn->query($sql);
		} catch (PDOException $e){
			$msg='erro ao executar a seguinte query: ';
			$msg.=PHP_EOL.$sql.PHP_EOL;
			$this->debug($msg);
			die($e->getMessage().PHP_EOL);
		}
	}
	function renameTable($oldTableName,$newTableName){
		switch ($this->dbType){
			case 'mysql':
			$sql='ALTER TABLE `'.$oldTableName;
			$sql.='` RENAME `'.$newTableName.'`';
			break;
			case 'sqlite':
			$sql='ALTER TABLE `'.$oldTableName;
			$sql.='` RENAME TO `'.$newTableName.'`';
			break;
		}
		return $this->query($sql);
	}
}
