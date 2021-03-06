<?php
declare(strict_types=1);
/**
 * This file is a part of secure-php-login-system.
 *
 * @author Akbar Hashmi (Owner/Developer)            <me@akbarhashmi.com>.
 * @author Nicholas English (Collaborator/Developer) <nenglish0820@outlook.com>.
 *
 * @link    <https://github.com/akbarhashmi/Secure-PHP-Login-System> Github repository.
 * @license <https://github.com/akbarhashmi/Secure-PHP-Login-System/blob/master/LICENSE> MIT license.
 */
 
namespace Akbarhashmi\Engine\Database;

use PDO;
use PDOException;
use Akbarhashmi\Engine\Exception\InvalidArgumentException;

/**
 * MySQLConnect.
 *
 * @codeCoverageIgnore
 */
class MySQLConnect extends PDO implements MySQLConnectInterface
{
    
    /**
     * @var bool $debug Should we run debugging.
     */
    protected $debug = \false;

    /**
     * Try to connect to the mysql server.
     *
     * @param string $hostname The database hostname.
     * @param string $port     The database port.
     * @param string $database The database name.
     * @param string $username The database username.
     * @param string $password The database password.
     * @param bool   $debug    Should we run debug.
     *
     * @throws InvalidArgumentException If the port argument is an invalid
     *                                  data type.
     * 
     * @return void.
     */
    public function __construct(string $hostname = 'localhost', $port = 3306, string $database, string $username, string $password = '', bool $debug = \false)
    {
        // Check the port data type.
        if (!\is_int($port) && !\is_string($port))
        {
            // Throw an error.
            throw new InvalidArgumentException(\sprintf(
                'The port argument is not a valid data type. Allowed: `string, int`. Passed: `%s`.',
                \gettype($port)
            ));
        }    
        // Set the debugging mode.
        $this->debug($debug);
        // Formulate a dns connection string.
        $dns = $this->formulateDns($hostname, $port, $database);
        // Try to connect to the server.
        try
        {
            // Actually attempt a connection to the database.
            parent::__construct($dns, $username, $password);
            // Set the character set.
            $this->exec('SET CHARACTER SET utf8');
            // If debug is enabled turn on debugging mode.
            if ($this->debug)
            {
                // Set the errmode attribute
                $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
            }
        } catch (PDOException $e)
        {
            // Catch the error and kill the script.
            die('Connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Formulate a dns string for the connection.
     *
     * @param string $hostname The database hostname.
     * @param string $port     The database port.
     * @param string $database The database name.
     *
     * @return string The dns string.
     */
    private function formulateDns(string $hostname, string $port, string $database)
    {
        // Formulate the dns string for the connection.
        return "mysql:host={$hostname};port={$port};dbname={$database};charset=utf8";
    }

    /**
     * Should we be running debug mode.
     *
     * @param bool $debug Is debugging enabled.
     *
     * @return void.
     */
    public function debug(bool $debug)
    {
        $this->debug = $debug;
    }

    /**
     * Run a select sql query.
     *
     * @param string $sql The sql select string.
     * @param array  $array The list of parameters to bind.
     * @param int    $fetchMode int The PDO fetch mode to use.
     *
     * @return array Return the array fetch results.
     */
    public function select(string $sql, array $array = [], $fetchMode = PDO::FETCH_ASSOC): array
    {
        // Prepare the sql statement.
        $sth = $this->prepare($sql);
        // Test the array parameters.
        foreach ($array as $key => $value)
        {
            // Bind the array value.
            $sth->bindValue(":$key", $value);
        }
        // Execute the statement.
        $sth->execute();
        // Return the results.
        return $sth->fetchAll($fetchMode);
    }

    /**
     * Run an insert query.
     *
     * @param string $table The name of the table
     * @param array  $data  An array of data to insert.
     *
     * @return bool Return TRUE if the statement ran properly.
     */
    public function insert(string $table, array $data): bool
    {
        // Sort the data out.
        \ksort($data);
        // Prepare the field names and values.
        $fieldNames = \implode('`, `', \array_keys($data));
        $fieldValues = ':' . \implode(', :', \array_keys($data));
        // Prepare the statements.
        $sth = $this->prepare("INSERT INTO $table (`$fieldNames`) VALUES ($fieldValues)");
        // Test the array parameters.
        foreach ($data as $key => $value)
        {
            // Bind the array value.
            $sth->bindValue(":$key", $value);
        }
        // Execute the statement.
        $sth->execute();
        // Return TRUE.
        return \true;
    }

    /**
     * Update an array of data in the database.
     *
     * @param string $table          The name of the table.
     * @param array  $data           An array of data to update.
     * @param string $where          Where to bind the array parameters.
     * @param array  $whereBindArray Parameters to bind to where part of query.
     *
     * @return bool Return TRUE if the statement ran properly.
     */
    public function update(string $table, array $data, string $where, array $whereBindArray = []): bool
    {
        // Sort the data out.
        \ksort($data);
        // Set a temp variable.
        $fieldDetails = \null;
        // Test the parameters array.
        foreach ($data as $key => $value)
        {
            // Set the sql statement string part.
            $fieldDetails .= "`$key`=:$key,";
        }
        // Trim off any extra comma.
        $fieldDetails = \rtrim($fieldDetails, ',');
        // Prepare the statement.
        $sth = $this->prepare("UPDATE $table SET $fieldDetails WHERE $where");
        // Prepare the binding of the new values.
        foreach ($data as $key => $value)
        {
            // Bind the value names.
            $sth->bindValue(":$key", $value);
        }
        // Bind the data to update in the database.
        foreach ($whereBindArray as $key => $value)
        {
            // Bind the data.
            $sth->bindValue(":$key", $value);
        }
        // Execute the query.
        $sth->execute();
        // Return TRUE.
        return \true;
    }

    /**
     * Run a delete query.
     *
     * @param string $table The name of the table.
     * @param string $where Where should we start binding.
     * @param array  $bind  The bind array data.
     * @param int    $limit How many rows are allowed to be deleted.
     *
     * @return bool Return TRUE if the statement ran properly.
     */
    public function delete(string $table, string $where, array $bind = [], int $limit = \null): bool
    {
        // Define the query string.
        $query = "DELETE FROM $table WHERE $where";
        // Check to see if we are using a limit.
        if ($limit) {
            // Add the limit to the query string.
            $query .= " LIMIT $limit";
        }
        // Prepare the query string.
        $sth = $this->prepare($query);
        // Test the bind parameters.
        foreach ($bind as $key => $value)
        {
            // Bind the data.
            $sth->bindValue(":$key", $value);
        }
        // Execute the query.
        $sth->execute();
        // Return TRUE.
        return \true;
    }
    
}
