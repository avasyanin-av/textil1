<?php
/**
 * Класс для работы с базой данных
 * Использует PDO для безопасного подключения к MySQL
 */

class Database {
    private $pdo;
    private static $instance = null;
    
    // Приватный конструктор для реализации Singleton
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }
    
    // Получение экземпляра базы данных (Singleton)
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Получение PDO объекта
    public function getPDO() {
        return $this->pdo;
    }
    
    // Выполнение запроса без возвращения результата
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Получение одной записи
    public function queryOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    // Получение всех записей
    public function queryAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Вставка записи и возврат ID
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
        
        $stmt = $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }
    
    // Обновление записи
    public function update($table, $data, $where, $whereParams = []) {
        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = "{$field} = :{$field}";
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        return $this->query($sql, $params);
    }
    
    // Удаление записи
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params);
    }
    
    // Получение количества записей
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->queryOne($sql, $params);
        return (int) $result['count'];
    }
    
    // Начало транзакции
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    // Подтверждение транзакции
    public function commit() {
        return $this->pdo->commit();
    }
    
    // Откат транзакции
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    // Проверка существования записи
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
    
    // Получение последних записей
    public function getLatest($table, $limit = 10, $orderBy = 'created_at DESC', $where = '', $params = []) {
        $sql = "SELECT * FROM {$table}";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        $sql .= " ORDER BY {$orderBy} LIMIT {$limit}";
        
        return $this->queryAll($sql, $params);
    }
    
    // Функция для безопасного экранирования имен таблиц и полей
    public static function escapeName($name) {
        return '`' . str_replace('`', '``', $name) . '`';
    }
    
    // Деструктор
    public function __destruct() {
        $this->pdo = null;
    }
    
    // Предотвращение клонирования
    private function __clone() {}
    
    // Предотвращение десериализации
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>