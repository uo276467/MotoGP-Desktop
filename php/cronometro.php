<?php

require_once __DIR__ . '/Cronometro.class.php';

session_start();

if (!isset($_SESSION['cronometro']) || !($_SESSION['cronometro'] instanceof Cronometro)) {
    $_SESSION['cronometro'] = new Cronometro();
}

$cronometro = $_SESSION['cronometro'];
$tiempoMostrado = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST['accion'] ?? '';

    if ($accion === "arrancar") {

        $cronometro = new Cronometro();
        $cronometro->arrancar();
        $_SESSION['cronometro'] = $cronometro;

    } elseif ($accion === "parar") {

        $cronometro->parar();
        $_SESSION['cronometro'] = $cronometro;

    } elseif ($accion === "mostrar") {

        $tiempoMostrado = $cronometro->mostrar();
    }
}
?>
<!DOCTYPE HTML>
<html lang="es">
<head>
    <meta charset="UTF-8" />

    <meta name="author" content="Daniel Suárez de la Roza"/>
    <meta name="description" content="Cronómetro en PHP"/>
    <meta name="keywords" content="MotoGP, cronometro, PHP"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css" />
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css" />

    <link rel="icon" href="../multimedia/favicon.ico" />
    <title>Cronómetro</title>
</head>

<body>
<header>
    <h1><a href="index.html" title="Página de inicio">MotoGP Desktop</a></h1>
    <nav>
        <a href="../index.html" title="Página de inicio">Inicio</a>
        <a href="../piloto.html" title="Información del piloto">Piloto</a>
        <a href="../circuito.html" title="Información del circuito">Circuito</a>
        <a href="../meteorologia.html" title="Información meteorológica">Meteorología</a>
        <a href="../clasificaciones.php" title="Información sobre clasificaciones">Clasificaciones</a>
        <a href="../juegos.html" class="active" title="Página de juegos">Juegos</a>
        <a href="../ayuda.html" title="Manual de ayuda de MotoGP-Desktop">Ayuda</a>
    </nav>
</header>

<p>Estás en: <a href="../index.html" title="Página de inicio">Inicio</a> >> <a href="../juegos.html" title="Juegos">Juegos</a> >> <strong>Cronómetro PHP</strong></p>

<main>
    <h2>Cronómetro PHP</h2>

    <form method="post">
        <p>
            <button type="submit" name="accion" value="arrancar">Arrancar</button>
            <button type="submit" name="accion" value="parar">Parar</button>
            <button type="submit" name="accion" value="mostrar">Mostrar</button>
        </p>
    </form>

    <?php if ($tiempoMostrado !== ""): ?>
        <p><?php echo $tiempoMostrado; ?></p>
    <?php endif; ?>

</main>

</body>
</html>