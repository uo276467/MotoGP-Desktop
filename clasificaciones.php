<?php
class Clasificacion {

    private string $documento;

    public function __construct() {
        $this->documento = "./xml/circuitoEsquema.xml";
    }

    public function consultar(): void {
        $datos = file_get_contents($this->documento);

        if ($datos === false) {
            echo "<p>Error al leer el archivo XML.</p>";
            return;
        }

        $xml = new SimpleXMLElement($datos);

        $ganadorNombre = (string) $xml->vencedor->attributes()['nombre'];
        $ganadorTiempo = (string) $xml->vencedor->duracion;
        $top3 = $xml->top3->piloto;

        echo "<section>";
        echo "<h3>Ganador de la carrera</h3>";
        echo "<p>Nombre: $ganadorNombre</p>";
        echo "<p>Tiempo empleado: $ganadorTiempo</p>";
        echo "</section>";

        echo "<section>";
        echo "<h3>Clasificación del mundial tras la carrera</h3>";

        echo "<ol>";
        foreach ($top3 as $piloto) {
            $puesto = (string) $piloto['puesto'];
            $puntos = (string) $piloto['puntos'];
            $nombre = (string) $piloto;

            echo "<li>";
            echo "$puesto. $nombre - $puntos puntos";
            echo "</li>";
        }
        echo "</ol>";

        echo "</section>";
    }
}
?>

<!DOCTYPE HTML>

<html lang="es">
<head>
    <meta charset="UTF-8" />

    <meta name="author" content="Daniel Suárez de la Roza"/>
    <meta name="description" content="Página de clasificaciones"/>
    <meta name="keywords" content="MotoGP, carreras, clasificación, ranking, posición, resultados"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <link rel="stylesheet" type="text/css" href="estilo/estilo.css" />
    <link rel="stylesheet" type="text/css" href="estilo/layout.css" />

    <title>MotoGP-Clasificaciones</title>
    <link rel="icon" href="multimedia/favicon.ico" />
</head>

<body>
    <header>
        <h1><a href="index.html" title="Página de inicio">MotoGP Desktop</a></h1>
        <nav>
            <a href="index.html" title="Página de inicio">Inicio</a>
            <a href="piloto.html" title="Información del piloto">Piloto</a>
            <a href="circuito.html" title="Información del circuito">Circuito</a>
            <a href="meteorologia.html" title="Información meteorológica">Meteorología</a>
            <a href="clasificaciones.php" class="active" title="Información sobre clasificaciones">Clasificaciones</a>
            <a href="juegos.html" title="Página de juegos">Juegos</a>
            <a href="ayuda.html" title="Manual de ayuda de MotoGP-Desktop">Ayuda</a>
        </nav>
    </header>

    <p>Estás en: <a href="index.html" title="Página de inicio">Inicio</a> >> <strong>Clasificaciones</strong></p>

    <main>
        <h2>Clasificaciones</h2>
        <?php
            $clasificacion = new Clasificacion();
            $clasificacion->consultar();
        ?>
    </main>
    
</body>
</html>