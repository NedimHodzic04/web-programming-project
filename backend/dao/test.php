<?php
require_once 'ProductService.php';
$menu_item_service = new ProductService();
$menus = $menu_item_service->createProduct(''); 
print_r($menus);
?>
