<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Database\Drivers;

use Nette;


/**
 * Supplemental SQLite3 database driver.
 */
class SqliteDriver implements Nette\Database\ISupplementalDriver
{
	use Nette\SmartObject;

	/** @var Nette\Database\Connection */
	private $connection;

	/** @var string  Datetime format */
	private $fmtDateTime;


	public function __construct(Nette\Database\Connection $connection, array $options)
	{
		$this->connection = $connection;
		$this->fmtDateTime = isset($options['formatDateTime']) ? $options['formatDateTime'] : 'U';
	}


	public function convertException(\PDOException $e)
	{
		$code = isset($e->errorInfo[1]) ? $e->errorInfo[1] : null;
		$msg = $e->getMessage();
		if ($code !== 19) {
			return Nette\Database\DriverException::from($e);

		} elseif (strpos($msg, 'must be unique') !== false
			|| strpos($msg, 'is not unique') !== false
			|| strpos($msg, 'UNIQUE constraint failed') !== false
		) {
			return Nette\Database\UniqueConstraintViolationException::from($e);

		} elseif (strpos($msg, 'may not be null') !== false
			|| strpos($msg, 'NOT NULL constraint failed') !== false
		) {
			return Nette\Database\NotNullConstraintViolationException::from($e);

		} elseif (strpos($msg, 'foreign key constraint failed') !== false
			|| strpos($msg, 'FOREIGN KEY constraint failed') !== false
		) {
			return Nette\Database\ForeignKeyConstraintViolationException::from($e);

		} else {
			return Nette\Database\ConstraintViolationException::from($e);
		}
	}


	/********************* SQL ****************d*g**/


	public function delimite($name)
	{
		return '[' . strtr($name, '[]', '  ') . ']';
	}


	public function formatBool($value)
	{
		return $value ? '1' : '0';
	}


	public function formatDateTime(/*\DateTimeInterface*/ $value)
	{
		return $value->format($this->fmtDateTime);
	}


	public function formatDateInterval(\DateInterval $value)
	{
		throw new Nette\NotSupportedException;
	}


	public function formatLike($value, $pos)
	{
		$value = addcslashes(substr($this->connection->quote($value), 1, -1), '%_\\');
		return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'") . " ESCAPE '\\'";
	}


	public function applyLimit(&$sql, $limit, $offset)
	{
		if ($limit < 0 || $offset < 0) {
			throw new Nette\InvalidArgumentException('Negative offset or limit.');

		} elseif ($limit !== null || $offset) {
			$sql .= ' LIMIT ' . ($limit === null ? '-1' : (int) $limit)
				. ($offset ? ' OFFSET ' . (int) $offset : '');
		}
	}


	public function normalizeRow($row)
	{
		foreach ($row as $key => $value) {
			unset($row[$key]);
			if (is_string($key) && ($key[0] === '[' || $key[0] === '"')) {
				$key = substr($key, 1, -1);
			}
			$row[$key] = $value;
		}
		return $row;
	}


	/********************* reflection ****************d*g**/


	public function getTables()
	{
		$tables = [];
		foreach ($this->connection->query("
			SELECT name, type = 'view' as view FROM sqlite_master WHERE type IN ('table', 'view') AND name NOT LIKE 'sqlite_%'
			UNION ALL
			SELECT name, type = 'view' as view FROM sqlite_temp_master WHERE type IN ('table', 'view') AND name NOT LIKE 'sqlite_%'
			ORDER BY name
		") as $row) {
			$tables[] = [
				'name' => $row->name,
				'view' => (bool) $row->view,
			];
		}

		return $tables;
	}


	public function getColumns($table)
	{
		$meta = $this->connection->query("
			SELECT sql FROM sqlite_master WHERE type = 'table' AND name = {$this->connection->quote($table)}
			UNION ALL
			SELECT sql FROM sqlite_temp_master WHERE type = 'table' AND name = {$this->connection->quote($table)}
		")->fetch();

		$columns = [];
		foreach ($this->connection->query("PRAGMA table_info({$this->delimite($table)})") as $row) {
			$column = $row['name'];
			$pattern = "/(\"$column\"|`$column`|\[$column\]|$column)\\s+[^,]+\\s+PRIMARY\\s+KEY\\s+AUTOINCREMENT/Ui";
			$type = explode('(', $row['type']);
			$columns[] = [
				'name' => $column,
				'table' => $table,
				'nativetype' => strtoupper($type[0]),
				'size' => isset($type[1]) ? (int) $type[1] : null,
				'unsigned' => false,
				'nullable' => $row['notnull'] == '0',
				'default' => $row['dflt_value'],
				'autoincrement' => $meta && preg_match($pattern, (string) $meta['sql']),
				'primary' => $row['pk'] > 0,
				'vendor' => (array) $row,
			];
		}
		return $columns;
	}


	public function getIndexes($table)
	{
		$indexes = [];
		foreach ($this->connection->query("PRAGMA index_list({$this->delimite($table)})") as $row) {
			$indexes[$row['name']]['name'] = $row['name'];
			$indexes[$row['name']]['unique'] = (bool) $row['unique'];
			$indexes[$row['name']]['primary'] = false;
		}

		foreach ($indexes as $index => $values) {
			$res = $this->connection->query("PRAGMA index_info({$this->delimite($index)})");
			while ($row = $res->fetch()) {
				$indexes[$index]['columns'][$row['seqno']] = $row['name'];
			}
		}

		$columns = $this->getColumns($table);
		foreach ($indexes as $index => $values) {
			$column = $indexes[$index]['columns'][0];
			foreach ($columns as $info) {
				if ($column == $info['name']) {
					$indexes[$index]['primary'] = (bool) $info['primary'];
					break;
				}
			}
		}
		if (!$indexes) { // @see http://www.sqlite.org/lang_createtable.html#rowid
			foreach ($columns as $column) {
				if ($column['vendor']['pk']) {
					$indexes[] = [
						'name' => 'ROWID',
						'unique' => true,
						'primary' => true,
						'columns' => [$column['name']],
					];
					break;
				}
			}
		}

		return array_values($indexes);
	}


	public function getForeignKeys($table)
	{
		$keys = [];
		foreach ($this->connection->query("PRAGMA foreign_key_list({$this->delimite($table)})") as $row) {
			$keys[$row['id']]['name'] = $row['id']; // foreign key name
			$keys[$row['id']]['local'] = $row['from']; // local columns
			$keys[$row['id']]['table'] = $row['table']; // referenced table
			$keys[$row['id']]['foreign'] = $row['to']; // referenced columns
			$keys[$row['id']]['onDelete'] = $row['on_delete'];
			$keys[$row['id']]['onUpdate'] = $row['on_update'];

			if (!isset($keys[$row['id']]['foreign'][0]) || $keys[$row['id']]['foreign'][0] == null) {
				$keys[$row['id']]['foreign'] = null;
			}
		}
		return array_values($keys);
	}


	public function getColumnTypes(\PDOStatement $statement)
	{
		$types = [];
		$count = $statement->columnCount();
		for ($col = 0; $col < $count; $col++) {
			$meta = $statement->getColumnMeta($col);
			if (isset($meta['sqlite:decl_type'])) {
				if (in_array($meta['sqlite:decl_type'], ['DATE', 'DATETIME'], true)) {
					$types[$meta['name']] = Nette\Database\IStructure::FIELD_UNIX_TIMESTAMP;
				} else {
					$types[$meta['name']] = Nette\Database\Helpers::detectType($meta['sqlite:decl_type']);
				}
			} elseif (isset($meta['native_type'])) {
				$types[$meta['name']] = Nette\Database\Helpers::detectType($meta['native_type']);
			}
		}
		return $types;
	}


	public function isSupported($item)
	{
		return $item === self::SUPPORT_MULTI_INSERT_AS_SELECT || $item === self::SUPPORT_SUBSELECT || $item === self::SUPPORT_MULTI_COLUMN_AS_OR_COND;
	}
}
