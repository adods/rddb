<?php
defined('DS') or die('Direct access prohibited');
defined('EXT') or die('Direct access prohibited');


class rddb_driver {

	public $username;

	public $password;

	public $hostname;

	public $database;

	public $persistent = false;

	public $table_prefix = '';

	public $driver = 'mysql';

	public $char_set = 'utf8';

	public $dbcollat = 'utf8_general_ci';

	public $auto_connect = true;

	public $port = '';

	protected $conn_id = false;

	protected $res_id = false;

	protected $cache = array();

	public $debug = false;

	public function __construct($params) {
		if (is_array($params)) {
			foreach ($params as $key => $val) {
				$this->$key = $val;
			}
		}
	}

	public function connect() {
		if (is_resource($this->conn_id) || is_object($this->conn_id)) {
			return true;
		}

		$this->conn_id = $this->_connect($this->persistent);

		if (!$this->conn_id) {
			if ($this->debug) {
				echo 'Cannot connect to database';
				exit();
			}
			return false;
		}

		if ($this->database != '') {
			if (!$this->_select_db()) {
				if ($this->debug) {
					echo 'Cannot open database: '.$this->database;
					exit();
				}
				return false;
			} else {
				if (!$this->set_charset($this->char_set, $this->dbcollat)) {
					return false;
				}
			}
			return true;
		} else {
			if ($this->debug) {
				echo 'No database specified';
				exit();
			}
			return false;
		}
	}

	public function set_charset($charset, $collat) {
		if (!$this->_set_charset($charset, $collat)) {
			if ($this->debug) {
				echo 'Cannot set database charset: '.$charset;
				exit();
			}
			return false;
		}
		return true;
	}

	public function charset() {
		return $this->_charset();
	}

	public function dbdriver() {
		return $this->driver;
	}
	
	public function error() {
		return $this->_error_message();
	}
	
	public function ecode() {
		return $this->_error_code();
	}

	public function simple_query($sql) {
		if (!$this->conn_id) {
			$this->connect();
		}
		return $this->_execute($sql);
	}

	public function query($sql) {
		if (!$sql) {
			return false;
		}

		$sql = str_replace('#__', $this->table_prefix, $sql);
		$sql = trim($sql);
		$sql = str_replace(array("\n", "\r", "\r\n"), ' ', $sql);

		if (false === ($this->res_id = $this->simple_query($sql))) {
			if ($this->debug) {
				echo $this->_error_message().'<br />Error Code: '
						.$this->_error_code().'<br />Query: '.$sql;
				exit();
			}
			return false;
		}

		if ($this->is_write_query($sql) === true) {
			return $this->write_result($this->conn_id, $this->res_id, $sql);
		} else {
			return $this->result($this->conn_id, $this->res_id, $sql);
		}
	}

	public function fetch_query($sql, $type = 'object') {
		if ($this->is_write_type($sql)) {
			return false;
		}

		if (!in_array($type, array(rddb::FETCH_OBJECT, rddb::FETCH_BOTH, 
			rddb::FETCH_ASSOC, rddb::FETCH_NUM))) {
			$type = rddb::FETCH_OBJECT;
		}

		$RES = $this->query($sql);
		$return = $RES->fetch($type);
		$RES->free();
		unset($RES);

		return $return;
	}
	
	public function select(
			$table = '', 
			$select = '*', 
			$where = '', 
			$join = '', 
			$order = '', 
			$limit = false, 
			$offset = false, 
			$group = '', 
			$having = '') {
		if (is_array($table) || is_array($select)) {
			if (is_array($table)) {
				$t = @$table['table'];
				extract($table);
				$table = $t;
			} else if (is_array($select)) {
				if (isset($select['where'])) {
					$where = $select['where'];
					unset($select['where']);
				}
				if (isset($select['join'])) {
					$join = $select['join'];
					unset($select['join']);
				}
				if (isset($select['order'])) {
					$order = $select['order'];
					unset($select['order']);
				}
				if (isset($select['limit'])) {
					$limit = $select['limit'];
					unset($select['limit']);
				}
				if (isset($select['offset'])) {
					$offset = $select['offset'];
					unset($select['offset']);
				} else if (isset($select['start'])) {
					$offset = $select['start'];
					unset($select['start']);
				}
				if (isset($select['group'])) {
					$group = $select['group'];
					unset($select['group']);
				}
				if (isset($select['having'])) {
					$having = $select['having'];
					unset($select['having']);
				}
				if (isset($select['select'])) {
					$sel = $select['select'];
					$select = $sel;
				}
				if ((is_array($select) && !isset($select[0])) || !$select) {
					$select = '*';
				}
			} else {
				$select = '*';
			}
		}

		if (empty($select)) {
			$select = '*';
		}

		return $this->_select($table, $select, $where, $join, $order, $limit, 
				$offset, $group, $having);
	}

	public function get(
			$table = '', 
			$select = '*', 
			$where = '', 
			$join = '', 
			$order = '', 
			$limit = false, 
			$offset = false, 
			$group = '', 
			$having = '') {
		if (is_array($table) || is_array($select)) {
			if (is_array($table)) {
				$t = @$table['table'];
				extract($table);
				$table = $t;
			} else if (is_array($select)) {
				if (isset($select['where'])) {
					$where = $select['where'];
					unset($select['where']);
				}
				if (isset($select['join'])) {
					$join = $select['join'];
					unset($select['join']);
				}
				if (isset($select['order'])) {
					$order = $select['order'];
					unset($select['order']);
				}
				if (isset($select['limit'])) {
					$limit = $select['limit'];
					unset($select['limit']);
				}
				if (isset($select['offset'])) {
					$offset = $select['offset'];
					unset($select['offset']);
				} else if (isset($select['start'])) {
					$offset = $select['start'];
					unset($select['start']);
				}
				if (isset($select['group'])) {
					$group = $select['group'];
					unset($select['group']);
				}
				if (isset($select['having'])) {
					$having = $select['having'];
					unset($select['having']);
				}
				if (isset($select['select'])) {
					$sel = $select['select'];
					$select = $sel;
				}
				if ((is_array($select) && !isset($select[0])) || !$select) {
					$select = '*';
				}
			} else {
				$select = '*';
			}
		}

		if (empty($select)) {
			$select = '*';
		}

		return $this->_get($table, $select, $where, $join, $order, $limit, 
				$offset, $group, $having);
	}

	public function fetch_get(
			$table = '', 
			$type = 'object', 
			$select = '*', 
			$where = '', 
			$join = '', 
			$order = '', 
			$limit = false, 
			$offset = false, 
			$group = '', 
			$having = '') {
		if (is_array($table) || is_array($type) || is_array($select)) {
			if (is_array($table)) {
				$t = @$table['table'];
				extract($table);
				$table = $t;
			} else if (is_array($type)) {
				$tbl = $table;
				$typ = @$type['type'];
				extract($type);
				$table = $tbl;
				$type = $typ;
			} else if (is_array($select)) {
				$tbl = $table;
				$slt = @$select['select'];
				extract($select);
				$table = $tbl;
				$select = $slt;
			}
		}

		if (!in_array($type, array(rddb::FETCH_OBJECT, rddb::FETCH_BOTH, 
			rddb::FETCH_ASSOC, rddb::FETCH_NUM))) {
			$type = rddb::FETCH_OBJECT;
		}

		$RES = $this->get($table, $select, $where, $join, $order, $limit, $offset,
				$group, $having);
		$return = $RES->fetch($type);
		$RES->free();

		return $return;
	}

	public function row(
			$table = '', 
			$type = 'object', 
			$select = '*', 
			$where = '', 
			$join = '', 
			$order = '', 
			$offset = false, 
			$group = '', 
			$having = '') {
		if (is_array($table) || is_array($type) || is_array($select)) {
			if (is_array($table)) {
				$t = @$table['table'];
				extract($table);
				$table = $t;
			} else if (is_array($type)) {
				$tbl = $table;
				$typ = @$type['type'];
				extract($type);
				$table = $tbl;
				$type = $typ;
			} else if (is_array($select)) {
				$tbl = $table;
				$slt = @$select['select'];
				extract($select);
				$table = $tbl;
				$select = $slt;
			}
		}

		if (!in_array($type, array(rddb::FETCH_OBJECT, rddb::FETCH_BOTH, 
			rddb::FETCH_ASSOC, rddb::FETCH_NUM))) {
			$type = rddb::FETCH_OBJECT;
		}


		$RES = $this->get($table, $select, $where, $join, $order, 1, $offset, 
				$group, $having);
		if ($RES && $RES->num_rows()) {
			$row = $RES->row($type);
			$RES->free();
			return $row;
		} else {
			return false;
		}
	}

	public function count_row($table = '', $where = '', $join = '', $group = '', 
			$having = '') {
		if (is_array($table) || is_array($where)) {
			if (is_array($table)) {
				if (isset($table['where'])) {
					$where = $table['where'];
				}
				if (isset($table['join'])) {
					$join = $table['join'];
				}
				if (isset($table['group'])) {
					$group = $table['group'];
				}
				if (isset($table['having'])) {
					$having = $table['having'];
				}
				if (isset($table['table'])) {
					$table = $table['table'];
				}
			} else {
				if (isset($where['join'])) {
					$join = $where['join'];
				}
				if (isset($where['group'])) {
					$group = $where['group'];
				}
				if (isset($where['having'])) {
					$having = $where['having'];
				}
				if (isset($where['where'])) {
					$where = $where['where'];
				}
			}
		}
		
		$res = $this->get($table, "COUNT(1) as `total`", $where, $join, false, 
				false, false, $group, $having);

		return ($res ? $res->row()->total : 0);
	}
	
	public function count($table = '', $where = '', $join = '', $group = '', 
			$having = '') {
		return $this->count_row($table, $where, $join, $group, $having);
	}

	public function insert($table = '', array $data = array(), 
			$return_insert_id = false) {
		if (!$table) {
			return FALSE;
		}

		if (!is_array($data) || empty($data)) {
			return FALSE;
		}

		$res = $this->_insert($table, $data);

		if ($return_insert_id) {
			return $res->insert_id();
		}

		return $res;
	}

	public function update($table = '', array $data = array(), $where = '') {
		if (!$table) {
			return FALSE;
		}

		if (!is_array($data) || empty($data)) {
			return FALSE;
		}

		return $this->_update($table, $data, $where);
	}

	public function insert_update($table = '', array $condition = array(), 
			array $data = array()) {
		$row = $this->row($table, array('where' => $condition));

		if ($row) {
			$this->update($table, $data, $condition);
		} else {
			$data = array_merge($condition, $data);
			$this->insert($table, $data);
		}
	}

	public function delete($table = '', $where = '', $limit = false) {
		if (!$table) {
			return FALSE;
		}

		return $this->_delete($table, $where, $limit);
	}

	public function truncate($table = '') {
		if (!$table) {
			return FALSE;
		}

		if (is_array($table)) {
			foreach ($table as $t) {
				$this->truncate($t);
			}
		}

		return $this->_truncate($table);
	}

	public function is_write_query($sql) {
		if (!preg_match('/^\s*"?(SET|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|'
				. 'TRUNCATE|LOAD DATA|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK)\s+/i', 
				$sql)) {
			return false;
		}
		return true;
	}

	public function protect_key($key) {
		if (empty($key)) {
			return false;
		}
		return $this->_protect_key($key);
	}

	public function escape($str) {
		switch (gettype($str)) {
			case 'string':
				if ($str === 'NULL') {
					$str = NULL;
				} else {
					$estr = $this->_escape(trim($str, "'"));
					if (!preg_match("/^'.*'$/", $str)) $str = "'".$estr."'";
				}
				break;
			case 'boolean':
				$str = ($str)?1:0;
				break;
			default:
				$str = ($str === 'NULL' || is_null($str))?NULL:$str;
				break;
		}
		return $str;
	}

	public function close() {
		if (is_resource($this->conn_id) || is_object($this->conn_id)) {
			$this->_close();
		}
		$this->conn_id = false;
	}

	protected function write_result($conn, $res, $sql) {
		$RES = $this->_write_result($conn, $res, $sql);
		return $RES;
	}

	protected function result($conn, $res, $sql) {
		$RES = $this->_result($conn, $res, $sql);
		return $RES;
	}

	public function list_tables() {
		if (isset($this->cache['table_list'])) {
			return $this->cache['table_list'];
		}

		if (false === ($sql = $this->_list_table_query())) {
			if ($this->debug) {
				echo 'Your current database driver does not support list table '
				. 'function';
			}
			return false;
		}

		$return = array();
		$result = $this->query($sql);

		if ($result->num_rows() > 0) {
			foreach ($result->fetch(rddb::FETCH_BOTH) as $row) {
				if (isset($row['TABLE_NAME'])) {
					$return[] = $row['TABLE_NAME'];
				} else {
					$return[] = $row[0];
				}
			}
		}

		$this->cache['table_list'] = $return;
		return $this->cache['table_list'];
	}

	public function table_exists($table) {
		$table = str_replace('#__', $this->table_prefix, $table);
		return (!in_array($table, $this->list_tables()))?false:true;
	}

	public function list_fields($table) {
		$table = str_replace('#__', $this->table_prefix, $table);
		if ($this->cache['field_list'][$table]) {
			return $this->cache['field_list'][$table];
		}

		if (false === ($sql = $this->_list_field_query($table))) {
			if ($this->debug) {
				echo 'Your current database driver does not support list field '
				. 'function';
			}
			return false;
		}

		$return = array();
		$result = $this->query($sql);

		foreach ($result->fetch(rddb::FETCH_BOTH) as $row) {
			if (isset($row['COLUMN_NAME'])) {
				$return[] = $row['COLUMN_NAME'];
			} else {
				$return[] = $row[0];
			}
		}

		$this->cache['field_list'][$table] = $return;
		return $this->cache['field_list'][$table];
	}

	public function field_exists($table, $field) {
		return (!in_array($field, $this->list_fields($table)))? false : true;
	}

	public function __destruct() {
		if (!$this->persistent) {
			$this->close();
		}
		unset($this->conn_id);
		unset($this->res_id);
	}
}

class db_result {

	public $sql;

	protected $conn_id;

	protected $res_id;

	protected $fetched_data = array();

	public function __construct($conn, $res, $sql) {
		$this->conn_id = $conn;
		$this->res_id = $res;
		$this->sql = $sql;
	}

	public function query_string() {
		return $this->sql;
	}

	public function insert_id() {
		return 0;
	}

	public function affected_rows() {
		return 0;
	}

	public function fetch() {
		return array();
	}

	public function row() {
		return true;
	}

	public function num_rows() {
		return 0;
	}

	public function num_fields() {
		return 0;
	}

	public function free() {
		$this->_free_result();
	}

	public function __destruct() {
		unset($this->fetched_data);
		unset($this->res_id);
	}
}

class rddb_string_value {
	public $value;
	
	public function __construct($value) {
		$this->value = $value;
	}
}
