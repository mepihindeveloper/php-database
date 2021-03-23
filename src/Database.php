<?php

declare(strict_types = 1);

namespace mepihindeveloper\components;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Класс Database
 * Класс реализует подключение и управление запросами к базе данных
 *
 * @package mepihindeveloper\components
 */
class Database implements interfaces\DatabaseInterface {
	
	/**
	 * @var PDOStatement[] Список подготовленных запросов
	 */
	public array $executeList = [];
	/**
	 * @var bool Статус активности транзакции
	 */
	public bool $isTransaction = false;
	/**
	 * @var PDO|null Соединение с базой данных
	 */
	protected ?PDO $pdo;
	/**
	 * @var PDOStatement Подготовленный запрос
	 */
	protected PDOStatement $pdoStatement;
	
	/**
	 * @inheritDoc
	 */
	public function connect(array $databaseConnectionParams = []): void {
		$dsn = "{$databaseConnectionParams['dbms']}:";
		
		foreach (['host', 'dbname'] as $key) {
			$dsn .= "{$key}={$databaseConnectionParams[$key]};";
		}
		
		$charset = array_key_exists('charset', $databaseConnectionParams) ? strtoupper($databaseConnectionParams['charset']) : 'UTF8';
		$this->pdo = new PDO(
			$dsn,
			$databaseConnectionParams['user'],
			$databaseConnectionParams['password']
		);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		$this->pdo->exec("SET NAMES '{$charset}'");
	}
	
	/**
	 * @inheritDoc
	 */
	public function closeConnection(): void {
		$this->pdo = null;
	}
	
	/**
	 * @inheritDoc
	 */
	public function beginTransaction(): void {
		if ($this->isTransaction) {
			throw new RuntimeException('Ошибка. Невозможно повторно инициализировать транзакцию.');
		}
		
		$this->pdo->beginTransaction();
		$this->isTransaction = true;
	}
	
	/**
	 * @inheritDoc
	 */
	public function commit(): void {
		try {
			if (!empty($this->executeList)) {
				foreach ($this->executeList as $executeQuery) {
					$executeQuery->execute();
				}
			}
			
			$this->pdo->commit();
		} catch (PDOException $exception) {
			$this->pdo->rollBack();
			
			throw new PDOException(500, $exception->getMessage(), $exception->getCode());
		} finally {
			$this->isTransaction = false;
			$this->executeList = [];
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function queryAll(string $query, array $attributes = [], $fetchStyle = PDO::FETCH_ASSOC): array {
		$this->execute($query, $attributes);
		
		return $this->pdoStatement->fetchAll($fetchStyle);
	}
	
	/**
	 * @inheritDoc
	 */
	public function execute(string $query, array $attributes = []): bool {
		$this->beforeQuery($query, $attributes);
		
		return $this->pdoStatement->execute();
	}
	
	/**
	 * Обрабатывает запрос перед выполнением
	 *
	 * @param string $query Запрос
	 * @param array $attributes Атрибуты запроса
	 *
	 * @return void
	 * @throws PDOException
	 */
	protected function beforeQuery(string $query, array $attributes = []): void {
		try {
			$this->pdoStatement = $this->pdo->prepare($query);
			
			if (!empty($attributes)) {
				$preparedAttributes = $this->bindAttributes($attributes);
				
				foreach ($preparedAttributes as $preparedAttribute) {
					$attributesPart = explode("\x7F", $preparedAttribute);
					$this->pdoStatement->bindParam($attributesPart[0], $attributesPart[1]);
				}
			}
			
			if ($this->isTransaction) {
				$this->executeList[] = $this->pdoStatement;
			}
		} catch (PDOException $exception) {
			throw new PDOException(500, $exception->getMessage(), $exception->getCode());
		}
	}
	
	/**
	 * Назначает атрибуты
	 *
	 * @param array $attributes Атрибуты
	 *
	 * @return array
	 */
	protected function bindAttributes(array $attributes): array {
		$preparedAttributes = [];
		
		foreach ($attributes as $key => $value) {
			$preparedAttributes[] = ':' . $key . "\x7F" . $value;
		}
		
		return $preparedAttributes;
	}
	
	/**
	 * @inheritDoc
	 */
	public function queryRow(string $query, array $attributes = [], $fetchStyle = PDO::FETCH_ASSOC) {
		$this->execute($query, $attributes);
		
		return $this->pdoStatement->fetch($fetchStyle);
	}
	
	/**
	 * @inheritDoc
	 */
	public function queryColumn(string $query, array $attributes = []): array {
		$this->execute($query, $attributes);
		$queryCells = $this->pdoStatement->fetchAll(PDO::FETCH_NUM);
		$cells = [];
		
		foreach ($queryCells as $queryCell) {
			$cells[] = $queryCell[0];
		}
		
		return $cells;
	}
	
	/**
	 * @inheritDoc
	 */
	public function queryOne(string $query, array $attributes = []) {
		$this->execute($query, $attributes);
		
		return $this->pdoStatement->fetchColumn();
	}
	
	/**
	 * @inheritDoc
	 */
	public function getLastInsertId(): string {
		return $this->pdo->lastInsertId();
	}
}