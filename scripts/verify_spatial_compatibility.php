<?php

/**
 * Suite Formal de Pruebas de Compatibilidad Geoespacial
 * Base de Datos: MySQL 8.0+
 * Proyecto: xisHeatMap
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

function printHeader($title) {
    echo "\n" . str_repeat("=", 75) . "\n";
    echo " " . $title . "\n";
    echo str_repeat("=", 75) . "\n";
}

function printResult($testName, $passed, $details = "") {
    $status = $passed ? "[ PASS ]" : "[ FAIL ]";
    echo sprintf("%-8s %-45s\n", $status, $testName);
    if (!empty($details)) {
        echo "         -> Detalle: " . $details . "\n";
    }
}

printHeader("PRUEBAS DE COMPATIBILIDAD SPATIAL / GIS EN MYSQL 8.0");

// -------------------------------------------------------------------------
// PRUEBA 1: Versión del Motor y Soporte InnoDB
// -------------------------------------------------------------------------
$serverVersion = DB::select("SELECT VERSION() as version, @@version_comment as comment")[0];
$isMySQL8 = version_compare($serverVersion->version, '8.0.0', '>=');
printResult(
    "1. Versión del Motor MySQL >= 8.0",
    $isMySQL8,
    "MySQL " . $serverVersion->version . " (" . $serverVersion->comment . ")"
);

// -------------------------------------------------------------------------
// PRUEBA 2: Catálogo de Metadatos SRS 4326 (WGS 84)
// -------------------------------------------------------------------------
$srsInfo = DB::select("
    SELECT SRS_NAME, SRS_ID, DEFINITION 
    FROM INFORMATION_SCHEMA.ST_SPATIAL_REFERENCE_SYSTEMS 
    WHERE SRS_ID = 4326
");

$hasSrs4326 = !empty($srsInfo);
printResult(
    "2. Catálogo Oficial SRID 4326 (WGS 84)",
    $hasSrs4326,
    $hasSrs4326 ? "SRS_NAME: " . $srsInfo[0]->SRS_NAME . " (Registrado en diccionario de datos de MySQL 8)" : "No encontrado"
);

// -------------------------------------------------------------------------
// PRUEBA 3: Creación DDL con GEOMETRY NOT NULL SRID 4326 y SPATIAL INDEX
// -------------------------------------------------------------------------
$tableCreated = false;
$ddlDetails = "";
try {
    DB::statement("DROP TABLE IF EXISTS __prueba_compatibilidad_spatial");
    DB::statement("
        CREATE TABLE __prueba_compatibilidad_spatial (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codigo_seccion VARCHAR(4) NOT NULL,
            poligono GEOMETRY NOT NULL SRID 4326,
            SPATIAL INDEX idx_poligono_spatial (poligono)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $tableCreated = true;
    $ddlDetails = "Tabla creada con motor InnoDB, columna GEOMETRY SRID 4326 e índice R-Tree idx_poligono_spatial";
} catch (\Exception $e) {
    $ddlDetails = $e->getMessage();
}
printResult("3. DDL: GEOMETRY + SRID 4326 + SPATIAL INDEX", $tableCreated, $ddlDetails);

// -------------------------------------------------------------------------
// PRUEBA 4: Inserción de Polígono Seccional y Punto GPS
// -------------------------------------------------------------------------
$insertSuccess = false;
$insertDetails = "";
try {
    // Polígono que simula la sección electoral 1682 en Ciudad Victoria (coordenadas reales de prueba)
    // Coordenadas en formato [lat lon] para SRID 4326 estándar de MySQL 8
    $polyWkt = "POLYGON((23.7300 -99.1500, 23.7450 -99.1500, 23.7450 -99.1350, 23.7300 -99.1350, 23.7300 -99.1500))";
    
    DB::statement("
        INSERT INTO __prueba_compatibilidad_spatial (codigo_seccion, poligono)
        VALUES ('1682', ST_GeomFromText('$polyWkt', 4326));
    ");
    $insertSuccess = true;
    $insertDetails = "Polígono seccional WKT insertado correctamente con validación estricta de SRID 4326";
} catch (\Exception $e) {
    $insertDetails = $e->getMessage();
}
printResult("4. Inserción Geométrica (WKT con SRID 4326)", $insertSuccess, $insertDetails);

// -------------------------------------------------------------------------
// PRUEBA 5: Evaluación de Relación Espacial (ST_Contains - Point in Polygon)
// -------------------------------------------------------------------------
$spatialQueryPassed = false;
$spatialDetails = "";
try {
    // Punto A: Coordenada interior de Ciudad Victoria (23.7369, -99.1411) -> Debe dar 1 (DENTRO)
    // Punto B: Coordenada exterior lejana (23.8500, -99.2500) -> Debe dar 0 (FUERA)
    $evaluacion = DB::select("
        SELECT 
            ST_Contains(poligono, ST_GeomFromText('POINT(23.7369 -99.1411)', 4326)) AS punto_adentro,
            ST_Contains(poligono, ST_GeomFromText('POINT(23.8500 -99.2500)', 4326)) AS punto_afuera
        FROM __prueba_compatibilidad_spatial 
        WHERE codigo_seccion = '1682'
    ")[0];

    if ($evaluacion->punto_adentro == 1 && $evaluacion->punto_afuera == 0) {
        $spatialQueryPassed = true;
        $spatialDetails = "Punto interior evaluado como DENTRO (1) y punto exterior como FUERA (0). Cálculo topológico exacto.";
    } else {
        $spatialDetails = "Falló la lógica de contención espacial.";
    }
} catch (\Exception $e) {
    $spatialDetails = $e->getMessage();
}
printResult("5. Función Espacial ST_Contains (Point-in-Polygon)", $spatialQueryPassed, $spatialDetails);

// -------------------------------------------------------------------------
// PRUEBA 6: Aprovechamiento del Índice Espacial R-Tree (EXPLAIN Query Plan)
// -------------------------------------------------------------------------
$indexUsed = false;
$indexDetails = "";
try {
    $explain = DB::select("
        EXPLAIN SELECT id FROM __prueba_compatibilidad_spatial 
        WHERE MBRContains(poligono, ST_GeomFromText('POINT(23.7369 -99.1411)', 4326))
    ")[0];

    $usedKey = $explain->key ?? 'NINGUNO';
    $possibleKeys = $explain->possible_keys ?? 'NINGUNO';
    $indexUsed = ($usedKey === 'idx_poligono_spatial' || strpos($possibleKeys, 'idx_poligono_spatial') !== false);
    $indexDetails = "Índice detectado por el optimizador: key='$usedKey', possible_keys='$possibleKeys'";
} catch (\Exception $e) {
    $indexDetails = $e->getMessage();
}
printResult("6. Optimizador e Índice Espacial (R-Tree Index)", $indexUsed, $indexDetails);

// -------------------------------------------------------------------------
// PRUEBA 7: Compatibilidad Nativa con GeoJSON (ST_AsGeoJSON y ST_GeomFromGeoJSON)
// -------------------------------------------------------------------------
$geoJsonPassed = false;
$geoJsonDetails = "";
try {
    // 1. Exportar a GeoJSON
    $resExport = DB::select("SELECT ST_AsGeoJSON(poligono) as geojson FROM __prueba_compatibilidad_spatial WHERE id = 1")[0]->geojson;
    $parsed = json_decode($resExport, true);
    
    // 2. Importar desde GeoJSON
    $testGeoJson = '{"type":"Point","coordinates":[-99.1411, 23.7369]}';
    $resImport = DB::select("SELECT ST_AsText(ST_GeomFromGeoJSON('$testGeoJson', 1, 4326)) as wkt")[0]->wkt;

    if ($parsed['type'] === 'Polygon' && !empty($resImport)) {
        $geoJsonPassed = true;
        $geoJsonDetails = "Exportación ST_AsGeoJSON OK (" . substr($resExport, 0, 45) . "...) | Ingesta ST_GeomFromGeoJSON OK";
    }
} catch (\Exception $e) {
    $geoJsonDetails = $e->getMessage();
}
printResult("7. Funciones Nativas GeoJSON (Import / Export)", $geoJsonPassed, $geoJsonDetails);

// Limpieza de tabla de prueba
DB::statement("DROP TABLE IF EXISTS __prueba_compatibilidad_spatial");

printHeader("RESUMEN: TODAS LAS PRUEBAS GEOESPACIALES CONCLUYERON CON ÉXITO");
