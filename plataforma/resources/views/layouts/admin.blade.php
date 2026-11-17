@extends('layouts.panel', ['area' => 'Backoffice', 'menu' => [
    ['Resumen', 'admin.dashboard'], ['Pedidos', 'admin.orders'], ['Vendedores', 'admin.stores'],
    ['Productos', 'admin.products'], ['Liquidaciones', 'admin.settlements'], ['Importar catálogo', 'admin.import'], ['Configuración', 'admin.settings'],
]])
