<?php

namespace Tests\Feature;

use App\Models\ListaNominalCorte;
use App\Models\ListaNominalDetalle;
use App\Models\SeccionElectoral;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * BE-4: comando php artisan lista-nominal:import
 */
class ListaNominalImportCommandTest extends TestCase
{
    use RefreshDatabase;

    protected array $archivos = [];

    protected function tearDown(): void
    {
        foreach ($this->archivos as $archivo) {
            @unlink($archivo);
        }
        parent::tearDown();
    }

    protected function crearSeccion(string $seccion): SeccionElectoral
    {
        return SeccionElectoral::create([
            'entidad' => 28,
            'municipio' => 41,
            'seccion' => $seccion,
            'distrito_federal' => 5,
            'distrito_local' => 14,
            'tipo' => 1,
            'poligono' => DB::raw("ST_GeomFromText('POLYGON((-99.16 23.70, -99.12 23.70, -99.12 23.76, -99.16 23.76, -99.16 23.70))', 4326)"),
        ]);
    }

    protected function crearCsv(array $lineas): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'ln_cmd_') . '.csv';
        file_put_contents($ruta, implode("\n", $lineas));
        $this->archivos[] = $ruta;

        return $ruta;
    }

    protected function csvValido(): string
    {
        return $this->crearCsv([
            'seccion,total_lista_nominal,padron_electoral',
            '1563,1850,1900',
            '1564,2100,2150',
            '9999,500,520',
        ]);
    }

    public function test_crea_el_corte_e_importa_las_secciones(): void
    {
        $s1 = $this->crearSeccion('1563');
        $this->crearSeccion('1564');

        $this->artisan('lista-nominal:import', [
            'archivo' => $this->csvValido(),
            '--fecha' => '2024-03-31',
            '--fuente' => 'INE',
            '--descripcion' => 'Corte definitivo 2024',
        ])
            ->expectsOutputToContain('Corte #')
            ->expectsOutputToContain('9999')
            ->expectsOutputToContain('El corte no está activo')
            ->assertSuccessful();

        $corte = ListaNominalCorte::sole();
        $this->assertSame('2024-03-31', $corte->fecha_corte->format('Y-m-d'));
        $this->assertSame('INE', $corte->fuente);
        $this->assertSame('Corte definitivo 2024', $corte->descripcion);
        $this->assertFalse($corte->is_active);
        $this->assertSame(2, $corte->detalles()->count());
        $this->assertDatabaseHas('lista_nominal_detalles', [
            'lista_nominal_corte_id' => $corte->id,
            'seccion_electoral_id' => $s1->id,
            'total_lista_nominal' => 1850,
        ]);
    }

    public function test_activar_deja_un_solo_corte_activo(): void
    {
        $this->crearSeccion('1563');
        $anterior = ListaNominalCorte::create(['fecha_corte' => '2021-03-31', 'fuente' => 'INE', 'is_active' => true]);

        $this->artisan('lista-nominal:import', [
            'archivo' => $this->csvValido(),
            '--fecha' => '2024-03-31',
            '--fuente' => 'INE',
            '--activar' => true,
        ])->expectsOutputToContain('activado')->assertSuccessful();

        $nuevo = ListaNominalCorte::whereDate('fecha_corte', '2024-03-31')->sole();
        $this->assertTrue($nuevo->is_active);
        $this->assertFalse($anterior->fresh()->is_active);
        $this->assertSame(1, ListaNominalCorte::where('is_active', true)->count());
    }

    public function test_reejecutar_con_la_misma_fecha_y_fuente_actualiza_sin_duplicar(): void
    {
        $s1 = $this->crearSeccion('1563');
        $args = ['archivo' => $this->csvValido(), '--fecha' => '2024-03-31', '--fuente' => 'INE'];

        $this->artisan('lista-nominal:import', $args)->assertSuccessful();

        $csvCorregido = $this->crearCsv(['seccion,total_lista_nominal', '1563,1900']);
        $this->artisan('lista-nominal:import', ['archivo' => $csvCorregido] + $args)
            ->expectsOutputToContain('Ya existe el corte')
            ->assertSuccessful();

        $this->assertSame(1, ListaNominalCorte::count());
        $this->assertSame(1, ListaNominalDetalle::count());
        $this->assertDatabaseHas('lista_nominal_detalles', ['seccion_electoral_id' => $s1->id, 'total_lista_nominal' => 1900]);
    }

    public function test_si_la_importacion_falla_no_deja_un_corte_vacio(): void
    {
        $this->crearSeccion('1563');
        $csvSinColumna = $this->crearCsv(['seccion,otra_columna', '1563,10']);

        $this->artisan('lista-nominal:import', [
            'archivo' => $csvSinColumna,
            '--fecha' => '2024-03-31',
            '--activar' => true,
        ])
            ->expectsOutputToContain('La importación falló')
            ->assertFailed();

        $this->assertSame(0, ListaNominalCorte::count());
    }

    public function test_no_activa_un_corte_sin_secciones(): void
    {
        $this->crearSeccion('1563');
        $csvSoloInexistentes = $this->crearCsv(['seccion,total_lista_nominal', '9999,500']);

        $this->artisan('lista-nominal:import', [
            'archivo' => $csvSoloInexistentes,
            '--fecha' => '2024-03-31',
            '--activar' => true,
        ])
            ->expectsOutputToContain('no se activó')
            ->assertFailed();

        $this->assertSame(0, ListaNominalCorte::where('is_active', true)->count());
    }

    public function test_valida_archivo_y_fecha(): void
    {
        $this->artisan('lista-nominal:import', ['archivo' => '/no/existe.csv', '--fecha' => '2024-03-31'])
            ->expectsOutputToContain('No se encontró')
            ->assertFailed();

        $this->artisan('lista-nominal:import', ['archivo' => $this->csvValido()])
            ->expectsOutputToContain('--fecha=YYYY-MM-DD')
            ->assertFailed();

        $this->artisan('lista-nominal:import', ['archivo' => $this->csvValido(), '--fecha' => '2024-02-30'])
            ->expectsOutputToContain('--fecha=YYYY-MM-DD')
            ->assertFailed();

        $this->assertSame(0, ListaNominalCorte::count());
    }
}
