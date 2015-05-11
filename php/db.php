<?php

/**
 * should be overwritten after inclusion with per project details
 */

$mysql_info = array(
	'domain' => 'localhost',
	'user' => 'root',
	'password' => '',
	'db' => 'default',
);

$connection = null; /* should be initialized only when needed in query() */

/**
 * sql query result object
 * easily iterate/re-iterate from a query() call
 */
class QueryResult {
	private $sql_result;
	
	public function __construct($sql_result) {
		$this->sql_result = $sql_result;
	}

	/**
	 * seek to first result if exists and return it
	 */
	public function get_first() {
		if (!$this->sql_result) return null;
		if ($this->sql_result === true || $this->sql_result === false)
			return $this->sql_result;
		mysqli_data_seek($this->sql_result, 0);
		return mysqli_fetch_array($this->sql_result, MYSQLI_ASSOC);
	}

	/**
	 * get last autoincremented insert id
	 */
	public function get_last_insert_id() {
		global $connection;
		return mysqli_insert_id($connection);
	}

	/**
	 * return next result
	 */
	public function get_next() {
		if (!$this->sql_result) return null;
		if ($this->sql_result === true || $this->sql_result === false)
			return $this->sql_result;
		return mysqli_fetch_array($this->sql_result, MYSQLI_ASSOC);
	}

	/**
	 * seek to first result
	 */
	public function reset() {
		if ($this->sql_result) {
			mysqli_data_seek($this->sql_result, 0);
		}
	}
}

/**
 * perform safe sql query using mysqli
 * $query_template is the sql query where ? is given to be substituted
 * with the given parameters which will be sanitized
 * ? must be quoted for strings, and must be be surrounded by whitespace
 * for integers, doubles, booleans, and null values; other values are not supported
 * ?? can be used for unsafe substitution if the above rules are too strict but
 * should not be used if possible; and never used to substitute mysql identifiers
 * returns QueryResults object
 *
 * example: query("select * from '?' where userid = ?", 'users', 1);
 */ 
function query($query_template /* , sqlparam1, sqlparam2, ... */) {
	global $connection;
	global $mysql_info;
	$result = false;;

	if (!$connection) {
		if (!($connection = mysqli_connect($mysql_info['domain'], $mysql_info['user'], $mysql_info['password'], $mysql_info['db']))) {
			// ?
			throw new Exception('database connection failed');
		};
	}

	$args = func_num_args(); $arg_cur = null;
	$safe_query = $query_template[0]; $len = strlen($query_template);
	$query_template .= ' '; /* prevent needing big case on $i+1 */
	for ($i = 1, $argn = 0; $i < $len; $i++) {
		if ($query_template[$i] == '?') {
			if ($argn+1 > $args) {
				throw new Exception('not enough parameters given in query');
			}
			elseif ($query_template[$i+1] == '?') {
				$arg_cur = func_get_arg($argn+1);
				/* ?? partially safe delimiter */
				if (is_string($arg_cur)) {
					$safe_query .= mysqli_real_escape_string($connection, $arg_cur);
					$argn++; $i++;
					continue;
				}
				else {
					/* non string values inserted as raw */
					$safe_query .= $arg_cur;
					$argn++; $i++;
					continue;
				}
			}
			elseif (($query_template[$i+1] == "'" || $query_template[$i+1] == '"')
				&& ($query_template[$i-1] == "'" || $query_template[$i-1] == '"')) {
				$arg_cur = func_get_arg($argn+1);
				/* "'?'" quoted safe delimiter */
				if (!is_string($arg_cur)) {
					throw new Exception('non string variable data type used as string given in query at parameter '. strval($argn+1));
				}
				$safe_query .= mysqli_real_escape_string($connection, $arg_cur) . $query_template[$i+1];
				$argn++; $i++;
				continue;
			}
			elseif ($query_template[$i+1] == '`' && $query_template[$i-1] == '`') {
				$arg_cur = func_get_arg($argn+1);
				/* `?` quoted safe delimiter, removed backticks which aren't removed by mysqli_real_escape_string() */
				if (!is_string($arg_cur)) {
					throw new Exception('non string variable data type used as string given in query at parameter ' . strval($argn+1));
				}
				$safe_query .= mysqli_real_escape_string($connection, str_replace('`', '', $arg_cur)) . $query_template[$i+1];
				$argn++; $i++;
				continue;
			}
			elseif (($query_template[$i+1] == ' ' || $query_template[$i+1] == "\n" || $query_template[$i+1] == "\r" || $query_template[$i+1] == "\t")
				&& ($query_template[$i-1] == ' ' || $query_template[$i-1] == "\n" || $query_template[$i+1] == "\r" || $query_template[$i+1] == "\t")) {
				$arg_cur = func_get_arg($argn+1);
				/* ? safe delimiter seperated by whitespace */
				if (!is_string($arg_cur)) {
					if (is_int($arg_cur) || is_bool($arg_cur)) {
						$safe_query .= intval($arg_cur) . $query_template[$i+1];
						$argn++; $i++;
						continue;
					}
					elseif (is_float($arg_cur)) {
						$safe_query .= $arg_cur . $query_template[$i+1];
						$argn++; $i++;
						continue;
					}
					elseif ($arg_cur == null) {
						$safe_query .= 'NULL' . $query_template[$i+1];
						$argn++; $i++;
						continue;
					}
					else {
						/* no raw values allowed - ?? can be used if necessary */
						throw new Exception('invalid variable data type given in query at parameter ' . strval($argn+1));
					}
				}
				else {
					/**
					 * string variables must be quoted (except in ??) in order to prevent
					 * users accidentally misquoting string data types
					 */
					throw new Exception('string variable not quoted in query parameter ' . strval($argn+1));
				}
			}
			else {
				/* ?? can be used instead if necessary */
				throw new Exception('parameter delimiter used incorrectly (occurance ' . strval($argn+1) . ')');
			}
		}
		else {
			/**
			 * none delimited characters
			 * some none delimited characters are skipped due to look ahead in above case
			 */
			$safe_query .= $query_template[$i];
		}
	}
	$result = mysqli_query($connection, $safe_query);
	return new QueryResult($result);
}

?>
