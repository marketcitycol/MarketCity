<?php

return [
    // Modo demostración: muestra el aviso de "versión de prueba" y pide a los buscadores no indexar.
    'demo' => (bool) env('MC_DEMO', true),
    // Muestra las cuentas de prueba en la pantalla de ingreso (solo en el computador local).
    'show_demo_accounts' => (bool) env('MC_SHOW_DEMO_ACCOUNTS', false),
];
