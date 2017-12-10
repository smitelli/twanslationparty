<?php

  /**
   * Wrapper class to help with PDO database queries. All members of this class
   * are static, to allow multiple classes to extend this base class and still
   * utilize the existing database connections.
   * @author Scott Smitelli
   * @package twanslationparty
   */

  class Database {
    const CHARSET = 'UTF8';
    private static $cfg = NULL;
    private static $dbh = NULL;
    private static $sth = NULL;

    /**
     * Constructor function. Parses a config array for database connection info
     * and creates a persistent connection to that database.
     * @access public
     * @param array $config The configuration array
     */
    public function __construct($config) {
      if (is_null(self::$dbh)) {
        try {
          // Haven't connected yet, try to do so
          self::$cfg = (object) $config;
          self::db_connect(self::$cfg->server, self::$cfg->username, self::$cfg->password, self::$cfg->database);

        } catch (PDOException $e) {
          // We need to catch the PDO exception, as its error message will contain the MySQL login information.
          throw new Exception('Could not establish a database connection.');
        }
      }
    }

    /**
     * Opens a new connection to the database.
     * @access public
     * @param string $host The server to connect to
     * @param string $user The user name to authenticate with
     * @param string $pass The password to authenticate with
     * @param string $db The database to use
     */
    public function db_connect($host, $user, $pass, $db) {
      self::$dbh = new PDO("mysql:host={$host};dbname={$db}", $user, $pass);
      self::$sth = NULL;

      self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      self::$dbh->exec('SET CHARACTER SET "' . self::CHARSET . '"');
    }

    /**
     * Destroys any connection that is currently open.
     * @access public
     */
    public function db_disconnect() {
      self::$dbh = NULL;
      self::$sth = NULL;
    }

    /**
     * Executes a single SQL query without parametrization, ignoring any results
     * that were returned. For UPDATE/etc., returns row count. See PDO::exec().
     * @access public
     * @param string $sql The SQL query to run
     * @return integer The number of rows affected by the query
     */
    public function db_exec_single($sql) {
      return self::$dbh->exec($sql);
    }

    /**
     * Executes a single SQL query with optional parametrization. Results from a
     * SELECT query are remembered and can be accessed with db_fetch().
     * @access public
     * @param string $sql The SQL query to run
     * @param array|object $params Array of key/value pairs for each query param
     * @return integer The number of rows in the result set
     */
    public function db_query($sql, $params = FALSE) {
      if (is_array($params) || is_object($params)) {
        $params = (array) $params;  //make sure objects become assoc. arrays
        self::$sth = self::$dbh->prepare($sql);
        self::$sth->execute($params);

      } else {
        self::$sth = self::$dbh->query($sql);
      }

      return self::$sth->rowCount();
    }

    /**
     * Fetch a single row from the results from the most recent db_query() call.
     * Each call to this method advances the cursor to the next row. If all rows
     * have been exhausted, returns FALSE.
     * @access public
     * @return object Data from the current row in the result set
     */
    public function db_fetch() {
      return self::$sth->fetch(PDO::FETCH_OBJ);
    }
  }

?>
