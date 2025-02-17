<?php

namespace Gazelle\DB;

//-----------------------------------------------------------------------------------
/////////////////////////////////////////////////////////////////////////////////////
/*//-- MySQL wrapper class ----------------------------------------------------------

This class provides an interface to mysqli. You should always use this class instead
of the mysql/mysqli functions, because this class provides debugging features and a
bunch of other cool stuff.

Everything returned by this class is automatically escaped for output. This can be
turned off by setting $Escape to false in next_record or to_array.

//--------- Basic usage -------------------------------------------------------------

* Making a query

$DB->prepare_query("
    SELECT *
    FROM table...");

    Is functionally equivalent to using mysqli_query("SELECT * FROM table...")
    Stores the result set in $this->QueryID
    Returns the result set, so you can save it for later (see set_query_id())

* Getting data from a query

$array = $DB->next_record();
    Is functionally equivalent to using mysqli_fetch_array($ResultSet)
    You do not need to specify a result set - it uses $this-QueryID

//--------- Advanced usage ---------------------------------------------------------

* The conventional way of retrieving a row from a result set is as follows:

[$All, $Columns, $That, $You, $Select[ = $DB->next_record();
-----

* This is how you loop over the result set:

while ([$All, $Columns, $That, $You, $Select] = $DB->next_record()) {
    echo "Do stuff with $All of the ".$Columns.$That.$You.$Select;
}
-----

* There are also a couple more mysqli functions that have been wrapped. They are:

record_count()
    Wrapper to mysqli_num_rows()

affected_rows()
    Wrapper to mysqli_affected_rows()

inserted_id()
    Wrapper to mysqli_insert_id()

close
    Wrapper to mysqli_close()
-----

* And, of course, a few handy custom functions.

to_array($Key = false)
    Transforms an entire result set into an array (useful in situations where you
    can't order the rows properly in the query).

    If $Key is set, the function uses $Key as the index (good for looking up a
    field). Otherwise, it uses an iterator.

    For an example of this function in action, check out forum.php.

collect($Key)
    Loops over the result set, creating an array from one of the fields ($Key).
    For an example, see forum.php.

set_query_id($ResultSet)
    This class can only hold one result set at a time. Using set_query_id allows
    you to set the result set that the class is using to the result set in
    $ResultSet. This result set should have been obtained earlier by using
    $DB->prepared_query().

    Example:

    $FoodRS = $DB->prepared_query("
            SELECT *
            FROM food");
    $DB->prepared_query("
        SELECT *
        FROM drink");
    $Drinks = $DB->next_record();
    $DB->set_query_id($FoodRS);
    $Food = $DB->next_record();

    Of course, this example is contrived, but you get the point.

-------------------------------------------------------------------------------------
*///---------------------------------------------------------------------------------

class Mysql_Exception extends \Exception {}
class Mysql_DuplicateKeyException extends Mysql_Exception {}

class Mysql {
    public \mysqli|false $LinkID = false;
    protected \mysqli_result|false|null $QueryID = false;
    protected array|null $Record = [];
    protected int $Row;
    protected int $Errno = 0;
    protected string $Error = '';
    protected bool $queryLog = true;

    protected string $PreparedQuery;
    protected \mysqli_stmt|false $Statement;

    public array $Queries = [];
    public float $Time = 0.0;

    public function __construct(
        protected readonly string $Database,
        protected readonly string $User,
        protected readonly string $Pass,
        protected readonly string $Server,
        protected readonly int $Port,
        protected readonly string $Socket,
    ) {}

    public function disableQueryLog(): void {
        $this->queryLog = false;
    }

    public function enableQueryLog(): void {
        $this->queryLog = true;
    }

    private function halt(string $Msg): void {
        if ($this->Errno == 1062) {
            throw new Mysql_DuplicateKeyException;
        }
        global $Debug;
        $Debug->saveCase("MySQL: error({$this->Errno}) {$this->Error} query=[$this->PreparedQuery]");
        throw new Mysql_Exception("$Msg  -- {$this->Error}");
    }

    public function connect(): void {
        if (!$this->LinkID) {
            $this->LinkID = mysqli_connect($this->Server, $this->User, $this->Pass, $this->Database, $this->Port, $this->Socket);
            if (!$this->LinkID) {
                $this->Errno = mysqli_connect_errno();
                $this->Error = mysqli_connect_error();
                $this->halt('Connection failed (host:'.$this->Server.':'.$this->Port.')');
            }
        }
    }

    private function setup_query(): void {
        /*
         * If there was a previous query, we store the warnings. We cannot do
         * this immediately after mysqli_query because mysqli_insert_id will
         * break otherwise due to mysqli_get_warnings sending a SHOW WARNINGS;
         * query. When sending a query, however, we're sure that we won't call
         * mysqli_insert_id (or any similar function, for that matter) later on,
         * so we can safely get the warnings without breaking things.
         * Note that this means that we have to call $this->warnings manually
         * for the last query!
         */
        if ($this->QueryID) {
            $this->warnings();
        }

        $this->connect();
    }

    /**
     * Prepares an SQL statement for execution with data.
     *
     * Normally, you'll most likely just want to be using
     * Mysql::prepared_query to call both Mysql::prepare()
     * and Mysql::execute() for one-off queries, you can use
     * this separately in the case where you plan to be running
     * this query repeatedly while just changing the bound
     * parameters (such as if doing a bulk update or the like).
     */
    public function prepare(string $Query): \mysqli_stmt|false {
        $this->setup_query();
        $Query = trim($Query);
        $this->PreparedQuery = $Query;
        $this->Statement = $this->LinkID->prepare($Query);
        if ($this->Statement === false) {
            $this->Errno = $this->LinkID->errno;
            $this->Error = $this->LinkID->error;
            $this->Queries[] = ["$Query /* ERROR: {$this->Error} */", 0, null];
            $this->halt(sprintf("Invalid Query: %s(%d) [%s]", $this->Error, $this->Errno, $Query));
        }
        return $this->Statement;
    }

    /**
     * Bind variables to our last prepared query and execute it.
     *
     * Variables that are passed into the function will have their
     * type automatically set for how to bind it to the query (either
     * integer (i), double (d), or string (s)).
     *
     * @param  array<mixed> $Parameters,... variables for the query
     * @return \mysqli_result|bool Returns a mysqli_result object
     *                            for successful SELECT queries,
     *                            or TRUE for other successful DML queries
     *                            or FALSE on failure.
     */
    public function execute(...$Parameters) {
        /** @var \mysqli_stmt $Statement */
        $Statement = &$this->Statement;

        if (count($Parameters) > 0) {
            $Binders = "";
            foreach ($Parameters as $Parameter) {
                if (is_integer($Parameter)) {
                    $Binders .= "i";
                }
                elseif (is_double($Parameter)) {
                    $Binders .= "d";
                }
                else {
                    $Binders .= "s";
                }
            }
            $Statement->bind_param($Binders, ...$Parameters);
        }

        $Closure = function() use ($Statement) {
            try {
                $Statement->execute();
                return $Statement->get_result();
            } catch (\mysqli_sql_exception $e) {
                if (mysqli_error($this->LinkID) == 1062) {
                    throw new Mysql_DuplicateKeyException;
                }
            }
        };

        $Query = $this->PreparedQuery . ' -- ' . json_encode($Parameters);
        return $this->attempt_query($Query, $Closure);
    }

    /**
     * Prepare and execute a prepared query returning the result set.
     *
     * Utility function that wraps Mysql::prepare() and Mysql::execute()
     * as most times, the query is going to be one-off and this will save
     * on keystrokes. If you do plan to be executing a prepared query
     * multiple times with different bound parameters, you'll want to call
     * the two functions separately instead of this function.
     *
     * @param mixed ...$Parameters
     * @return bool|\mysqli_result
     */
    public function prepared_query(string $Query, ...$Parameters) {
        $this->prepare($Query);
        return $this->execute(...$Parameters);
    }

    private function attempt_query(string $Query, Callable $Closure): \mysqli_result|false {
        global $Debug;
        $QueryStartTime = microtime(true);
        // In the event of a MySQL deadlock, we sleep allowing MySQL time to unlock, then attempt again for a maximum of 5 tries
        for ($i = 1; $i < 6; $i++) {
            $this->QueryID = $Closure();
            if (!in_array(mysqli_errno($this->LinkID), [1213, 1205])) {
                break;
            }
            $Debug->analysis('Non-Fatal Deadlock:', $Query, 3600 * 24);
            trigger_error("Database deadlock, attempt $i");

            sleep($i * rand(2, 5)); // Wait longer as attempts increase
        }
        $QueryEndTime = microtime(true);
        // Kills admin pages, and prevents Debug->analysis when the whole set exceeds 1 MB
        if (($Len = strlen($Query))>16384) {
            $Query = substr($Query, 0, 16384).'... '.($Len-16384).' bytes trimmed';
        }
        if ($this->queryLog) {
            $this->Queries[] = [$Query, ($QueryEndTime - $QueryStartTime) * 1000, null];
        }
        $this->Time += ($QueryEndTime - $QueryStartTime) * 1000;

        // Update/Insert/etc statements for prepared queries don't return a QueryID,
        // but mysqli_errno is also going to be 0 for no error
        $this->Errno = mysqli_errno($this->LinkID);
        if (!$this->QueryID && $this->Errno !== 0) {
            $this->Error = mysqli_error($this->LinkID);
            $this->halt("Invalid Query: $Query");
        }

        $this->Row = 0;
        return $this->QueryID;
    }

    public function inserted_id(): ?int {
        if ($this->LinkID) {
            return mysqli_insert_id($this->LinkID);
        }
        return null;
    }

    public function next_row(int $type = MYSQLI_NUM): ?array {
        return $this->LinkID ? mysqli_fetch_array($this->QueryID, $type) : null;
    }

    public function next_record(int $Type = MYSQLI_BOTH, mixed $Escape = true, bool $Reverse = false): ?array {
        // $Escape can be true, false, or an array of keys to not escape
        // If $Reverse is true, then $Escape is an array of keys to escape
        if ($this->LinkID) {
            $this->Record = mysqli_fetch_array($this->QueryID, $Type);
            $this->Row++;
            if (!is_array($this->Record)) {
                $this->QueryID = false;
            } elseif ($Escape !== false) {
                $this->Record = $this->display_array($this->Record, $Escape, $Reverse);
            }
            return $this->Record;
        }
        return null;
    }

    /**
     * Fetches next record from the result set of the previously executed query.
     *
     * Utility around next_record where we just return the array as MYSQLI_BOTH
     * and require the user to explicitly define which columns to define (as opposed
     * to all columns always being escaped, which is a bad sort of lazy). Things that
     * need to be escaped are strings that users input (with any characters) and
     * are not displayed inside a textarea or input field.
     *
     * @param mixed  $Escape Boolean true/false for escaping entire/none of query
     *                          or can be an array of array keys for what columns to escape
     */
    public function fetch_record(...$Escape): ?array {
        if (count($Escape) === 1 && $Escape[0] === true) {
            $Escape = true;
        }
        elseif (count($Escape) === 0) {
            $Escape = false;
        }
        return $this->next_record(MYSQLI_BOTH, $Escape, true);
    }

    public function close(): void {
        if ($this->LinkID) {
            if (!mysqli_close($this->LinkID)) {
                $this->halt('Cannot close connection or connection did not open.');
            }
            $this->LinkID = false;
        }
    }

    /*
     * returns an integer with the number of rows found
     * returns a string if the number of rows found exceeds MAXINT
     */
    public function record_count(): int|string|null {
        if ($this->QueryID) {
            return mysqli_num_rows($this->QueryID);
        }
        return null;
    }

    /*
     * returns true if the query exists and there were records found
     * returns false if the query does not exist or if there were 0 records returned
     */
    public function has_results(): bool {
        return ($this->QueryID && $this->record_count() !== 0);
    }

    public function affected_rows(): int {
        if ($this->LinkID) {
            return $this->LinkID->affected_rows;
        }
        /* why the fuck is this necessary for \Gazelle\Bonus\purchaseInvite() ?! */
        if ($this->Statement) {
            return $this->Statement->affected_rows;
        }
        return 0;
    }

    public function info(): string {
        return mysqli_get_host_info($this->LinkID);
    }

    // Creates an array from a result set
    // If $Key is set, use the $Key column in the result set as the array key
    // Otherwise, use an integer
    public function to_array(bool|string $Key = false, int $Type = MYSQLI_BOTH, bool|array $Escape = true): array {
        $Return = [];
        while ($Row = mysqli_fetch_array($this->QueryID, $Type)) {
            if ($Escape !== false) {
                $Row = $this->display_array($Row, $Escape);
            }
            if ($Key !== false) {
                $Return[$Row[$Key]] = $Row;
            } else {
                $Return[] = $Row;
            }
        }
        mysqli_data_seek($this->QueryID, 0);
        return $Return;
    }

    //  Loops through the result set, collecting the $ValField column into an array with $KeyField as keys
    public function to_pair(string $KeyField, string $ValField, bool $Escape = true): array {
        $Return = [];
        while ($Row = mysqli_fetch_array($this->QueryID)) {
            if ($Escape) {
                $Key = display_str($Row[$KeyField]);
                $Val = display_str($Row[$ValField]);
            } else {
                $Key = $Row[$KeyField];
                $Val = $Row[$ValField];
            }
            $Return[$Key] = $Val;
        }
        mysqli_data_seek($this->QueryID, 0);
        return $Return;
    }

    //  Loops through the result set, collecting the $Key column into an array
    public function collect(int|string $Key, bool $Escape = true): array {
        $Return = [];
        while ($Row = mysqli_fetch_array($this->QueryID)) {
            $Return[] = $Escape ? display_str($Row[$Key]) : $Row[$Key];
        }
        mysqli_data_seek($this->QueryID, 0);
        return $Return;
    }

    /**
     * Runs a prepared_query using placeholders and returns the matched row.
     * Stashes the current query id so that this can be used within a block
     * that is looping over an active resultset.
     */
    public function row(string $sql, mixed ...$args): ?array {
        $qid = $this->get_query_id();
        $this->prepared_query($sql, ...$args);
        $result = $this->next_record(MYSQLI_NUM, false);
        $this->set_query_id($qid);
        return $result;
    }

    /**
     * Runs a prepared_query using placeholders and returns the matched row.
     * Stashes the current query id so that this can be used within a block
     * that is looping over an active resultset.
     *
     * @param mixed   $args  The values of the placeholders
     * @return array  key=>value resultset or null
     */
    public function rowAssoc(string $sql, ...$args): ?array {
        $qid = $this->get_query_id();
        $this->prepared_query($sql, ...$args);
        $result = $this->next_record(MYSQLI_ASSOC, false);
        $this->set_query_id($qid);
        return $result;
    }

    /**
     * Runs a prepared_query using placeholders and returns the first element
     * of the first row.
     * Stashes the current query id so that this can be used within a block
     * that is looping over an active resultset.
     */
    public function scalar(string $sql, mixed ...$args): int|string|bool|float|null {
        $qid = $this->get_query_id();
        $this->prepared_query($sql, ...$args);
        $result = $this->has_results() ? $this->next_record(MYSQLI_NUM, false) : [null];
        $this->set_query_id($qid);
        return $result[0];
    }

    /**
     * Does a table.column exist in the database? This helps when code needs to
     * deal with (legitimate) variations in the schema.
     */
    public function entityExists(string $table, string $column): bool {
        return (bool)$this->scalar("
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = ?
                AND table_name = ?
                AND column_name = ?
            ", SQLDB, $table, $column
        );
    }

    public function set_query_id(mixed &$ResultSet): void {
        $this->QueryID = $ResultSet;
        $this->Row = 0;
    }

    public function get_query_id(): mixed {
        return $this->QueryID ?? false;
    }

    /**
     * This function determines whether the last query caused warning messages
     * and stores them in $this->Queries.
     */
    public function warnings(): void {
        $Warnings = [];
        if ($this->LinkID !== false && mysqli_warning_count($this->LinkID)) {
            $e = mysqli_get_warnings($this->LinkID);
            do {
                if ($e->errno == 1592) {
                    // 1592: Unsafe statement written to the binary log using statement format since BINLOG_FORMAT = STATEMENT.
                    continue;
                }
                $Warnings[] = 'Code ' . $e->errno . ': ' . display_str($e->message);
            } while ($e->next());
        }
        $this->Queries[count($this->Queries) - 1][2] = $Warnings;
    }

    public function begin_transaction(): void {
        if (!$this->LinkID) {
            $this->connect();
        }
        mysqli_begin_transaction($this->LinkID);
    }

    public function commit(): void {
        mysqli_commit($this->LinkID);
    }

    public function rollback(): void {
        mysqli_rollback($this->LinkID);
    }

    /**
     * HTML escape an entire array for output.
     * @param array $Array, what we want to escape
     * @param boolean|array $Escape
     *    if true, all keys escaped
     *    if false, no escaping.
     *    If array, it's a list of array keys not to escape.
     * @param boolean $Reverse reverses $Escape such that then it's an array of keys to escape
     * @return array mutated version of $Array with values escaped.
     */
    protected function display_array(array $Array, mixed $Escape = [], bool $Reverse = false): array {
        foreach ($Array as $Key => $Val) {
            if ((!is_array($Escape) && $Escape == true) || (!$Reverse && !in_array($Key, $Escape)) || ($Reverse && in_array($Key, $Escape))) {
                $Array[$Key] = display_str($Val);
            }
        }
        return $Array;
    }
}
