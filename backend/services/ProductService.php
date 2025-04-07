<?php
require_once 'BaseService.php';
require_once 'MenuItemDao.php';
class ProductService extends BaseService {
   public function __construct() {
       $dao = new ProductDao();
       parent::__construct($dao);
   }
   public function getByCategory($category) {
       return $this->dao->getByCategory($category);
   }
}
