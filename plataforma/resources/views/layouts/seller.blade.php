@extends('layouts.panel', ['area' => 'Seller Center', 'menu' => [
    ['Inicio', 'seller.dashboard'], ['Productos', 'seller.products.index'], ['Pedidos', 'seller.orders'],
    ['Liquidaciones', 'seller.settlements'], ['Mi tienda', 'seller.store.edit'], ['Datos legales y pagos', 'seller.legal'], ['Seguridad', 'seller.security'],
]])
