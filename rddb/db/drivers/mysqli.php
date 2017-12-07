<?php
defined('DS') or die('Direct access prohibited');
defined('EXT') or die('Direct access prohibited');

class mysqli_rddb_driver extends rddb_driver {

	private $db;

	protected function _connect($persistent = false) {
		$hostname = $this->hostname;
		if ($persistent == true) {
			$hostname = 'p:'.$hostname;
		}

		$this->db = new mysqli($hostname, $this->username, $this->password, 
				$this->database);

		if ($this->db->connect_error) {
			return false;
		}

		return $this->db;
	}

	protected function _select_db() {
		return true;
	}

	protected function _set_charset($char_set, $collat) {
		/*$res = $this->db->set_charset($char_set);
		$res2 = $this->db->query("SET NAMES ".$this->escape($char_set)." COLLATE ".$this->escape($collat));
		$res3 = $this->db->query("SET CHARACTER SET ".$this->escape($char_set));
		return $res&&$res2&&$res3 ? true : false;*/
		return true;
	}

	protected function _charset() {
		return $this->db->character_set_name();
	}

	protected function _execute($sql) {
		if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $sql)) {
			$sql = preg_replace("/^\s*DELETE\s+FROM\s+(\S+)\s*$/", 
					"DELETE FROM \\1 WHERE 1=1", $sql);
		}

		return $this->db->query($sql);
	}

	protected function _error_message() {
		return $this->db->error;
	}

	protected function _error_code() {
		return $this->db->errno;
	}

	protected function _get($table, $select, $where, $join, $order, $limit, 
			$offset, $group, $having) {
		$sql = $this->_select($table, $select, $where, $join, $order, $limit, 
				$offset, $group, $having);

		return $this->query($sql);
	}

	protected function _insert($table, $data) {
		$query_key = array();
		$query_val = array();

		foreach ($data as $key => $val) {
			$query_key[] = $this->protect_key($key);
			if (!is_array($val)) {
				$query_val[] = is_null($val)?'NULL':$this->escape($val);
			} else {
				$query_val[] = $val[0];
			}
		}

		$sql = "INSERT INTO `".$table."` (".implode(", ", $query_key).") VALUES ("
				.implode(", ", $query_val).")";

		return $this->query($sql);
	}

	protected function _update($table, $data, $where) {
		$set = array();

		foreach ($data as $key => $val) {
			if (is_string($key)) {
				if ($val === NULL) {
					$set[] = $this->protect_key($key)." = NULL";
				} else if (is_array($val)) {
					$set[] = $this->protect_key($key)." = ".$val[0];
				} else {
					$set[] = $this->protect_key($key)." = ".$this->escape($val);
				}
			} else if (is_numeric($key)) {
				$set[] = $val;
			}
		}

		$where = $this->_where($where);

		$sql = "UPDATE `".$table."` SET ".implode(', ', $set);

		if (!empty($where)) {
			$sql .= " WHERE ".$where;
		}

		return $this->query($sql);
	}

	protected function _delete($table, $where = '', $limit = false) {
		$where = $this->_where($where);

		$sql = "DELETE FROM `".$table."`";
		if (!empty($where)) {
			$sql .= " WHERE ".$where;
		}
		if (is_int($limit) && $limit > 0) {
			$sql .= " LIMIT ".$limit;
		}

		return $this->query($sql);
	}
	
	protected function _select($table, $select, $where, $join, $order, $limit, 
			$offset, $group, $having) {
		if (!is_array($select)) {
			$select = array($select);
		}
		$rs = array();
		foreach ($select as $s) {
			if (!empty($s) && strpos($s, '(') === false 
					&& strpos($s, ' ') === false 
					&& strpos($s, ',') === false && $s != '*') {
				$star = false;
				if (strpos($s, '.*') !== false) {
					$star = true;
					$s = str_replace('.*', '', $s);
				}
				$s = $this->_protect_dotted_key($s);
				if ($star) {
					$s .= '.*';
				}
			} else if (stripos($s, ' AS ') !== false 
					&& strpos($s, '(') === false 
					&& strpos($s, ',') === false) {
				$ea = explode(' as ', strtolower($s));
				$ea[0] = $this->_protect_dotted_key($ea[0]);
				$s = $ea[0].' AS '.$this->protect_key($ea[1]);
			} else if (substr_count($s, ' ') == 1) {
				$ea = explode(' ', strtolower($s));
				$ea[0] = $this->_protect_dotted_key($ea[0]);
				$s = $ea[0].' '.$this->protect_key($ea[1]);
			}
			$rs[] = $s;
		}
		$select = implode(', ', $rs);

		if (!is_array($table)) {
			$table = array($table);
		}

		$rt = array();
		foreach ($table as $t) {
			if (strpos($t, '(') === false 
					&& strpos($t, ',') === false 
					&& strpos($t, ' ') === false) {
				$t = $this->_protect_dotted_key($t);
			} else if (stripos($t, ' AS ') !== false 
					&& strpos($t, '(') === false) {
				$ea = explode(' as ', strtolower($t));
				$ea[0] = $this->_protect_dotted_key($ea[0]);
				$t = $ea[0].' AS '.$this->protect_key($ea[1]);
			} else if (substr_count($t, ' ') == 1) {
				$ea = explode(' ', strtolower($t));
				$ea[0] = $this->_protect_dotted_key($ea[0]);
				$t = $ea[0].' '.$this->protect_key($ea[1]);
			}
			$rt[] = $t;
		}
		$table = implode(', ', $rt);

		$sql = "SELECT ".$select." FROM ".$table;

		$join = $this->_join($join);
		if ($join) {
			$sql .= " ".$join;
		}

		$where = $this->_where($where);
		if ($where) {
			$sql .= " WHERE ".$where;
		}

		if (!empty($group)) {
			if (is_array($group)) {
				$rg = array();
				foreach ($group as $g) {
					$rg[] = $this->_protect_dotted_key($g);
				}
				$group = implode(', ', $rg);
			} else if (strpos($group, ',') === false) {
				$group = $this->_protect_dotted_key($group);
			}
			$sql .= " GROUP BY ".$group;
			if (!empty($having)) {
				$having = $this->_where($having);
				$sql .= " HAVING ".$having;
			}
		}

		if (!empty($order)) {
			if (is_array($order)) {
				$ro = array();
				foreach ($order as $k => $v) {
					if (is_int($k)) {
						if (strpos($v, '(') === false) {
							if (strpos($v, ' ') === false) {
								$v = $this->_protect_dotted_key($v);
							} else {
								$oa = explode(' ', $v);
								$oa[0] = $this->_protect_dotted_key($oa[0]);
								$oa[1] = strtoupper($oa[1]);
								if (!in_array($oa[1], array('ASC', 'DESC'))) {
									$oa[1] = 'ASC';
								}
								$v = implode(' ', $oa);
							}
							$ro[] = $v;
						}
					} else {
						$k = $this->_protect_dotted_key($k);
						$v = strtoupper($v);
						if (!in_array($v, array('ASC', 'DESC'))) {
							$v = 'ASC';
						}
						$ro[] = $k.' '.$v;
					}
				}
				$order = implode(', ', $ro);
			} else if (strpos($order, '(') === false) {
				if (strpos($order, ' ') !== false) {
					$ao = explode(' ', $order);
					if (count($ao) == 2) {
						$ao[0] = $this->_protect_dotted_key($ao[0]);
						if (!in_array($ao[1], array('ASC', 'DESC'))) {
							$ao[1] = 'ASC';
						}
						$order = implode(' ', $ao);
					}
				} else {
					$order = $this->_protect_dotted_key($order);
				}
			}
			$sql .= " ORDER BY ".$order;
		}

		if ($limit !== false) {
			$sql .= " LIMIT ";
			if ($offset !== false) {
				$sql .= $offset.",";
			}
			$sql .= $limit;
		}
		
		return $sql;
	}

	protected function _where($where) {
		if (is_string($where) && !empty($where)) {
			return $where;
		} else if (is_array($where)) {
			$r_where = array();
			foreach ($where as $k => $v) {
				if (is_int($k) || strtoupper($k) === 'OR') {
					if (is_array($v)) {
						$v = "(".$this->_where($v).")";
					}
					if (strtoupper($k) === 'OR') {
						$v = 'OR '.$v;
					}
					$r_where[] = $v;
				} else {
					$q = '';
					$k = trim($k);
					if (preg_match('/^\s*"?(OR|NOT|AND)\s+/i', $k, $m)) {
						$op = strtoupper($m[1]);
						$k = trim(preg_replace('/^\s*"?(OR|NOT|AND)\s+/i', '', $k));
					}
					if (strpos($k, '(') === false) {
						if (strpos($k, "'") === false) {
							$k = $this->_protect_dotted_key($k);
						} else {
							$k = $this->_escape($k);
						}
					}
					if (isset($op) && strtoupper($op) != 'AND') {
						if ((strtoupper($op) == 'NOT' && !is_array($v)) 
								|| (strtoupper($op) == 'NOT' 
										&& strpos($v, '%') === false) 
								|| strtoupper($op) != 'NOT') {
							$q .= $op.' ';
							$op = false;
						}
					}
					$q .= $k;
					if (is_array($v)) {
						reset($v);
						if (count($v) == 2 && strtolower(key($v)) === 'between') {
							$v1 = isset($v['between']) ? $v['between'] : $v['BETWEEN'];
							$v2 = $v[0];
							$q .= " BETWEEN ";
							if (strpos($v1, '(') === false 
									&& strpos($v1, '`') === false) {
								$v1 = $this->escape($v1);
							}
							$q .= $v1;
							$q .= " AND ";
							if (strpos($v2, '(') === false 
									&& strpos($v2, '`') === false) {
								$v2 = $this->escape($v2);
							}
							$q .= $v2;
						} else {
							$tv = array();
							foreach ($v as $val) {
								$tv[] = $this->escape($val);
							}
							$q .= (isset($op) && strtoupper($op) == 'NOT' ? 
									' NOT' : '')." IN ("
									.(!empty($tv)?implode(', ', $tv):'NULL').")";
							$op = false;
						}
					} else {
						if (is_null($v)) {
							$q .= ' IS NULL';
						} else if ($v instanceof rddb_string_value) {
							$q .= " = ".$this->escape($v->value);
						} else if (preg_match('/^\s*"?(>|<|=|>=|<=|<>|!=|<=>|LIKE|IN|'
								. 'IS|REGEXP|NOT LIKE|IS NOT|NOT IN|IS NOT NULL)\s+/i', 
								$v, $m)) {
							$opq = strtoupper($m[1]);
							$v = trim(preg_replace('/^\s*"?(>|<|=|>=|<=|<>|!=|<=>|LIKE'
									. '|IN|IS|REGEXP|NOT LIKE|IS NOT|NOT IN|IS NOT NULL)'
									. '\s+/i', '', $v));
							if (strpos($v, '%') !== false 
									|| strpos($v, '(') === false) {
								$v = $this->escape($v);
							}
							if ($opq === 'IS NOT NULL') {
								$q .= " ".$opq;
							} else {
								$q .= " ".$opq." ".$v;
							}
						} else if (preg_match('/^`[a-zA-Z_][a-zA-Z0-9_]+`$/i', 
								$v, $m)) {
							$q .= " = ".$v;
						} else {
							if (preg_match('/^%|[^\x5C]%|[^\x5C](\x5C\x5C)+%/', $v)) {
								if (isset($op) && $op == 'NOT') $q .= " NOT";
								$q .= " LIKE ";
							} else {
								$q .= " = ";
							}
							if (strpos($v, '%') !== false 
									|| (strpos($v, '(') === false)) {
								$v = $this->escape($v);
							}
							$q .= $v;
						}
					}
					$r_where[] = $q;
				}
			}
			$w = implode(' AND ', $r_where);
			$w = str_replace('AND OR', 'OR', $w);
			$w = str_replace('AND AND', 'AND', $w);
			return $w;
		}
	}

	protected function _join($join = '') {
		if (is_string($join)) {
			return $join;
		} else if (is_array($join)) {
			$return = array();
			reset($join);
			$key = key($join);
			$val = current($join);
			if (is_array($val)) {
				foreach ($join as $j) {
					$return[] = $this->build_join($j);
				}
			} else {
				$return[] = $this->build_join($join);
			}
			return implode(' ', $return);
		} else {
			return '';
		}
	}

	private function build_join($join = array()) {
		$return = array('JOIN');
		reset($join);
		$table = current($join);
		if (strpos($table, '(') === false) {
			if (stripos($table, ' AS ') !== false) {
				$ea = explode(' as ', strtolower($s));
				$ea[0] = $this->_protect_dotted_key($ea[0]);
				$table = $ea[0].' AS '.$this->protect_key($ea[1]);
			} else if (substr_count($table, ' ') == 1) {
				$ea = explode(' ', strtolower($table));
				$ea[0] = $this->_protect_dotted_key($ea[0]);
				$table = $ea[0].' '.$this->protect_key($ea[1]);
			} else if (strpos($table, ' ') === false) {
				$table = $this->_protect_dotted_key($table);
			}
		}
		$return[] = $table;
		next($join);
		$on1 = key($join);
		$on2 = current($join);
		$next = $on2;
		if (is_int($on1)) {
			if (preg_match("/^USING\([^`](.+)[^`]\)$/i", $on2, $matches)) {
				$next = "USING(".$this->protect_key($matches[1]).")";
			} else {
				$next = "USING(".$this->protect_key($on2).")";
			}
		} else if (is_array($on2)) {
			$w = $this->_where($on2);
			$next = "ON ".$w;
		} else if (is_string($on1)) {
			if (strpos($on1, '(') === false) {
				$on1 = $this->_protect_dotted_key($on1);
			}
			if (strpos($on2, '(') === false) {
				$on2 = $this->_protect_dotted_key($on2);
			}
			$next = "ON ".$on1." = ".$on2;
		}
		$return[] = $next;
		if (next($join) !== false) {
			$type = strtoupper(current($join));
			array_unshift($return, $type);
		}

		return implode(' ', $return);
	}

	protected function _truncate($table) {
		$table = str_replace('#__', $this->table_prefix, $table);
		$sql = "TRUNCATE TABLE `".$table."`";

		return $this->query($sql);
	}

	protected function _escape($str) {
		return $this->db->real_escape_string($str);
	}

	protected function _key_protector() {
		return "`";
	}

	protected function _protect_key($key) {
		if (strpos($key, $this->_key_protector()) === false 
				&& strpos($key, "'") === false) {
			return $this->_key_protector().$key.$this->_key_protector();
		} else {
			return $key;
		}
	}

	protected function _protect_dotted_key($key) {
		if (strpos($key, '.') !== false) {
			$ak = explode('.', $key);
			$ak = array_map(array($this, 'protect_key'), $ak);
			$key = implode('.', $ak);
		} else {
			$key = $this->protect_key($key);
		}
		return $key;
	}

	protected function _close() {
		$this->db->close();
		$this->db = false;
	}

	protected function _write_result($conn, $res, $sql) {
		if ($this->is_insert($sql)) {
			return new mysqli_db_insert_result($conn, $res, $sql);
		}
		return new mysqli_db_write_result($conn, $res, $sql);
	}

	protected function _result($conn, $res, $sql) {
		return new mysqli_db_list_result($conn, $res, $sql);
	}

	protected function _list_table_query() {
		return "SHOW TABLES FROM `".$this->database."`";
	}

	protected function _list_field_query($table) {
		$table = str_replace('#__', $this->table_prefix, $table);
		return "SHOW COLUMNS FROM `".$table."`";
	}

	private function is_insert($sql) {
		$exploded = explode(' ', trim($sql));
		if (strtoupper($exploded[0]) == 'INSERT')
			return true;
		else
			return false;
	}
}

class mysqli_db_result extends db_result {

	public function _free_result() {
		$this->res_id->free();
	}
}

class mysqli_db_insert_result extends mysqli_db_result {

	public function insert_id() {
		return $this->conn_id->insert_id;
	}
	
	public function affected_rows() {
		return $this->conn_id->affected_rows;
	}
}

class mysqli_db_write_result extends mysqli_db_result {

	public function affected_rows() {
		return $this->conn_id->affected_rows;
	}
}

class mysqli_db_list_result extends mysqli_db_result {

	public function fetch($type = 'object') {
		if (isset($this->fetched_data[$type]) 
				&& is_array($this->fetched_data[$type])) {
			return $this->fetched_data[$type];
		}

		$object = array();
		$array = array();
		$assoc = array();
		$row = array();
		while ($d = $this->res_id->fetch_assoc()) {
			//var_dump($d);
			$rowd = array();
			$arrayd = array();
			$objectd = new stdClass();
			foreach ($d as $key => $val) {
				$rowd[] = $val;
				$arrayd[$key] = $val;
				$arrayd[] = $val;
				$objectd->{$key} = $val;
			}
			$object[] = $objectd;
			$array[] = $arrayd;
			$assoc[] = $d;
			$row[] = $rowd;
		}
		$this->fetched_data[rddb::FETCH_OBJECT] = $object;
		$this->fetched_data[rddb::FETCH_BOTH] = $array;
		$this->fetched_data[rddb::FETCH_ASSOC] = $assoc;
		$this->fetched_data[rddb::FETCH_NUM] = $row;

		return $this->fetched_data[$type];
	}

	public function row($type = 'object') {
		if (!isset($this->fetched_data[$type]) 
				|| !is_array($this->fetched_data[$type])) {
			$this->fetch($type);
		}

		return $this->fetched_data[$type][0];
	}

	public function num_rows() {
		return $this->res_id->num_rows;
	}

	public function num_fields() {
		return $this->res_id->field_count;
	}

	public function _free_result() {
		$this->fetched_data = array();
		parent::_free_result();
	}
}
