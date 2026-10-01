<?php

namespace Tests\Feature;

use App\Models\Especie;
use App\Models\Pond;
use App\Models\User;
use Database\Seeders\EspecieSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuiaPecesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EspecieSeeder::class);
    }

    public function test_guest_is_redirected_to_login_when_accessing_guia_peces(): void
    {
        $response = $this->get(route('guia-peces.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_guia_peces_index(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($user)->get(route('guia-peces.index'));

        $response->assertStatus(200)
            ->assertSee('Enciclopedia')
            ->assertSee('Guía Técnica de Cultivo de Peces')
            ->assertSee('Mojarra Negra / Plateada')
            ->assertSee('Mojarra Roja')
            ->assertSee('Cachama Blanca')
            ->assertSee('Bocachico')
            ->assertSee('Trucha Arcoíris')
            ->assertSee('Bagre Rayado')
            ->assertSee('Volver al Dashboard');
    }

    public function test_user_can_filter_especies_by_search_query(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('guia-peces.index', ['search' => 'Trucha']));

        $response->assertStatus(200)
            ->assertSee('Trucha Arcoíris')
            ->assertDontSee('Piaractus brachypomus');
    }

    public function test_user_can_filter_especies_by_clima(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('guia-peces.index', ['clima' => 'frío']));

        $response->assertStatus(200)
            ->assertSee('Trucha Arcoíris')
            ->assertDontSee('Oreochromis niloticus');
    }

    public function test_user_can_view_especie_show_page(): void
    {
        $user = User::factory()->create();
        $mojarra = Especie::where('nombre_comun', 'like', '%Mojarra Roja%')->firstOrFail();

        $response = $this->actingAs($user)->get(route('guia-peces.show', $mojarra->id));

        $response->assertStatus(200)
            ->assertSee('Mojarra Roja')
            ->assertSee('Oreochromis sp.')
            ->assertSee('1. Calidad del Agua y Densidad')
            ->assertSee('2. Alimentación y Proteína')
            ->assertSee('3. Protocolo Completo de Cultivo y Manejo')
            ->assertSee('4. Consejos para Policultivo')
            ->assertSee('Volver a la Guía de Peces');
    }

    public function test_pond_model_returns_correct_species_image(): void
    {
        $pondRoja = new Pond(['name' => 'Estanque 1 - Mojarra Roja']);
        $this->assertStringContainsString('mojarra_roja.jpg', $pondRoja->foto_especie);

        $pondCachama = new Pond(['name' => 'Estanque 2 - Cachama & Bocachico']);
        $this->assertStringContainsString('cachama_blanca.jpg', $pondCachama->foto_especie);

        $pondBocachico = new Pond(['name' => 'Estanque Especial Bocachico']);
        $this->assertStringContainsString('bocachico.jpg', $pondBocachico->foto_especie);

        $pondTrucha = new Pond(['name' => 'Tanque Raceways Trucha']);
        $this->assertStringContainsString('trucha_arcoiris.jpg', $pondTrucha->foto_especie);

        $pondBagre = new Pond(['name' => 'Estanque Bagre Rayado']);
        $this->assertStringContainsString('bagre_rayado.jpg', $pondBagre->foto_especie);
    }

    public function test_real_images_exist_in_public_directory(): void
    {
        $files = [
            'mojarra_negra.jpg',
            'mojarra_roja.jpg',
            'cachama_blanca.jpg',
            'cachama.jpg',
            'bocachico.jpg',
            'trucha_arcoiris.jpg',
            'bagre_rayado.jpg',
            'estanque_cultivo.jpg',
            'login_bg_piscicola.jpg',
        ];

        foreach ($files as $file) {
            $this->assertFileExists(public_path('images/peces/'.$file));
        }
    }
}
