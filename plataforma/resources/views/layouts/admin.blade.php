@extends('layouts.panel', ['area' => 'Backoffice', 'menu' => [
    ['Resumen', 'admin.dashboard'], ['Pedidos', 'admin.orders'], ['PQRS', 'admin.pqrs'], ['Vendedores', 'admin.stores'],
    ['Productos', 'admin.products'], ['Liquidaciones', 'admin.settlements'], ['Importar catálogo', 'admin.import'], ['Páginas', 'admin.pages'], ['Plugins', 'admin.plugins'], ['Configuración', 'admin.settings'], ['Cambio de dominio', 'admin.cutover'],
]])
