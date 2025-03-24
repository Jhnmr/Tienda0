<?php
class aboutcontroller {
    public function index() {
        $pageTitle = "Acerca de nosotros";
        include __DIR__ . '/../../views/shop/about.php';
    }
}