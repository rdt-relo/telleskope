<?php

trait MySQLTrait
{
    abstract private static function GetRWConnection(): mysqli;
    abstract private static function GetROConnection(): mysqli;

    protected static function DBGet(string $select, string $hint = "/*qc=on*/", bool $get_result_stmt = false): mysqli_result|array
    {
        $dbrw = self::GetRWConnection();

        $query = mysqli_query($dbrw, $hint . $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBR-11*"));        

        ## The following code blocks should be exactly the same as the block in DBGet and DBROGet ##
        ## Start of duplicate code block ##
        $results = array();
        if (!is_bool($query)) { // Query result can be bool in which case we cannot use mysqli function
            if ($get_result_stmt) {
                return $query;
            }
            while (@$rows = mysqli_fetch_assoc($query)) {
                $results[] = $rows;
            }
        }
        ## End of duplicate code block 1 ##
        return ($results);
    }

    /**
     * This method is same as DBGet except it fetches data from Read Only instance
     * @param string $select
     * @param string $hint
     * @param bool $get_result_stmt
     * @return mysqli_result|array
     */
    protected static function DBROGet(string $select, string $hint = "/*qc=on*/", bool $get_result_stmt = false): mysqli_result|array
    {
        $dbro = self::GetROConnection();
        $query = mysqli_query($dbro, "/*qc=on*/" . $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbro), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBRO-11*"));
        
        ## The following code blocks should be exactly the same as the block in DBGet and DBROGet ##
        ## Start of duplicate code block ##
        $results = array();
        if (!is_bool($query)) { // To get rid of warning PHP Warning: mysqli_fetch_assoc() expects parameter 1 to be mysqli_result, bool given in
            if ($get_result_stmt) {
                return $query;
            }
            while (@$rows = mysqli_fetch_assoc($query)) {
                $results[] = $rows;
            }
        }
        ## End of duplicate code block 1 ##
        return ($results);
    }

    /**
     * Fetch records matching sql statement.
     * NOTE: DO NOT APPLY RAW2CLEAN, htmlspecialchars etc to inputs. Use proper types instead
     * @param string $sql The SQL Statement to execute
     * @param string $types a string with types that follow, 'i' for integer, 's' for string, 'm' for partially marked up HTML string, 'x' consider xml.
     * @param mixed ...$argv arguments for the SQL statement. all html/php tags will be removed for 's' type, partial html tags will be retained for 'm' type, type 'x' will be kept as is
     * @return array of all the fetched records
     */
    protected static function DBGetPS(string $sql, string $types = '', ...$argv)
    {
        $dbrw = self::GetRWConnection();
        $params_str = implode(',', $argv);

        $stmt = mysqli_prepare($dbrw, "/*qc=on*/" . $sql) or (Logger::Log('Fatal Error Preparing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBRP-11*"));
        

        ## The following code blocks should be exactly the same as the block in DBGetPS and DBROGetPS ##
        ## Start of duplicate code block 1##
        $bind_params = array();
        $argc = count($argv);
        if ($argc) {
            // First escape the  HTML code from strings
            $type_array = str_split($types);
            for ($c = 0; $c < $argc; $c++) {
                if ($type_array[$c] === 's') {
                    $argv[$c] = is_null($argv[$c]) ? null : htmlspecialchars($argv[$c]);
                } elseif ($type_array[$c] === 'm') {
                    $type_array[$c] = 's';
                    $argv[$c] = is_null($argv[$c]) ? null : self::cleanMarkup($argv[$c]);
                } elseif ($type_array[$c] === 'x') {
                    $type_array[$c] = 's';
                }
            }
            $types = implode('', $type_array);

            // If there are arguments, bind them
            $bind_params[] = $stmt;
            $bind_params[] = $types;

            for ($i = 0; $i < $argc; $i++)
                $bind_params[] = &$argv[$i];

            ## End of duplicate code block 1 ##       
            call_user_func_array('mysqli_stmt_bind_param', $bind_params) or (Logger::Log('Fatal Error Binding SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and die(header(HTTP_INTERNAL_SERVER_ERROR). " *Internal Error: DBRP-11b*"));
        }

        mysqli_stmt_execute($stmt) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBRP-11c*"));      

        $query = mysqli_stmt_get_result($stmt) or (Logger::Log("Fatal Error getting: {$sql}, parameters = {$params_str} | server error " . mysqli_error($dbrw)) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBRP-11d*"));

        ## Start of duplicate code block 2 ##
        $results = array();
        if (!is_bool($query)) { // Query result can be bool in which case we cannot use mysqli function
            while (@$rows = mysqli_fetch_assoc($query)) {
                $results[] = $rows;
            }
        }
        // Close statement
        mysqli_stmt_close($stmt);
        ## End of duplicate code block 2 ##

        return ($results);
    }

    /**
     * Function to clean markup type in prepared statement
     * @param $input string that needs to be cleaned
     * @return string|string[]|null
     */
    private static function cleanMarkup(string $input)
    {

        $allowed_tags = /** @lang text */
            "<p><strong><img><ol><ul><li><a><hr><br><em><s><blockquote><span><u><i><del><figure><figcaption><table><tbody><tr><td><th>";
        return strip_tags($input, $allowed_tags);
    }



    /**
     * This method functions the same way as DBGetPS except the query is run on the Read Only Instance
     * @param string $sql
     * @param string $types
     * @param mixed ...$argv
     * @return array
     */
    protected static function DBROGetPS(string $sql, string $types = '', ...$argv)
    {
        $dbro = self::GetROConnection();
        $params_str = implode(',', $argv);

        $stmt = mysqli_prepare($dbro, "/*qc=on*/" . $sql) or (Logger::Log('Fatal Error Preparing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbro), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBROP-11*"));
       

        ## The following code blocksshould be exactly the same as the block in DBGetPS ##
        ## Start of duplicate code block 1 ##
        $bind_params = array();
        $argc = count($argv);
        if ($argc) {
            // First escape the  HTML code from strings
            $type_array = str_split($types);
            for ($c = 0; $c < $argc; $c++) {
                if ($type_array[$c] === 's') {
                    $argv[$c] = is_null($argv[$c]) ? null : htmlspecialchars($argv[$c]);
                } elseif ($type_array[$c] === 'm') {
                    $type_array[$c] = 's';
                    $argv[$c] = is_null($argv[$c]) ? null : self::cleanMarkup($argv[$c]);
                } elseif ($type_array[$c] === 'x') {
                    $type_array[$c] = 's';
                }
            }
            $types = implode('', $type_array);

            // If there are arguments, bind them
            $bind_params[] = $stmt;
            $bind_params[] = $types;

            for ($i = 0; $i < $argc; $i++)
                $bind_params[] = &$argv[$i];

            ## End of duplicate code block 1 ##            
            call_user_func_array('mysqli_stmt_bind_param', $bind_params) or (Logger::Log('Fatal Error Binding SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbro), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and die(header(HTTP_INTERNAL_SERVER_ERROR). " *Internal Error: DBROP-11b*"));
        }

        mysqli_stmt_execute($stmt) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbro), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBROP-11c*"));
        
        $query = mysqli_stmt_get_result($stmt) or (Logger::Log("Fatal Error getting: {$sql}, parameters = {$params_str} | server error " . mysqli_error($dbro)) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBROP-11d*"));

        ## Start of duplicate code block 2 ##
        $results = array();
        if (!is_bool($query)) { // Query result can be bool in which case we cannot use mysqli function
            while (@$rows = mysqli_fetch_assoc($query)) {
                $results[] = $rows;
            }
        }
        // Close statement
        mysqli_stmt_close($stmt);
        ## End of duplicate code block 2 ##

        return ($results);
    }//end

    protected static function DBInsert(string $insert)
    {
        $dbrw = self::GetRWConnection();
        $query = mysqli_query($dbrw, $insert) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $insert]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBI-11*"));
        
        if ($query) {
            return mysqli_insert_id($dbrw);
        } else {
            Logger::Log(mysqli_error($dbrw));
            return 0;
        }
    }

    /**
     * Insert a record into the database
     * NOTE: DO NOT APPLY RAW2CLEAN, htmlspecialchars etc to inputs. Use proper types instead
     * @param string $sql The SQL Statement to insert
     * @param string $types a string with types that follow, 'i' for integer, 's' for string, 'm' for partially marked up HTML string, 'x' consider xml.
     * @param mixed ...$argv arguments for the SQL statement. all html/php tags will be removed for 's' type, partial html tags will be retained for 'm' type, type 'x' will be kept as is
     * @return int|string
     */
    protected static function DBInsertPS(string $sql, string $types = '', ...$argv)
    {
        $dbrw = self::GetRWConnection();
        $params_str = implode(',', $argv);

        $stmt = mysqli_prepare($dbrw, $sql) or (Logger::Log('Fatal Error Preparing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBIP-11*"));
       
        $bind_params = array();
        $argc = count($argv);
        if ($argc) {
            // First escape the  HTML code from strings
            $type_array = str_split($types);
            for ($c = 0; $c < $argc; $c++) {
                if ($type_array[$c] === 's') {
                    $argv[$c] = is_null($argv[$c]) ? null : htmlspecialchars($argv[$c]);
                } elseif ($type_array[$c] === 'm') {
                    $type_array[$c] = 's';
                    $argv[$c] = is_null($argv[$c]) ? null : self::cleanMarkup($argv[$c]);
                } elseif ($type_array[$c] === 'x') {
                    $type_array[$c] = 's';
                }
            }
            $types = implode('', $type_array);

            // If there are arguments, bind them
            $bind_params[] = $stmt;
            $bind_params[] = $types;

            for ($i = 0; $i < $argc; $i++)
                $bind_params[] = &$argv[$i];

            call_user_func_array('mysqli_stmt_bind_param', $bind_params) or (Logger::Log('Fatal Error Binding SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and die(header(HTTP_INTERNAL_SERVER_ERROR). " *Internal Error: DBIP-11b*"));
        }

        mysqli_stmt_execute($stmt) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBIP-11c*"));       

        $last_inserted_id = mysqli_insert_id($dbrw);
        // Close statement
        mysqli_stmt_close($stmt);

        return $last_inserted_id; // Returns 0 if there was no auto increment value or error

    }//end

    /**
     * This method calls the stored procedure with arguments as specfied in the query.
     * @param string $stmt
     * @return array array containing insert_id, impacted_id, impacted_rows (0 means no rows impacted), error_code (0 is success or error code)
     */
    protected static function DBCall(string $stmt): array
    {
        $retVal = array('insert_id' => 0, 'impacted_id' => 0, 'impacted_rows' => 0, 'error_code' => 0);
        $dbrw = self::GetRWConnection();
        $query = mysqli_query($dbrw, $stmt);
        if ($query && $query->num_rows === 1) {
            $result = mysqli_fetch_assoc($query);
            $retVal['insert_id'] = (int)$result['insert_id'];
            $retVal['impacted_id'] = (int)$result['impacted_id'];
            $retVal['impacted_rows'] = (int)$result['impacted_rows'];
            while (mysqli_next_result($dbrw)) {
                ;
            }
        } else {
            Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $stmt]);           
            $retVal['error_code'] = 1;
        }
        return $retVal;
    }//end

    /**
     * Utility function to execute update commands. The key difference between this function and DBUpdate is that
     * this function will *not* die on error. DBUpdate dies on error.
     * @param string $query
     * @return int - Returns 1 on success and 0 on failure
     */
    protected static function DBMutate(string $query)
    {
        $dbrw = self::GetRWConnection();
        if (mysqli_query($dbrw, $query)) {
            return 1;
        } else {           
            Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $query]);     
            return 0;
        }
    }

    /**
     * Utility function to execute update commands as prepared statement.
     * The key difference between this function and DBUpdatePS is that
     * this function will *not* die on error. DBUpdatePS dies on error.
     * NOTE: DO NOT APPLY RAW2CLEAN, htmlspecialchars etc to inputs. Use proper types instead
     * @param string $sql The SQL Statement to execute
     * @param string $types a string with types that follow, 'i' for integer, 's' for string, 'm' for partially marked up HTML string, 'x' consider xml.
     * @param mixed ...$argv arguments for the SQL statement. all html/php tags will be removed for 's' type, partial html tags will be retained for 'm' type, type 'x' will be kept as is
     * @return int - Returns N number of rows that were impacted
     *
     */
    protected static function DBMutatePS(string $sql, string $types = '', ...$argv)
    {
        $dbrw = self::GetRWConnection();
        $params_str = implode(',', $argv);
        $error = 0;

        $stmt = mysqli_prepare($dbrw, $sql) or (Logger::Log('Fatal Error Preparing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and $error++);        

        if ($error)
            return (0);

        $bind_params = array();
        $argc = count($argv);
        if ($argc) {
            // First escape the  HTML code from strings
            $type_array = str_split($types);
            for ($c = 0; $c < $argc; $c++) {
                if ($type_array[$c] === 's') {
                    $argv[$c] = is_null($argv[$c]) ? null : htmlspecialchars($argv[$c]);
                } elseif ($type_array[$c] === 'm') {
                    $type_array[$c] = 's';
                    $argv[$c] = is_null($argv[$c]) ? null : self::cleanMarkup($argv[$c]);
                } elseif ($type_array[$c] === 'x') {
                    $type_array[$c] = 's';
                }
            }
            $types = implode('', $type_array);

            // If there are arguments, bind them
            $bind_params[] = $stmt;
            $bind_params[] = $types;

            for ($i = 0; $i < $argc; $i++)
                $bind_params[] = &$argv[$i];

            call_user_func_array('mysqli_stmt_bind_param', $bind_params) or (Logger::Log('Fatal Error Binding SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and $error++);
            if ($error) {
                mysqli_stmt_close($stmt);
                return (0);
            }
        }

        mysqli_stmt_execute($stmt) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and $error++);       

        if ($error) {
            mysqli_stmt_close($stmt);
            return (0);
        }
        $affected_rows = mysqli_affected_rows($dbrw);
        // Close statement
        mysqli_stmt_close($stmt);

        return $affected_rows; // Returns 0 if no rows were affected
    }//End

    /**
     * Utility function to execute update commands. The key difference between this function and DBMutate is that
     * this function will die on error. DBMutate does not die on error.
     * @param string $update - Update SQL command to execute
     * @return int - Returns 1 (something updated) or -1 (nothing updated) on success and 0 on failure
     *
     */
    protected static function DBUpdate(string $update)
    {
        $dbrw = self::GetRWConnection();
        $query = mysqli_query($dbrw, $update) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $update]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBU-11*"));       

        if ($query) {
            if (mysqli_affected_rows($dbrw))
                return 1;
            else
                return -1;
        } else {
            return 0;
        }
    }//End

    /**
     * @Depricated: Utility function that differs from DBUpdate only in the return value.
     * If only one record was updated then it returns true else it returns false. This method is marked as deprecated
     * as it should be used only for Atomic updates such as in the Job Class. No other use is premitted.
     * @param string $update
     * @return bool
     */
    protected static function DBAtomicUpdate(string $update) : bool
    {
        $dbrw = self::GetRWConnection();
        mysqli_query($dbrw, $update) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $update]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBU-11*"));
        return mysqli_affected_rows($dbrw) == 1;
    }//End
    /**
     * Utility function to execute update commands. The key difference between this function and DBMutatePS is that
     * this function will die on error. DBMutatePS does not die on error.
     * NOTE: DO NOT APPLY RAW2CLEAN, htmlspecialchars etc to inputs. Use proper types instead
     * @param string $sql The SQL Statement to execute
     * @param string $types a string with types that follow, 'i' for integer, 's' for string, 'm' for partially marked up HTML string, 'x' consider xml.
     * @param mixed ...$argv arguments for the SQL statement. all html/php tags will be removed for 's' type, partial html tags will be retained for 'm' type, type 'x' will be kept as is
     * @return int - Returns no of affected rows on success
     *
     */
    protected static function DBUpdatePS(string $sql, string $types = '', ...$argv)
    {
        $dbrw = self::GetRWConnection();
        $params_str = implode(',', $argv);

        $stmt = mysqli_prepare($dbrw, $sql) or (Logger::Log('Fatal Error Preparing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBUP-11*"));
      
        $bind_params = array();
        $argc = count($argv);
        if ($argc) {
            // First escape the  HTML code from strings
            $type_array = str_split($types);
            for ($c = 0; $c < $argc; $c++) {
                if ($type_array[$c] === 's') {
                    $argv[$c] = is_null($argv[$c]) ? null : htmlspecialchars($argv[$c]);
                } elseif ($type_array[$c] === 'm') {
                    $type_array[$c] = 's';
                    $argv[$c] = is_null($argv[$c]) ? null : self::cleanMarkup($argv[$c]);
                } elseif ($type_array[$c] === 'x') {
                    $type_array[$c] = 's';
                }
            }
            $types = implode('', $type_array);

            // If there are arguments, bind them
            $bind_params[] = $stmt;
            $bind_params[] = $types;

            for ($i = 0; $i < $argc; $i++)
                $bind_params[] = &$argv[$i];

            call_user_func_array('mysqli_stmt_bind_param', $bind_params) or (Logger::Log('Fatal Error Binding SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBUP-11b*"));
            
        }

        mysqli_stmt_execute($stmt) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql, 'sql_params' => $params_str]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: DBU-11c*"));

        $affected_rows = mysqli_affected_rows($dbrw);
        // Close statement
        mysqli_stmt_close($stmt);

        return $affected_rows; // Returns 0 if there was no auto increment value or error
    }

    /**
     * @param string $input input values that will be cleaned
     * @param bool $html pass true if you want to allow HTML to stay as it, (default = false)
     * @param bool $dbescape if false then db level escaping is not done. useful when using Prepared statement or just
     * outputing to screen. Default is true.
     * @return string|string[]|null
     */
    protected static function raw2clean(string $input, $html = false, $dbescape = true)
    {
        $dbrw = self::GetRWConnection();
        $r = $input;
        if ($html) {
            $allowed_tags = /** @lang text */
                "<p><strong><img><ol><ul><li><a><hr><em><s><blockquote><span><u><del>";
            $r = strip_tags($r, $allowed_tags); //Strip all HTML tags except for the allowed ones.
            $r = preg_replace('/[^[:print:]]/', ' ', $r); //remove non printable characters that CKEDITOR sometimes adds
        } else {
            $r = htmlspecialchars($r); //Escape HTML characters like <,>,& etc.
        }
        if ($dbescape)
            $r = mysqli_real_escape_string($dbrw, $r); //Escape characters that are unsafe for MySQL.

        return $r;
    }

    protected static function mysqli_escape($input)
    {
        $dbrw = self::GetRWConnection();
        return mysqli_real_escape_string($dbrw, $input); //Escape characters that are unsafe for MySQL.
    }

    protected static function CleanInputs($input): string
    {
        return self::mysqli_escape($input);
    }

    /**
     * Utility function for operations on Temporary table
     * (sql can run on other tables also but use this method should be used only for temporary tables)
     * requires CREATE TEMPORARY TABLES grant,
     * all operations are Read Only instance
     * @param string $stmt
     * @return bool|mysqli_result
     * @throws Exception
     */
    protected static function DBTemporaryTableOperation(string $stmt): mysqli_result|bool
    {
        $dbro = self::GetROConnection();
        $query = mysqli_query($dbro, $stmt);
        if (!$query) {
            throw new Exception(mysqli_error($dbro));
        }
        return $query;
    }

    /**
     * This method returns the value for the matching field
     * @param string $what field to match
     * @return mixed|null the value that matches the field, or '' on error (no match found)
     */
    public function val(string $what, bool $call_getter = true) {
        if ($call_getter) {
            $getter_fn = '__getval_' . $what;
            if (method_exists($this, $getter_fn)) {
                return $this->{$getter_fn}($what);
            }
        }

        if (isset($this->fields[$what])) {
            return $this->fields[$what];
        } else {
            return null;
        }
    }

    /**
     * Returns the value of the field as PHP Array type. String is expexted to be in a valid JSON type
     * @param string|null $what
     * @return mixed null if the conversion cannot be done array
     */
    public function val_json2Array (?string $what) : mixed
    {
        // Convert backslashes to double backslashes
        // return $what
        //  ? json_decode(str_replace('\\', '\\\\', ($this->val($what) ?? '')), true)
        //  : null;
        return $what ? json_decode($this->val($what) ?? '', true) : null;
    }

    /**
     * This method returns true if a value exists for the matching field
     * @param string $what - the field to look for
     * @return bool
     */
    public function has(string $what) {
        if (isset($this->fields[$what]) && !empty($this->fields[$what])) {
            return true;
        } else {
            return false;
        }
    }
}
