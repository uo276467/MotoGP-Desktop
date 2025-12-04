<?php
class Configuracion {

    private mysqli $conn;
    private string $dbName = "UO276467_DB";

    public function __construct() {
        $this->conn = new mysqli("localhost", "DBUSER2025", "DBPSWD2025");

        if ($this->conn->connect_error) {
            die("Error de conexión a MySQL: " . $this->conn->connect_error);
        }
    }

    // Selecciona la base de datos UO276467_DB
    private function seleccionarBaseDatos(): void {
        if (!$this->conn->select_db($this->dbName)) {
            die("No se ha podido seleccionar la base de datos {$this->dbName}. ¿Existe?");
        }
    }

    
    // Reiniciar base de datos
    public function reiniciarBaseDatos(): void {
        $this->seleccionarBaseDatos();

        // Desactivar comprobación de claves foráneas para poder truncar en cualquier orden
        $this->conn->query("SET FOREIGN_KEY_CHECKS = 0");

        $resultado = $this->conn->query("SHOW TABLES");
        if ($resultado) {
            while ($fila = $resultado->fetch_array()) {
                $tabla = $fila[0];
                $this->conn->query("TRUNCATE TABLE `$tabla`");
            }
            $resultado->free();
        }

        // Volver a activar la comprobación de claves foráneas
        $this->conn->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    // Eliminar base de datos
    public function eliminarBaseDatos(): void {
        // No es necesario seleccionar la BD para hacer DROP
        $sql = "DROP DATABASE IF EXISTS `{$this->dbName}`";
        if (!$this->conn->query($sql)) {
            die("Error al eliminar la base de datos: " . $this->conn->error);
        }
    }

    
    // Exportar datos en formato CSV:
    public function exportarCSV(): void {
        $this->seleccionarBaseDatos();

        $nombreFichero = $this->dbName . "_export_" . date("Ymd_His") . ".csv";

        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$nombreFichero\"");

        $salida = fopen("php://output", "w");

        if (!$salida) {
            die("No se ha podido abrir el flujo de salida para el CSV.");
        }

        $resultadoTablas = $this->conn->query("SHOW TABLES");

        if ($resultadoTablas) {
            while ($filaTabla = $resultadoTablas->fetch_array()) {
                $tabla = $filaTabla[0];

                // Fila indicando el inicio de una tabla
                fputcsv($salida, ["#TABLE", $tabla]);

                // Obtener nombres de columnas
                $resultadoColumnas = $this->conn->query("SHOW COLUMNS FROM `$tabla`");
                $columnas = [];
                if ($resultadoColumnas) {
                    while ($col = $resultadoColumnas->fetch_assoc()) {
                        $columnas[] = $col["Field"];
                    }
                    $resultadoColumnas->free();
                }

                // Escribir cabecera de columnas
                fputcsv($salida, $columnas);

                // Obtener datos de la tabla
                $resultadoDatos = $this->conn->query("SELECT * FROM `$tabla`");
                if ($resultadoDatos) {
                    while ($filaDatos = $resultadoDatos->fetch_assoc()) {
                        // Ordenar los valores según el orden de las columnas
                        $valores = [];
                        foreach ($columnas as $colName) {
                            $valores[] = $filaDatos[$colName];
                        }
                        fputcsv($salida, $valores);
                    }
                    $resultadoDatos->free();
                }

                fputcsv($salida, []);
            }

            $resultadoTablas->free();
        }

        fclose($salida);
        exit();
    }
}


$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $config = new Configuracion();

    if (isset($_POST["accion"])) {
        switch ($_POST["accion"]) {
            case "reiniciar":
                $config->reiniciarBaseDatos();
                $mensaje = "La base de datos se ha reiniciado correctamente (todas las tablas vaciadas).";
                break;
            case "eliminar":
                $config->eliminarBaseDatos();
                $mensaje = "La base de datos se ha eliminado correctamente.";
                break;
            case "exportar":
                // Este método ya hace exit(), no se llega a mostrar HTML
                $config->exportarCSV();
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta name="author" content="Daniel Suárez de la Roza"/>
    <meta name="description" content="Configuración base de datos de tests"/>
    <meta name="keywords" content="MotoGP, configuración, base de datos, test, usabilidad"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css" />
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css" />

    <title>MotoGP-Configuración Test</title>
    <link rel="icon" href="../multimedia/favicon.ico" />

</head>
<body>
    <h1>Configuración de la Base de Datos (UO276467_DB)</h1>

    <section>
        <h2>Reiniciar base de datos</h2>
        <p>Vacía todas las tablas de la base de datos, pero mantiene la estructura.</p>
        <form method="post">
            <input type="hidden" name="accion" value="reiniciar">
            <button type="submit">Reiniciar base de datos</button>
        </form>
    </section>

    <section>
        <h2>Eliminar base de datos</h2>
        <p class="aviso">
            ¡Atención! Esta operación eliminará por completo la base de datos
            <strong>UO276467_DB</strong> (tablas y datos). Tendrás que volver a crearla.
        </p>
        <form method="post">
            <input type="hidden" name="accion" value="eliminar">
            <button type="submit">Eliminar base de datos</button>
        </form>
    </section>

    <section>
        <h2>Exportar datos a CSV</h2>
        <p>Exporta el contenido de todas las tablas de la base de datos a un archivo .csv.</p>
        <form method="post">
            <input type="hidden" name="accion" value="exportar">
            <button type="submit">Exportar CSV</button>
        </form>
    </section>

    <?php if (!empty($mensaje)) : ?>
        <section>
            <h2>Acción realizada</h2>
            <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
        </section>
    <?php endif; ?>
</body>
</html>