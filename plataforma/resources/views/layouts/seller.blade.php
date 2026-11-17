@extends('layouts.panel', ['area' => 'Seller Center', 'menu' => [
    ['Inicio', 'seller.dashboard'], ['Productos', 'seller.products.index'], ['Pedidos', 'seller.orders'],
    ['Liquidaciones', 'seller.settlements'], ['Mi tienda', 'seller.store.edit'],
]])
