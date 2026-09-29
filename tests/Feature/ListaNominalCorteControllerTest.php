<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ListaNominalCorte;
use App\Models\SeccionElectoral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListaNominalCorteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMINISTRADOR,
        ]);
    }

    public function test_can_list_historical_cortes(): void
    {
        ListaNominalCorte::create([
            'fecha_corte' => '2024-01-15',
            'fuente'      => 'INE - RFE',
            'descripcion' => 'Corte Inicial',
            'is_active'   => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.lista-nominal.cortes.index'));

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'total']);
    }

    public function test_can_create_corte_without_file(): void
    {
        $payload = [
            'fecha_corte' => '2024-03-31',
            'fuente'      => 'INE RFE Oficial',
            'descripcion' => 'Corte previo a elecciones',
            'is_active'   => true,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.lista-nominal.cortes.store'), $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('lista_nominal_cortes', [
            'fuente'    => 'INE RFE Oficial',
            'is_active' => true,
        ]);
    }

    public function test_can_create_corte_with_csv_file(): void
    {
        // Crear sección electoral para vincular
        $seccion = SeccionElectoral::create([
            'entidad'          => 28,
            'municipio'        => 41,
            'seccion'          => '1563',
            'distrito_federal' => 5,
            'distrito_local'   => 14,
            'tipo'             => 1,
            'poligono'         => DB::raw("ST_GeomFromText('POLYGON((-99.16 23.70, -99.12 23.70, -99.12 23.76, -99.16 23.76, -99.16 23.70))', 4326)"),
        ]);

        $csvContent = "seccion,total_lista_nominal,padron_electoral,hombres,mujeres,no_binario\n1563,1850,1900,900,950,0";
        $file = UploadedFile::fake()->createWithContent('corte_2024.csv', $csvContent);

        $payload = [
            'fecha_corte' => '2024-03-31',
            'fuente'      => 'INE RFE',
            'descripcion' => 'Corte con archivo directo',
            'is_active'   => false,
            'archivo'     => $file,
            'municipio'   => 41,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.lista-nominal.cortes.store'), $payload, [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('import.total_guardados', 1);

        $this->assertDatabaseHas('lista_nominal_detalles', [
            'seccion_electoral_id' => $seccion->id,
            'total_lista_nominal'  => 1850,
        ]);
    }

    public function test_can_import_detalles_to_existing_corte(): void
    {
        $seccion = SeccionElectoral::create([
            'entidad'          => 28,
            'municipio'        => 41,
            'seccion'          => '1563',
            'distrito_federal' => 5,
            'distrito_local'   => 14,
            'tipo'             => 1,
            'poligono'         => DB::raw("ST_GeomFromText('POLYGON((-99.16 23.70, -99.12 23.70, -99.12 23.76, -99.16 23.76, -99.16 23.70))', 4326)"),
        ]);

        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-04-30',
            'fuente'      => 'INE RFE',
            'is_active'   => false,
        ]);

        $csvContent = "seccion,total_lista_nominal,padron_electoral\n1563,2000,2050";
        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.lista-nominal.cortes.import', $corte), [
                'archivo'   => $file,
                'municipio' => 41,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_guardados', 1);

        $this->assertDatabaseHas('lista_nominal_detalles', [
            'lista_nominal_corte_id' => $corte->id,
            'seccion_electoral_id'   => $seccion->id,
            'total_lista_nominal'    => 2000,
        ]);
    }

    public function test_atomic_activation_deactivates_previous_corte(): void
    {
        $corte1 = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente'      => 'Fuente 1',
            'is_active'   => true,
        ]);

        $corte2 = ListaNominalCorte::create([
            'fecha_corte' => '2024-02-01',
            'fuente'      => 'Fuente 2',
            'is_active'   => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson(route('admin.lista-nominal.cortes.activar', $corte2));

        $response->assertStatus(200);

        $this->assertFalse($corte1->fresh()->is_active);
        $this->assertTrue($corte2->fresh()->is_active);
    }

    public function test_can_update_corte_metadata(): void
    {
        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente'      => 'Fuente Antigua',
            'descripcion' => 'Descripción previa',
            'is_active'   => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson(route('admin.lista-nominal.cortes.update', $corte), [
                'fecha_corte' => '2024-02-01',
                'fuente'      => 'Fuente Actualizada',
                'descripcion' => 'Descripción nueva',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('lista_nominal_cortes', [
            'id'          => $corte->id,
            'fuente'      => 'Fuente Actualizada',
            'descripcion' => 'Descripción nueva',
        ]);
    }

    public function test_cannot_delete_active_corte(): void
    {
        $corteActivo = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente'      => 'Fuente Oficial',
            'is_active'   => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.lista-nominal.cortes.destroy', $corteActivo));

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('lista_nominal_cortes', ['id' => $corteActivo->id]);
    }

    public function test_can_delete_inactive_corte(): void
    {
        $corteInactivo = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente'      => 'Fuente Obsoleta',
            'is_active'   => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.lista-nominal.cortes.destroy', $corteInactivo));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('lista_nominal_cortes', ['id' => $corteInactivo->id]);
    }

    public function test_unauthenticated_user_cannot_access_cortes(): void
    {
        $response = $this->getJson(route('admin.lista-nominal.cortes.index'));
        $response->assertStatus(401);
    }

    public function test_unauthorized_role_cannot_access_cortes(): void
    {
        $brigadista = User::factory()->create([
            'role' => UserRole::DESDOBLE,
        ]);

        $response = $this->actingAs($brigadista)
            ->getJson(route('admin.lista-nominal.cortes.index'));

        $response->assertStatus(403);
    }
}
