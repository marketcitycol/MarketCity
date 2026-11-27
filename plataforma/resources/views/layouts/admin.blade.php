@extends('layouts.panel', ['area' => 'Backoffice', 'menu' => [
    ['Resumen', 'admin.dashboard'], ['Pedidos', 'admin.orders'], ['PQRS', 'admin.pqrs'], ['Contacto', 'admin.contact'], ['Vendedores', 'admin.stores'],
    ['Productos', 'admin.products'], ['Preguntas', 'admin.questions'], ['Liquidaciones', 'admin.settlements'], ['Campañas', 'admin.campaigns'], ['Cupones', 'admin.coupons'], ['Mensajes', 'admin.messages'], ['Importar catálogo', 'admin.import'], ['Páginas', 'admin.pages'], ['Plugins', 'admin.plugins'], ['Configuración', 'admin.settings'], ['Cambio de dominio', 'admin.cutover'], ['Seguridad', 'account.security'],
]])
