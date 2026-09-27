<?php

namespace Tests\Feature;

use App\Models\ListaNominalCorte;
use App\Models\ListaNominalDetalle;
use App\Models\SeccionElectoral;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListaNominalTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Helper para obtener una sección electoral existente o crear una con polígono WGS84 válido.
     */
    protected function getOrCreateSeccion(): SeccionElectoral
    {
        $seccion = SeccionElectoral::first();
        if ($seccion) {
            return $seccion;
        }

        $seccion = new SeccionElectoral([
            'entidad' => 28,
            'municipio' => 41,
            'seccion' => '9999',
            'distrito_federal' => 5,
            'distrito_local' => 14,
            'tipo' => 1,
            'control' => 1,
        ]);
        $seccion->poligono = DB::raw("ST_GeomFromText('POLYGON((-99.16 23.70, -99.12 23.70, -99.12 23.76, -99.16 23.76, -99.16 23.70))', 4326)");
        $seccion->save();

        return $seccion;
    }

    /**
     * Prueba los scopes active() y latestFirst() de ListaNominalCorte.
     */
    public function test_scopes_active_y_latest_first_en_lista_nominal_corte(): void
    {
        $corteAntiguo = ListaNominalCorte::create([
            'fecha_corte' => '2023-06-30',
            'fuente' => 'INE RFE 2023',
            'descripcion' => 'Corte histórico',
            'is_active' => false,
        ]);

        $corteMedio = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-15',
            'fuente' => 'INE RFE 2024 Preliminar',
            'descripcion' => 'Corte intermedio',
            'is_active' => false,
        ]);

        $corteReciente = ListaNominalCorte::create([
            'fecha_corte' => '2024-06-30',
            'fuente' => 'INE RFE 2024 Definitivo',
            'descripcion' => 'Corte activo',
            'is_active' => true,
        ]);

        // Validar scope active()
        $activos = ListaNominalCorte::active()->whereIn('id', [$corteAntiguo->id, $corteMedio->id, $corteReciente->id])->get();
        $this->assertCount(1, $activos);
        $this->assertEquals($corteReciente->id, $activos->first()->id);
        $this->assertTrue($activos->first()->is_active);

        // Validar scope latestFirst() (orden cronológico descendente por fecha_corte)
        $ordenados = ListaNominalCorte::latestFirst()->whereIn('id', [$corteAntiguo->id, $corteMedio->id, $corteReciente->id])->get();
        $fechasOrdenadas = $ordenados->pluck('fecha_corte')->map->format('Y-m-d')->values()->toArray();
        $this->assertEquals(['2024-06-30', '2024-01-15', '2023-06-30'], $fechasOrdenadas);
    }

    /**
     * Prueba las relaciones básicas:
     * - ListaNominalCorte -> detalles (hasMany)
     * - ListaNominalDetalle -> corte (belongsTo)
     * - ListaNominalDetalle -> seccionElectoral (belongsTo)
     * - SeccionElectoral -> listaNominalDetalles (hasMany)
     */
    public function test_relaciones_basicas_entre_corte_detalle_y_seccion(): void
    {
        $seccion = $this->getOrCreateSeccion();

        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-03-31',
            'fuente' => 'INE RFE',
            'is_active' => true,
        ]);

        $detalle = ListaNominalDetalle::create([
            'lista_nominal_corte_id' => $corte->id,
            'seccion_electoral_id' => $seccion->id,
            'total_lista_nominal' => 1850,
            'padron_electoral' => 1900,
            'hombres' => 900,
            'mujeres' => 950,
            'no_binario' => 0,
        ]);

        // Corte -> detalles (hasMany)
        $corteConDetalles = ListaNominalCorte::with('detalles')->find($corte->id);
        $this->assertTrue($corteConDetalles->detalles->contains('id', $detalle->id));

        // Detalle -> corte (belongsTo)
        $this->assertNotNull($detalle->corte);
        $this->assertEquals($corte->id, $detalle->corte->id);

        // Detalle -> seccionElectoral (belongsTo)
        $this->assertNotNull($detalle->seccionElectoral);
        $this->assertEquals($seccion->id, $detalle->seccionElectoral->id);

        // SeccionElectoral -> listaNominalDetalles (hasMany)
        $seccionConDetalles = SeccionElectoral::with('listaNominalDetalles')->find($seccion->id);
        $this->assertTrue($seccionConDetalles->listaNominalDetalles->contains('id', $detalle->id));
    }

    /**
     * Valida exhaustivamente que listaNominalActiva() devuelva ÚNICAMENTE
     * el detalle que pertenece al corte activo (is_active = true), incluso cuando
     * existen múltiples cortes históricos asociados a la misma sección.
     */
    public function test_lista_nominal_activa_devuelve_unicamente_el_detalle_del_corte_activo(): void
    {
        $seccion = $this->getOrCreateSeccion();

        // 1. Crear dos cortes: uno INACTIVO (histórico) y uno ACTIVO (vigente)
        $corteInactivo = ListaNominalCorte::create([
            'fecha_corte' => '2021-06-06',
            'fuente' => 'INE Proceso 2021',
            'is_active' => false,
        ]);

        $corteActivo = ListaNominalCorte::create([
            'fecha_corte' => '2024-06-02',
            'fuente' => 'INE Proceso 2024',
            'is_active' => true,
        ]);

        // 2. Asociar dos detalles a la MISMA sección: uno por cada corte
        $detalleInactivo = ListaNominalDetalle::create([
            'lista_nominal_corte_id' => $corteInactivo->id,
            'seccion_electoral_id' => $seccion->id,
            'total_lista_nominal' => 1200, // Cifra del corte viejo
            'padron_electoral' => 1250,
            'hombres' => 600,
            'mujeres' => 600,
            'no_binario' => 0,
        ]);

        $detalleActivo = ListaNominalDetalle::create([
            'lista_nominal_corte_id' => $corteActivo->id,
            'seccion_electoral_id' => $seccion->id,
            'total_lista_nominal' => 2500, // Cifra del corte nuevo
            'padron_electoral' => 2600,
            'hombres' => 1200,
            'mujeres' => 1300,
            'no_binario' => 0,
        ]);

        // 3. Consultar la sección mediante eager loading con listaNominalActiva
        $seccionConsultada = SeccionElectoral::with('listaNominalActiva')->find($seccion->id);

        $this->assertNotNull($seccionConsultada->listaNominalActiva, 'La relación listaNominalActiva no debe ser nula.');
        $this->assertEquals(
            $detalleActivo->id,
            $seccionConsultada->listaNominalActiva->id,
            'Debe devolver el ID del detalle del corte ACTIVO y no del inactivo.'
        );
        $this->assertEquals(
            2500,
            $seccionConsultada->listaNominalActiva->total_lista_nominal,
            'Debe reflejar las cifras del corte activo (2500).'
        );
        $this->assertNotEquals(
            $detalleInactivo->id,
            $seccionConsultada->listaNominalActiva->id,
            'No debe asociar el detalle del corte inactivo.'
        );

        // 4. Alternancia dinámica: desactivar corte activo y activar el anterior
        $corteActivo->update(['is_active' => false]);
        $corteInactivo->update(['is_active' => true]);

        // Volver a consultar la sección
        $seccionAlternada = SeccionElectoral::with('listaNominalActiva')->find($seccion->id);

        $this->assertNotNull($seccionAlternada->listaNominalActiva);
        $this->assertEquals(
            $detalleInactivo->id,
            $seccionAlternada->listaNominalActiva->id,
            'Al alternar la vigencia del corte, la relación debe resolver el detalle del corte ahora activo.'
        );
        $this->assertEquals(
            1200,
            $seccionAlternada->listaNominalActiva->total_lista_nominal,
            'Debe reflejar la cifra del corte reactivado (1200).'
        );

        // 5. Escenario sin corte activo: ambos desactivados
        $corteInactivo->update(['is_active' => false]);

        $seccionSinActivo = SeccionElectoral::with('listaNominalActiva')->find($seccion->id);
        $this->assertNull(
            $seccionSinActivo->listaNominalActiva,
            'Si ningún corte está activo, la relación debe retornar null de manera limpia.'
        );
    }
}
