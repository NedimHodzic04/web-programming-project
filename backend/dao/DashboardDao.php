<?php
// DashboardDao.php
require_once __DIR__ . '/BaseDao.php'; // Adjust path if necessary

class DashboardDao extends BaseDao { // <--- Extend BaseDao

    public function __construct() {
        // You must call the parent constructor and provide a table name.
        // Since DashboardDao queries multiple tables, a common approach is:
        // 1. Pass a placeholder table name if BaseDao strictly requires it.
        // 2. Or, if BaseDao's methods for table-specific operations (like getAll, getById)
        //    are not used by DashboardDao, you might make BaseDao's $table optional
        //    or provide a dummy value.
        // Let's use a dummy table name for now, as BaseDao's constructor requires it.
        parent::__construct(); // Or any other primary table, or a generic string.
                                     // The specific table name passed here doesn't matter
                                     // much for DashboardDao's *specific* methods below,
                                     // as they explicitly name tables in their queries.
    }

    public function getUserCount() {
        $stmt = $this->connection->query("SELECT COUNT(*) as count FROM users");
        return $stmt->fetchColumn();
    }

    public function getOrderCount() {
        $stmt = $this->connection->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
        return $stmt->fetchColumn();
    }

    public function getProductCount() {
        $stmt = $this->connection->query("SELECT COUNT(*) as count FROM products");
        return $stmt->fetchColumn();
    }

    public function getCategoryCount() {
        $stmt = $this->connection->query("SELECT COUNT(*) as count FROM categories");
        return $stmt->fetchColumn();
    }
}
?>