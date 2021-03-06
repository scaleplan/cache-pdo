<?php
declare(strict_types=1);

namespace Scaleplan\Db;

use Scaleplan\Db\Exceptions\PDOConnectionException;
use Scaleplan\Db\Interfaces\DbInterface;
use Scaleplan\Db\Interfaces\TableTagsInterface;
use function Scaleplan\Translator\translate;

/**
 * Class TableTags
 *
 * @package Scaleplan\Db
 */
class TableTags implements TableTagsInterface
{
    /**
     * С какими схемами дополнительно будет рабоать объект при подключении к PosqlgreSQL
     */
    public const PGSQL_ADDITIONAL_TABLES = ['pg_enum'];

    public const SYSTEM_SCHEMAS = ['pg_catalog', 'information_schema'];

    /**
     * С какими схемами дополнительно будет рабоать объект при подключении к MySQL
     */
    public const MYSQL_ADDITIONAL_TABLES = [];

    /**
     * @var DbInterface
     */
    protected $db;

    /**
     * @var array
     */
    protected $tables;

    /**
     * TableTags constructor.
     *
     * @param DbInterface $db
     */
    public function __construct(DbInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Добавить дополнительные таблицы к используемым
     */
    protected function addAdditionTables() : void
    {
        $dbms = strtoupper($this->db->getDBDriver());
        foreach (\constant("static::{$dbms}_ADDITIONAL_TABLES") as $table) {
            $this->tables[]['table_name'] = $table;
        }
    }

    /**
     * Инициализировать хранение имен таблиц в сессии
     *
     * @param string $dbName - имя базы данных
     */
    protected static function initSessionStorage(string $dbName) : void
    {
        if (!isset($_SESSION['databases']) || !\is_array($_SESSION['databases'])) {
            $_SESSION['databases'] = [];
        }

        if (!isset($_SESSION['databases'][$dbName]) || !\is_array($_SESSION['databases'][$dbName])) {
            $_SESSION['databases'][$dbName] = ['tables' => []];
        }
    }

    /**
     * Созранить список пользовательстких таблиц базы данных
     *
     * @param string[]|null $schemas - какие схемы будут использоваться
     *
     * @throws PDOConnectionException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function initTablesList(array $schemas = null) : void
    {
        $dbName = $this->db->getDbName();

        if (!empty($_SESSION['databases'][$dbName]['tables'])) {
            $this->tables = $_SESSION['databases'][$dbName]['tables'];
            return;
        }

        static::initSessionStorage($dbName);

        $this->addAdditionTables();

        if ($this->db->getDBDriver() === 'pgsql') {
            $_SESSION['databases'][$dbName]['tables']
                = $this->tables
                = array_merge($this->tables, $this->getPostgresTables($schemas));
        } elseif ($this->db->getDBDriver() === 'mysql') {
            $_SESSION['databases'][$dbName]['tables']
                = $this->tables
                = array_merge($this->tables, $this->getMysqlTables($dbName));
        }

        if (!$this->tables) {
            throw new PDOConnectionException(translate('db.tables-list-received-error'));
        }
    }

    /**
     * Получить список пользовательстких таблиц базы данных
     *
     * @param array|null $schemas
     *
     * @return array
     */
    protected function getPostgresTables(array $schemas = null) : array
    {
        if (null !== $schemas) {
            return $this->db->query(
                "SELECT table_schema || '.' || table_name AS table_name 
                 FROM information_schema.tables 
                 WHERE table_schema = ANY(string_to_array(:schemas, ','))",
                ['schemas' => implode(',', $schemas)]
            );
        }

        return $this->db->query(
            "SELECT table_schema || '.' || table_name AS table_name 
             FROM information_schema.tables 
             WHERE table_schema != ALL(string_to_array(:schemas, ','))",
            ['schemas' => implode(',', static::SYSTEM_SCHEMAS)]
        );
    }

    /**
     * @param string $dbName
     *
     * @return array
     */
    protected function getMysqlTables(string $dbName) : array
    {
        return $this->db->query("SHOW TABLES FROM $dbName");
    }

    /**
     * Возвращаем имена таблиц использующихся в запросе только для запросов на изменение
     *
     * @param string $query - запрос
     *
     * @return array
     */
    public function getEditTables(string &$query) : array
    {
        if (preg_match('/(UPDATE\s|INSERT\sINTO\s|DELETE\s|ALTER\sTYPE)/', $query)) {
            return $this->getTables($query);
        }

        return [];
    }

    /**
     * Возвращаем имена таблиц использующихся в запросе
     *
     * @param string $query - запрос
     *
     * @return array
     */
    public function getTables(string &$query) : array
    {
        $tables = [];
        foreach ($this->tables as &$table) {
            if (strpos($query, $table['table_name']) !== false) {
                $tables[] = $table['table_name'];
            }
        }

        unset($table);
        return array_unique($tables);
    }
}
