<?php
class categorycontroller {
    private $categoryModel;
    
    public function __construct() {
        require_once __DIR__ . '/../../models/category.php';
        $this->categoryModel = new category();
    }
    
    public function index() {
        // Obtener todas las categorías
        // $categories = $this->categoryModel->getAll();
        
        // Datos de ejemplo
        $categories = [
            ['id' => 1, 'name' => 'Electrónica', 'description' => 'Productos electrónicos', 'image' => '/public/assets/img/categories/electronics.jpg'],
            ['id' => 2, 'name' => 'Ropa', 'description' => 'Moda y accesorios', 'image' => '/public/assets/img/categories/clothing.jpg'],
            ['id' => 3, 'name' => 'Hogar', 'description' => 'Artículos para el hogar', 'image' => '/public/assets/img/categories/home.jpg']
        ];
        
        $pageTitle = "Categorías";
        
        include __DIR__ . '/../../views/shop/category/view.php';
    }
    
    public function show($slug) {
        // Obtener categoría y sus productos
        // $category = $this->categoryModel->getBySlug($slug);
        // $products = $this->categoryModel->getProducts($category['id']);
        
        // Datos de ejemplo
        $category = ['id' => 1, 'name' => 'Electrónica', 'description' => 'Productos electrónicos'];
        $products = [];
        
        if (!$category) {
            $_SESSION['error_message'] = 'Categoría no encontrada';
            redirect('/categorias');
            return;
        }
        
        $pageTitle = $category['name'];
        
        include __DIR__ . '/../../views/shop/category/view.php';
    }
}