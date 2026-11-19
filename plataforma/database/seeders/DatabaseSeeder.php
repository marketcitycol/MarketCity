<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\MerchantEntity;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Datos de PRUEBA para abrir y probar la versión 1. No contiene datos reales de clientes, vendedores ni del titular.
 * La migración de los datos reales desde WooCommerce/WCFM es un paso aparte (ver docs/producto/alcance-v1.md).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'commission_percent' => '15',
            'commission_fixed' => '0',
            'settlement_business_days' => '10',
            'payment_methods' => 'sandbox,transferencia',
            'terms_version' => '2026-11',
            'installments' => '4',
            // Cuenta de PRUEBA para ver la transferencia en local; en el servidor la escribe el propietario.
            'transfer_bank' => 'Banco de prueba',
            'transfer_account_type' => 'Ahorros',
            'transfer_account_number' => '000-000000-00',
            'transfer_holder' => 'MarketCity (prueba)',
            'transfer_holder_document' => 'NIT 000.000.000-0',
            'transfer_deadline_hours' => '24',
        ];
        foreach ($settings as $k => $v) {
            Setting::put($k, $v);
        }

        MerchantEntity::create([
            'legal_name' => 'Fundador de MarketCity (datos por completar)',
            'person_type' => 'natural', 'document_type' => 'CC', 'document_number' => 'POR-COMPLETAR',
            'email' => 'pagos@marketcity.test', 'city' => 'Bogotá',
            'is_active' => true, 'active_from' => now(),
        ]);
        MerchantEntity::create([
            'legal_name' => 'MarketCity S.A.S. (en constitución)',
            'person_type' => 'juridica', 'document_type' => 'NIT', 'document_number' => 'POR-COMPLETAR',
            'email' => 'pagos@marketcity.test', 'city' => 'Bogotá', 'is_active' => false,
        ]);

        User::create(['name' => 'Administrador MarketCity', 'email' => 'admin@marketcity.test', 'password' => 'marketcity', 'role' => 'admin']);
        User::create(['name' => 'Laura Gómez', 'email' => 'cliente@marketcity.test', 'password' => 'marketcity', 'role' => 'customer', 'phone' => '3001234567', 'document_type' => 'CC', 'document_number' => '1000000001']);

        $categories = collect([
            ['Celulares y tablets', '📱'], ['Computadores', '💻'], ['Audio', '🎧'], ['Electrodomésticos', '🔌'],
            ['Hogar y cocina', '🍳'], ['Moda', '👕'], ['Belleza y cuidado', '💄'], ['Deportes', '⚽'],
            ['Juguetes', '🧸'], ['Mascotas', '🐾'],
        ])->mapWithKeys(fn ($c, $i) => [$c[0] => Category::create(['name' => $c[0], 'slug' => Str::slug($c[0]), 'icon' => $c[1], 'position' => $i])]);

        $brands = collect(['Genérico', 'Samsung', 'Xiaomi', 'Lenovo', 'JBL', 'Oster', 'Imusa', 'Adidas', 'Nike', 'Lego', 'Philips', 'Hasbro'])
            ->mapWithKeys(fn ($b) => [$b => Brand::create(['name' => $b, 'slug' => Str::slug($b)])]);

        $stores = [
            ['TecnoAndes', 'Bogotá D.C.', 'Bogotá', 'Tecnología original con garantía.', true, 0, true, 10000],
            ['Casa Caribe', 'Atlántico', 'Barranquilla', 'Todo para tu hogar y tu cocina.', false, 12000, false, 0],
            ['Ruta Sport', 'Antioquia', 'Medellín', 'Ropa y artículos deportivos.', true, 0, true, 8000],
            ['Mundo Kids & Pets', 'Valle del Cauca', 'Cali', 'Juguetes y productos para mascotas.', false, 9000, false, 0],
        ];
        $storeModels = [];
        foreach ($stores as $i => [$name, $dep, $city, $desc, $free, $fee, $pickup, $pickupFee]) {
            $u = User::create([
                'name' => "Vendedor {$name}", 'email' => 'vendedor'.($i + 1).'@marketcity.test',
                'password' => 'marketcity', 'role' => 'seller', 'phone' => '30000000'.($i + 10),
            ]);
            $storeModels[$name] = Store::create([
                'user_id' => $u->id, 'name' => $name, 'slug' => Str::slug($name), 'description' => $desc,
                'department' => $dep, 'city' => $city, 'address' => 'Dirección de prueba '.($i + 1),
                'phone' => $u->phone, 'document_type' => 'CC', 'document_number' => '9000000'.$i,
                'free_shipping' => $free, 'shipping_fee' => $fee, 'pickup_enabled' => $pickup, 'pickup_fee' => $pickupFee,
                'status' => 'active',
            ]);
        }
        // Ejemplo de condiciones negociadas: comisión del 12 % y solo pago en línea.
        $storeModels['Ruta Sport']->update(['commission_percent' => 12, 'payment_methods' => 'sandbox']);

        // Una solicitud pendiente para probar la aprobación en el backoffice.
        $u = User::create(['name' => 'Vendedor Nuevo', 'email' => 'vendedor5@marketcity.test', 'password' => 'marketcity', 'role' => 'seller']);
        Store::create(['user_id' => $u->id, 'name' => 'Boutique Nueva', 'slug' => 'boutique-nueva', 'department' => 'Santander', 'city' => 'Bucaramanga', 'status' => 'pending']);

        // [tienda, categoría, marca, nombre, precio, precio antes, stock, color, destacado]
        $products = [
            ['TecnoAndes', 'Celulares y tablets', 'Samsung', 'Celular Galaxy A16 128 GB', 749900, 899900, 12, 'Negro', true],
            ['TecnoAndes', 'Celulares y tablets', 'Xiaomi', 'Celular Redmi 14C 256 GB', 619900, 699900, 20, 'Azul', true],
            ['TecnoAndes', 'Celulares y tablets', 'Samsung', 'Tablet Galaxy Tab A9 64 GB', 689900, null, 7, 'Gris', false],
            ['TecnoAndes', 'Celulares y tablets', 'Genérico', 'Cargador rápido USB-C 25 W', 39900, 59900, 80, 'Blanco', false],
            ['TecnoAndes', 'Computadores', 'Lenovo', 'Portátil IdeaPad Slim 3 15" Ryzen 5', 2199900, 2599900, 5, 'Gris', true],
            ['TecnoAndes', 'Computadores', 'Lenovo', 'Mouse inalámbrico ergonómico', 49900, null, 40, 'Negro', false],
            ['TecnoAndes', 'Computadores', 'Genérico', 'Teclado mecánico RGB en español', 159900, 189900, 15, 'Negro', false],
            ['TecnoAndes', 'Audio', 'JBL', 'Parlante JBL Flip 6 resistente al agua', 499900, 579900, 9, 'Rojo', true],
            ['TecnoAndes', 'Audio', 'JBL', 'Audífonos JBL Tune 520BT', 219900, 259900, 18, 'Negro', false],
            ['TecnoAndes', 'Audio', 'Xiaomi', 'Audífonos Redmi Buds 6 Active', 89900, null, 30, 'Blanco', false],
            ['Casa Caribe', 'Electrodomésticos', 'Oster', 'Licuadora Oster Xpert 1,5 L', 299900, 349900, 14, 'Plateado', true],
            ['Casa Caribe', 'Electrodomésticos', 'Oster', 'Freidora de aire Oster 4 L', 349900, 429900, 10, 'Negro', true],
            ['Casa Caribe', 'Electrodomésticos', 'Philips', 'Plancha a vapor Philips 2000', 139900, null, 16, 'Azul', false],
            ['Casa Caribe', 'Electrodomésticos', 'Genérico', 'Ventilador de pie 18" 3 velocidades', 159900, 199900, 0, 'Blanco', false],
            ['Casa Caribe', 'Hogar y cocina', 'Imusa', 'Juego de ollas Imusa 5 piezas', 259900, 319900, 11, 'Rojo', false],
            ['Casa Caribe', 'Hogar y cocina', 'Imusa', 'Sartén antiadherente 24 cm', 69900, null, 25, 'Negro', false],
            ['Casa Caribe', 'Hogar y cocina', 'Genérico', 'Juego de sábanas doble 144 hilos', 89900, 119900, 22, 'Beige', false],
            ['Casa Caribe', 'Hogar y cocina', 'Genérico', 'Organizador de cocina 3 niveles', 54900, null, 19, 'Blanco', false],
            ['Ruta Sport', 'Deportes', 'Adidas', 'Balón de fútbol Adidas talla 5', 119900, 139900, 24, 'Blanco', true],
            ['Ruta Sport', 'Deportes', 'Genérico', 'Mancuernas ajustables 20 kg', 249900, 299900, 6, 'Negro', false],
            ['Ruta Sport', 'Deportes', 'Genérico', 'Tapete de yoga 6 mm', 59900, null, 30, 'Morado', false],
            ['Ruta Sport', 'Moda', 'Nike', 'Tenis Nike Revolution 7 hombre', 329900, 389900, 13, 'Negro', true],
            ['Ruta Sport', 'Moda', 'Adidas', 'Camiseta deportiva Adidas mujer', 99900, null, 28, 'Rosado', false],
            ['Ruta Sport', 'Moda', 'Genérico', 'Gorra deportiva ajustable', 39900, 49900, 35, 'Azul', false],
            ['Ruta Sport', 'Belleza y cuidado', 'Philips', 'Afeitadora Philips OneBlade', 189900, 229900, 12, 'Verde', false],
            ['Mundo Kids & Pets', 'Juguetes', 'Lego', 'Lego Classic caja creativa 484 piezas', 189900, 219900, 8, 'Multicolor', true],
            ['Mundo Kids & Pets', 'Juguetes', 'Hasbro', 'Juego de mesa Monopoly Colombia', 139900, null, 15, 'Multicolor', false],
            ['Mundo Kids & Pets', 'Juguetes', 'Genérico', 'Carro a control remoto 4x4', 129900, 169900, 9, 'Rojo', false],
            ['Mundo Kids & Pets', 'Mascotas', 'Genérico', 'Cama para perro mediana', 99900, 129900, 14, 'Gris', false],
            ['Mundo Kids & Pets', 'Mascotas', 'Genérico', 'Rascador para gato 3 niveles', 149900, null, 6, 'Beige', false],
            ['Mundo Kids & Pets', 'Mascotas', 'Genérico', 'Comedero automático con temporizador', 119900, 149900, 10, 'Blanco', false],
            ['Mundo Kids & Pets', 'Belleza y cuidado', 'Genérico', 'Kit de cuidado facial 4 pasos', 79900, 99900, 20, 'Blanco', false],
        ];

        foreach ($products as $i => [$store, $cat, $brand, $name, $price, $regular, $stock, $color, $featured]) {
            Product::create([
                'store_id' => $storeModels[$store]->id,
                'category_id' => $categories[$cat]->id,
                'brand_id' => $brands[$brand]->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'sku' => 'MC-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'short_description' => "{$name}. Producto de prueba vendido por {$store}.",
                'description' => "Descripción de prueba para «{$name}».\n\nEste catálogo es sintético y sirve para probar la versión 1 de la plataforma propia de MarketCity. Garantía legal según el Estatuto del Consumidor.",
                'price' => $price,
                'regular_price' => $regular,
                'stock' => $stock,
                'color' => $color,
                'featured' => $featured,
                'sales_count' => ($i * 7) % 23,
                'status' => 'active',
            ]);
        }
    }
}
