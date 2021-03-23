<?php

declare(strict_types = 1);

namespace mepihindeveloper\components\interfaces;

use PDO;
use PDOException;

/**
 * Интерфейс DatabaseInterface
 *
 * Декларирует методы обязательные для реализации компонента Database
 *
 * @package mepihindeveloper\components\interfaces
 */
interface DatabaseInterface {
	
	/**
	 * Создает подключение к базе данных
	 *
	 * @param array $databaseConnectionParams Параметры соединения
	 *
	 * @return void
	 */
	public function connect(array $databaseConnectionParams = []): void;
	
	/**
	 * Закрывает подключение к базе данных
	 *
	 * @return void
	 */
	public function closeConnection(): void;
	
	/**
	 * Начинает транзакцию
	 *
	 * @return void
	 */
	public function beginTransaction(): void;
	
	/**
	 * Выполняет транзакцию
	 *
	 * @throws PDOException
	 */
	public function commit(): void;
	
	/**
	 * Выполняет запрос
	 *
	 * @param string $query Запрос
	 * @param array $attributes Атрибуты
	 *
	 * @return bool
	 * @throws PDOException
	 */
	public function execute(string $query, array $attributes = []): bool;
	
	/**
	 * Возвращает массив, содержащий все строки результирующего набора
	 *
	 * @see https://www.php.net/manual/ru/pdostatement.fetchall.php PDOStatement::fetchAll
	 *
	 * @param string $query Запрос
	 * @param array $attributes Атрибуты
	 * @param int $fetchStyle Определяет содержимое возвращаемого массива
	 *
	 * @return array
	 * @throws PDOException
	 */
	public function queryAll(string $query, array $attributes = [], $fetchStyle = PDO::FETCH_ASSOC): array;
	
	/**
	 * Возвращает строку результирующего набора
	 *
	 * @see https://www.php.net/manual/ru/pdostatement.fetch.php PDOStatement::fetch
	 *
	 * @param string $query Запрос
	 * @param array $attributes Атрибуты
	 * @param int $fetchStyle Определяет содержимое возвращаемого массива
	 *
	 * @return mixed
	 * @throws PDOException
	 */
	public function queryRow(string $query, array $attributes = [], $fetchStyle = PDO::FETCH_ASSOC);
	
	/**
	 * Возвращает колонку результирующего набора
	 *
	 * @see https://www.php.net/manual/ru/pdostatement.fetchcolumn.php PDOStatement::fetchColumn
	 *
	 * @param string $query Запрос
	 * @param array $attributes Атрибуты
	 *
	 * @return array
	 * @throws PDOException
	 */
	public function queryColumn(string $query, array $attributes = []): array;
	
	/**
	 * Возвращает единственную запись результирующего набора
	 *
	 * @param string $query Запрос
	 * @param array $attributes Атрибуты
	 *
	 * @return mixed
	 * @throws PDOException
	 */
	public function queryOne(string $query, array $attributes = []);
	
	/**
	 * Возвращает ID последней вставленной строки или значение последовательности
	 *
	 * @see https://www.php.net/manual/ru/pdo.lastinsertid.php PDO::lastInsertId
	 *
	 * @return string
	 */
	public function getLastInsertId(): string;
}