<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Content\CoverageMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Rotación del inicio (#45) y mapa de cobertura sin datos de clientes (#33). */
class HomeCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_coverage_map_aggregates_by_city_and_hides_small_counts(): void
    {
        $make = function (string $dept, string $city, int $n, string $status = 'paid') {
            for ($i = 0; $i < $n; $i++) {
                Order::forceCreate([
                    'number' => 'MC-T'.uniqid(), 'first_name' => 'Nombre', 'last_name' => 'Secreto', 'document_number' => '1', 'email' => 'x@example.com',
                    'phone' => '3000000000', 'department' => $dept, 'city' => $city, 'address' => 'Calle Secreta 1', 'subtotal' => 1000, 'total' => 1000,
                    'payment_method' => 'sandbox', 'payment_status' => $status, 'terms_version' => '1', 'terms_accepted_at' => now(), 'access_token' => bin2hex(random_bytes(8)),
                ]);
            }
        };
        $make('Antioquia', 'Medellin', 4);          // sin tilde: igual se ubica en Medellín
        $make('Bogotá D.C.', 'Bogota D.C.', 2);      // Bogotá con otro formato
        $make('Santander', 'Pueblo Inventado', 1);    // ciudad desconocida: cae en el departamento
        $make('Cesar', 'Valledupar', 5, 'failed');    // no pagado: no cuenta
        CoverageMap::forget();

        $points = collect(CoverageMap::points())->keyBy(0);
        $this->assertSame(4, $points['Medellín'][4]);
        $this->assertSame(0, $points['Bogotá'][4]);      // menos de 3: sin número
        $this->assertTrue($points->has('Bucaramanga'));  // primera ciudad de Santander
        $this->assertFalse($points->has('Valledupar'));
        $this->assertStringNotContainsString('Secreto', json_encode($points->all()));

        $this->get('/')->assertOk()->assertSee('data-coverage-map', false)->assertSee('Medell')->assertDontSee('Calle Secreta');
    }

    public function test_home_rotation_keeps_sizes_and_avoids_repeats_and_can_be_turned_off(): void
    {
        Product::query()->update(['featured' => true]);
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertNotEmpty($html);

        Setting::put('home_rotation', '0');
        $a = $this->get('/')->viewData('newest')->pluck('id')->all();
        $b = $this->get('/')->viewData('newest')->pluck('id')->all();
        $this->assertSame($a, $b);
        $this->assertSame(Product::visible()->latest('id')->take(10)->pluck('id')->all(), $a);

        Setting::put('home_rotation', '1');
        $r = $this->get('/');
        $this->assertLessThanOrEqual(10, $r->viewData('newest')->count());
        $this->assertLessThanOrEqual(8, $r->viewData('featured')->count());
        $ranked = $r->viewData('bestsellers')->pluck('sales_count')->all();
        $sorted = $ranked;
        rsort($sorted);
        $this->assertSame($sorted, $ranked); // los más vendidos conservan su orden
    }

    public function test_dark_mode_toggle_is_offered_except_in_checkout_and_pagination_is_in_spanish(): void
    {
        $this->get('/')->assertSee('data-theme-toggle', false)->assertSee("localStorage.getItem('mc-theme')", false);
        $this->get('/checkout')->assertDontSee('data-theme-toggle', false)->assertDontSee("localStorage.getItem('mc-theme')", false);
        $this->get('/shop')->assertSee('Mostrando')->assertDontSee('Showing');
    }
}
