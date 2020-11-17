<?php

	/**********************************************************************
	*  Author: Justin Vincent (jv@jvmultimedia.com)
	*  Web...: http://twitter.com/justinvincent
	*  Name..: ezSQL_mysql
	*  Desc..: mySQL component (part of ezSQL databse abstraction library)
	*
	*/

	/**********************************************************************
	*  ezSQL error strings - mySQL
	*/
    
    global $ezsql_mysql_str;

	$ezsql_mysql_str = array
	(
		1 => 'Require $dbuser and $dbpassword to connect to a database server',
		2 => 'Error establishing mySQL database connection. Correct user/password? Correct hostname? Database server running?',
		3 => 'Require $dbname to select a database',
		4 => 'mySQL database connection is not active',
		5 => 'Unexpected error while trying to select database'
	);

	/**********************************************************************
	*  ezSQL Database specific class - mySQL
	*/

	//if ( ! function_exists ('mysql_connect') ) die('<b>Fatal Error:</b> ezSQL_mysql requires mySQL Lib to be compiled and or linked in to the PHP engine');
	if ( ! class_exists ('ezSQLcore') ) die('<b>Fatal Error:</b> ezSQL_mysql requires ezSQLcore (ez_sql_core.php) to be included/loaded before it can be used');

	class ezSQL_mysql extends ezSQLcore
	{
		
		var $dbs;
		var $dbh = null;
		var $dbuser = false;
		var $dbpassword = false;
		var $dbname = false;
		var $dbhost = false;
		var $encoding = false;
		var $rows_affected = false;

		/**********************************************************************
		*  Constructor - allow the user to perform a quick connect at the
		*  same time as initialising the ezSQL_mysql class
		*/

		function ezSQL_mysql($dbuser='', $dbpassword='', $dbname='', $dbhost='localhost', $encoding='')
		{
			
			$GLOBALS['dbtype'] = 'mysql';
			$this->dbuser = $dbuser;
			$this->dbpassword = $dbpassword;
			$this->dbname = $dbname;
			$this->dbhost = $dbhost;
			$this->encoding = $encoding;
		}

		/**********************************************************************
		*  Short hand way to connect to mySQL database server
		*  and select a mySQL database at the same time
		*/

		function quick_connect($dbuser='', $dbpassword='', $dbname='', $dbhost='localhost', $encoding='')
		{
			$return_val = false;
			if ( ! $this->connect($dbuser, $dbpassword, $dbhost) ) ;
			else if ( ! $this->select($dbname, $encoding) ) ;
			else $return_val = true;
			return $return_val;
		}

		/**********************************************************************
		*  Try to connect to mySQL database server
		*/

		function connect($dbuser='', $dbpassword='', $dbhost='localhost')
		{
			global $ezsql_mysql_str; $return_val = false;
			
			// Keep track of how long the DB takes to connect
			$this->timer_start('db_connect_time');

			// Must have a user and a password
			if ( ! $dbuser )
			{
				$this->register_error($ezsql_mysql_str[1].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysql_str[1],E_USER_WARNING) : null;
			}
			else
			{
				// Try to establish the server database handle
				if (is_array($dbuser)) {
					foreach ($dbuser as $db) {
						$client_flags = mt_rand(130000, 230000);
						$this->dbs[] = @mysql_connect($db['host'], $db['user'], $db['password'], true, $client_flags);
					}
					$this->change_db();
				} else {
					$this->dbh = @mysql_connect($dbhost, $dbuser, $dbpassword, true, 131074);
					$this->db = $this->dbh;
				}
				if ( ! $this->dbh )
				{
					$this->register_error($ezsql_mysql_str[2].' in '.__FILE__.' on line '.__LINE__);
					$this->show_errors ? trigger_error($ezsql_mysql_str[2],E_USER_WARNING) : null;
				}
				else
				{
					$this->db = $this->dbh;
					$this->dbuser = $dbuser;
					$this->dbpassword = $dbpassword;
					$this->dbhost = $dbhost;
					$return_val = true;
					
					$this->conn_queries = 0;
				}
			}

			return $return_val;
		}
		
		/**********************************************************************
		 * Change the database
		 */
		
		function change_db($index = 0)
		{
			if (count($this->dbs)<=$index) {
				$this->dbh = $this->dbs[0];
				$this->db = $this->dbh;
				return;
			}
			$this->dbh = $this->dbs[$index];
			$this->db = $this->dbh;
		}

		/**********************************************************************
		*  Try to select a mySQL database
		*/

		function select($dbname='', $encoding='')
		{
			global $ezsql_mysql_str; $return_val = false;

			// Must have a database name
			if ( ! $dbname )
			{
				$this->register_error($ezsql_mysql_str[3].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysql_str[3],E_USER_WARNING) : null;
			}

			// Must have an active database connection
			else if ( ! $this->dbh )
			{
				$this->register_error($ezsql_mysql_str[4].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysql_str[4],E_USER_WARNING) : null;
			}

			// Try to connect to the database
			else if ( !@mysql_select_db($dbname,$this->dbh) )
			{
				// Try to get error supplied by mysql if not use our own
				if ( !$str = @mysql_error($this->dbh))
					  $str = $ezsql_mysql_str[5];

				$this->register_error($str.' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($str,E_USER_WARNING) : null;
			}
			else
			{
				$this->dbname = $dbname;
				if ( $encoding == '') $encoding = $this->encoding;
				if($encoding!='')
				{
					$encoding = strtolower(str_replace("-","",$encoding));
					$charsets = array();
					$result = mysql_query("SHOW CHARACTER SET");
					while($row = mysql_fetch_array($result,MYSQL_ASSOC))
					{
						$charsets[] = $row["Charset"];
					}
					if(in_array($encoding,$charsets)){
						mysql_query("SET NAMES '".$encoding."'");						
					}
				}
				
				$return_val = true;
			}

			return $return_val;
		}

		/**********************************************************************
		*  Format a mySQL string correctly for safe mySQL insert
		*  (no mater if magic quotes are on or not)
		*/

		function escape($str)
		{
			// If there is no existing database connection then try to connect
			if ( ! isset($this->dbh) || ! $this->dbh )
			{
				$this->connect($this->dbuser, $this->dbpassword, $this->dbhost);
				$this->select($this->dbname, $this->encoding);
			}

			return mysql_real_escape_string(stripslashes($str));
		}

		/**********************************************************************
		*  Return mySQL specific system date syntax
		*  i.e. Oracle: SYSDATE Mysql: NOW()
		*/

		function sysdate()
		{
			return 'NOW()';
		}

		/**********************************************************************
		*  Perform mySQL query and try to detirmin result value
		*/

		function query($query, $param=array())
		{

			// This keeps the connection alive for very long running scripts
			if ( $this->count(false) >= 500 )
			{
				$this->disconnect();
				$this->quick_connect($this->dbuser,$this->dbpassword,$this->dbname,$this->dbhost,$this->encoding);
			}

			// Initialise return
			$return_val = 0;

			// Flush cached values..
			$this->flush();

			// For reg expressions
			$query = trim($query);

			// Log how the function was called
			$this->func_call = "\$db->query(\"$query\")";

			// Keep track of the last query for debug..
			$this->last_query = $query;

			// Count how many queries there have been
			$this->count(true, true);
			
			// Start timer
			$this->timer_start($this->num_queries);

			// Use core file cache function
			if ( $cache = $this->get_cache($query) )
			{
				// Keep tack of how long all queries have taken
				$this->timer_update_global($this->num_queries);

				// Trace all queries
				if ( $this->use_trace_log )
				{
					$this->trace_log[] = $this->debug(false);
				}
				
				return $cache;
			}

			// If there is no existing database connection then try to connect
			if ( ! isset($this->dbh) || ! $this->dbh )
			{
				$this->connect($this->dbuser, $this->dbpassword, $this->dbhost);
				$this->select($this->dbname,$this->encoding);
				// No existing connection at this point means the server is unreachable
				if ( ! isset($this->dbh) || ! $this->dbh )
					return false;
			}

			// Perform the query via std mysql_query function..
			$this->result = @mysql_query($query,$this->dbh);

			// If there is an error then take note of it..
			if ( $str = @mysql_error($this->dbh) )
			{
				$this->register_error($str);
				if ($this->show_errors) {
					//trigger_error(html_entity_decode($str.'<pre>'.$query.'</pre>'),E_USER_WARNING);
					$e = new Exception;
					echo '<br /><div style="margin:0 15px 10px;"><b>Warning: </b>'.$str.'<pre style="text-align:left;display:block;overflow:auto;overflow-y:hidden;padding:9.5px;font-size:13px;line-height:1.42857143;word-break:break-all;word-wrap:break-word;color:#333;background-color:#f5f5f5;border:1px solid #ccc;border-radius:4px;">'.$query."\n\n".$e->getTraceAsString().'</pre></div>';
				}
				return false;
			}

			// Query was a Data Manipulation Query (insert, delete, update, replace, ...)
			if ( !is_resource($this->result) )
			{
				$is_insert = true;
				$this->rows_affected = @mysql_affected_rows($this->dbh);

				// Take note of the insert_id
				if ( preg_match("/^(insert|replace)\s+/i",$query) )
				{
					$this->insert_id = @mysql_insert_id($this->dbh);
				}

				// Return number fo rows affected
				$return_val = $this->rows_affected;
			}
			// Query was a Data Query Query (select, show, describe, explain, ...)
			else
			{
				$is_insert = false;

				// Take note of column info
				$i=0;
				while ($i < @mysql_num_fields($this->result))
				{
					$this->col_info[$i] = @mysql_fetch_field($this->result);
					$i++;
				}

				// Store Query Results
				$num_rows=0;
				while ( $row = @mysql_fetch_object($this->result) )
				{
					// Store relults as an objects within main array
					$this->last_result[$num_rows] = $row;
					$num_rows++;
				}

				@mysql_free_result($this->result);

				// Log number of rows the query returned
				$this->num_rows = $num_rows;

				// Return number of rows selected
				$return_val = $this->num_rows;
			}

			// disk caching of queries
			$this->store_cache($query,$is_insert);

			// If debug ALL queries
			$this->trace || $this->debug_all ? $this->debug() : null ;

			// Keep tack of how long all queries have taken
			$this->timer_update_global($this->num_queries);

			// Trace all queries
			if ( $this->use_trace_log )
			{
				$this->trace_log[] = $this->debug(false);
			}

			return $return_val;

		}
		
		/**********************************************************************
		*  Close the active mySQL connection
		*/

		function disconnect()
		{
			$this->conn_queries = 0; // Reset connection queries count
			@mysql_close($this->dbh);
		}

	}
