<?php
require_once __DIR__ . '/Cronometro.class.php';

class Test
{
    // Estado
    private string $estadoPrueba;

    // Errores
    private array $errores = [];

    // Variables de formulario
    private string $idUsuario = "";
    private string $edad = "";
    private string $genero = "";
    private string $profesion = "";
    private string $pericia = "";
    private string $dispositivo = "";

    private string $comentariosUsuarioForm = "";
    private string $propuestasMejora = "";
    private string $valoracion = "";
    private string $tareaCompletadaForm = "";
    private string $comentariosFacilitador = "";

    // Respuestas del test
    private array $preguntas = [];

    public function __construct()
    {
        session_start();
        $this->initSessionState();
        $this->initCronometro();
        $this->initFormVariables();
    }

    public function run(): void
    {
        $this->handleRequest();
        $this->render();
    }

    private function initSessionState(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $_SESSION['estado_prueba'] = 'inicial';

            unset(
                $_SESSION['cronometro'],
                $_SESSION['tiempo_segundos'],
                $_SESSION['respuestas']
            );
        }

        // Cargar estado actual
        $this->estadoPrueba = $_SESSION['estado_prueba'] ?? 'inicial';
    }

    private function initCronometro(): void
    {
        if (!isset($_SESSION['cronometro']) || !($_SESSION['cronometro'] instanceof Cronometro)) {
            $_SESSION['cronometro'] = new Cronometro();
        }
    }

    private function initFormVariables(): void
    {
        // Variables de formulario
        $this->idUsuario = (string)($_POST['id_usuario'] ?? ($_SESSION['id_usuario'] ?? ""));
        $this->edad = (string)($_POST['edad'] ?? "");
        $this->genero = (string)($_POST['genero'] ?? "");
        $this->profesion = (string)($_POST['profesion'] ?? "");
        $this->pericia = (string)($_POST['pericia_informatica'] ?? "");
        $this->dispositivo = (string)($_POST['dispositivo'] ?? ($_SESSION['dispositivo'] ?? ""));

        $this->comentariosUsuarioForm = (string)($_POST['comentarios_usuario'] ?? "");
        $this->propuestasMejora = (string)($_POST['propuestas_mejora'] ?? "");
        $this->valoracion = (string)($_POST['valoracion'] ?? "");
        $this->tareaCompletadaForm = (string)($_POST['tarea_completada'] ?? "");
        $this->comentariosFacilitador = (string)($_POST['comentarios_facilitador'] ?? "");

        // Respuestas de las preguntas
        $this->preguntas = [];
        for ($i = 1; $i <= 10; $i++) {
            $this->preguntas[$i] = $_POST["p$i"] ?? ($_SESSION['respuestas'][$i] ?? "");
        }
    }

    private function handleRequest(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }

        $accion = $_POST['accion'] ?? '';

        if ($accion === 'empezar') {
            $this->accionEmpezar();
        } elseif ($accion === 'terminar_test') {
            $this->accionTerminarTest();
        } elseif ($accion === 'guardar_resultados') {
            $this->accionGuardarResultados();
        }
    }

    private function accionEmpezar(): void
    {
        // Validación campos de Usuario
        $idUsuario = trim($_POST['id_usuario'] ?? "");
        $edad = trim($_POST['edad'] ?? "");
        $genero = $_POST['genero'] ?? "";
        $profesion = trim($_POST['profesion'] ?? "");
        $pericia = trim($_POST['pericia_informatica'] ?? "");
        $dispositivo = $_POST['dispositivo'] ?? "";

        // id_usuario
        if ($idUsuario === "" || !ctype_digit($idUsuario)) {
            $this->errores[] = "El id de usuario debe ser un número entero.";
        } else {
            $idUsuarioInt = (int)$idUsuario;
            if ($idUsuarioInt < 1 || $idUsuarioInt > 13) {
                $this->errores[] = "El id de usuario debe estar entre 1 y 13.";
            }
        }

        // edad
        if ($edad === "" || !ctype_digit($edad)) {
            $this->errores[] = "Debe indicar la edad del usuario.";
        }

        // genero
        $generosValidos = ['Hombre', 'Mujer', 'Otro'];
        if (!in_array($genero, $generosValidos, true)) {
            $this->errores[] = "Debe seleccionar un género válido.";
        }

        // profesion
        if ($profesion === "") {
            $this->errores[] = "Debe indicar la profesión del usuario.";
        }

        // pericia_informatica (0-10)
        if ($pericia === "" || !ctype_digit($pericia)) {
            $this->errores[] = "Debe indicar la pericia informática (0-10).";
        } else {
            $periciaInt = (int)$pericia;
            if ($periciaInt < 0 || $periciaInt > 10) {
                $this->errores[] = "La pericia informática debe estar entre 0 y 10.";
            }
        }

        // dispositivo (ENUM 'Ordenador','Tableta','Telefono')
        $dispositivosValidos = ['Ordenador', 'Tableta', 'Telefono'];
        if (!in_array($dispositivo, $dispositivosValidos, true)) {
            $this->errores[] = "Debe seleccionar un dispositivo válido.";
        }

        if (!empty($this->errores)) {
            // Mantener estado
            $this->estadoPrueba = 'inicial';
            $_SESSION['estado_prueba'] = 'inicial';
            return;
        }

        // Si no hay errores, insertar usuario
        $conn = $this->dbConnect();

        // Insertar usuario
        $sql = "INSERT INTO Usuario (id_usuario, profesion, edad, genero, pericia_informatica)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    profesion = VALUES(profesion),
                    edad = VALUES(edad),
                    genero = VALUES(genero),
                    pericia_informatica = VALUES(pericia_informatica)";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error al preparar la sentencia de Usuario: " . $conn->error);
        }

        $idUsuarioInt = (int)$idUsuario;
        $edadInt = (int)$edad;
        $periciaInt = (int)$pericia;

        $stmt->bind_param(
            "isisi",
            $idUsuarioInt,
            $profesion,
            $edadInt,
            $genero,
            $periciaInt
        );

        if (!$stmt->execute()) {
            die("Error al guardar los datos del usuario: " . $stmt->error);
        }

        $stmt->close();
        $conn->close();

        // Guardar datos clave en sesión
        $_SESSION['id_usuario'] = $idUsuarioInt;
        $_SESSION['dispositivo'] = $dispositivo;

        // Reiniciar cronómetro y arrancar
        $cronometro = new Cronometro();
        $cronometro->arrancar();
        $_SESSION['cronometro'] = $cronometro;

        // Limpiar posibles respuestas anteriores
        unset($_SESSION['tiempo_segundos'], $_SESSION['respuestas']);

        // Pasar al test
        $_SESSION['estado_prueba'] = 'preguntas';
        $this->estadoPrueba = 'preguntas';

        $this->idUsuario = (string)$idUsuarioInt;
        $this->edad = (string)$edadInt;
        $this->genero = (string)$genero;
        $this->profesion = (string)$profesion;
        $this->pericia = (string)$periciaInt;
        $this->dispositivo = (string)$dispositivo;
    }

    private function accionTerminarTest(): void
    {
        // Solo validar que las respuestas no estén vacías
        for ($i = 1; $i <= 10; $i++) {
            $valor = trim($_POST["p$i"] ?? "");
            if ($valor === "") {
                $this->errores[] = "Debe contestar a la pregunta $i.";
            }
        }

        // Si hay errores, seguimos mostrando el test
        if (!empty($this->errores)) {
            $_SESSION['estado_prueba'] = 'preguntas';
            $this->estadoPrueba = 'preguntas';
            return;
        }

        // Parar cronómetro y guardar solo el tiempo
        $cronometro = $_SESSION['cronometro'] ?? null;
        $tiempoSegundos = 0;

        if ($cronometro instanceof Cronometro) {
            $cronometro->parar();
            $tiempoSegundos = (int) round($cronometro->getTiempoSegundos());
        }

        $_SESSION['tiempo_segundos'] = $tiempoSegundos;

        // Pasar al cuestionario posterior
        $_SESSION['estado_prueba'] = 'post_test';
        $this->estadoPrueba = 'post_test';
    }

    private function accionGuardarResultados(): void
    {
        $this->comentariosUsuarioForm = trim($_POST['comentarios_usuario'] ?? "");
        $this->propuestasMejora = trim($_POST['propuestas_mejora'] ?? "");
        $this->valoracion = trim($_POST['valoracion'] ?? "");
        $this->tareaCompletadaForm = $_POST['tarea_completada'] ?? "";
        $this->comentariosFacilitador = trim($_POST['comentarios_facilitador'] ?? "");

        if ($this->valoracion === "" || !ctype_digit($this->valoracion)) {
            $this->errores[] = "Debe indicar una valoración numérica (0-10).";
        } else {
            $valoracionInt = (int)$this->valoracion;
            if ($valoracionInt < 0 || $valoracionInt > 10) {
                $this->errores[] = "La valoración debe estar entre 0 y 10.";
            }
        }

        if (!in_array($this->tareaCompletadaForm, ['Si', 'No'], true)) {
            $this->errores[] = "Debe indicar si la tarea se ha completado o no.";
        }
        $tareaCompletada = ($this->tareaCompletadaForm === 'Si') ? 1 : 0;

        if ($this->comentariosFacilitador === "") {
            $this->errores[] = "El facilitador debe introducir comentarios.";
        }

        // Recuperar datos de sesión
        $idUsuario = $_SESSION['id_usuario'] ?? null;
        $dispositivo = $_SESSION['dispositivo'] ?? null;
        $tiempoSegundos = $_SESSION['tiempo_segundos'] ?? null;

        if ($idUsuario === null || $dispositivo === null || $tiempoSegundos === null) {
            $this->errores[] = "No hay datos suficientes en sesión. Reinicie el test.";
            echo "Debug: idUsuario=$idUsuario, dispositivo=$dispositivo, tiempoSegundos=$tiempoSegundos";
        }

        if (!empty($this->errores)) {
            $_SESSION['estado_prueba'] = 'post_test';
            $this->estadoPrueba = 'post_test';
            return;
        }

        $conn = $this->dbConnect();

        // Insertar en TestUsabilidad
        $sqlTest = "INSERT INTO TestUsabilidad
                        (id_usuario, dispositivo, tiempo_segundos, tarea_completada,
                         comentarios_usuario, propuestas_mejora, valoracion)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmtTest = $conn->prepare($sqlTest);
        if ($stmtTest === false) {
            die("Error al preparar la sentencia de TestUsabilidad: " . $conn->error);
        }

        $comentariosUsuarioBD = $this->comentariosUsuarioForm;

        $valoracionInt = (int)$this->valoracion;

        $stmtTest->bind_param(
            "isisssi",
            $idUsuario,
            $dispositivo,
            $tiempoSegundos,
            $tareaCompletada,
            $comentariosUsuarioBD,
            $this->propuestasMejora,
            $valoracionInt
        );

        if (!$stmtTest->execute()) {
            die("Error al guardar los datos en TestUsabilidad: " . $stmtTest->error);
        }

        $stmtTest->close();

        // Insertar en ObservacionFacilitador
        $sqlObs = "INSERT INTO ObservacionFacilitador (id_usuario, comentarios)
                   VALUES (?, ?)";

        $stmtObs = $conn->prepare($sqlObs);
        if ($stmtObs === false) {
            die("Error al preparar la sentencia de ObservacionFacilitador: " . $conn->error);
        }

        $stmtObs->bind_param("is", $idUsuario, $this->comentariosFacilitador);

        if (!$stmtObs->execute()) {
            die("Error al guardar los datos en ObservacionFacilitador: " . $stmtObs->error);
        }

        $stmtObs->close();
        $conn->close();

        // Limpiar datos de sesión relacionados con el test
        $_SESSION['estado_prueba'] = 'finalizada';
        $this->estadoPrueba = 'finalizada';

        unset($_SESSION['cronometro'], $_SESSION['tiempo_segundos']);
    }

    private function dbConnect(): mysqli
    {
        $conn = new mysqli(
            "localhost",
            "DBUSER2025",
            "DBPSWD2025",
            "UO276467_DB"
        );
        if ($conn->connect_error) {
            die("Error de conexión a la base de datos: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        return $conn;
    }

    private function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function render(): void
    {
        // Variables locales para mantener el HTML idéntico (solo cambian las fuentes)
        $estadoPrueba = $this->estadoPrueba;
        $errores = $this->errores;

        $idUsuario = $this->idUsuario;
        $edad = $this->edad;
        $genero = $this->genero;
        $profesion = $this->profesion;
        $pericia = $this->pericia;
        $dispositivo = $this->dispositivo;

        $comentariosUsuarioForm = $this->comentariosUsuarioForm;
        $propuestasMejora = $this->propuestasMejora;
        $valoracion = $this->valoracion;
        $tareaCompletadaForm = $this->tareaCompletadaForm;
        $comentariosFacilitador = $this->comentariosFacilitador;

        $preguntas = $this->preguntas;

        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>

    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css"/>
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css"/>

    <title>MotoGP-Usabilidad</title>
    <link rel="icon" href="../multimedia/favicon.ico" />
</head>
<body>

<main>
    <h1>Prueba de Usabilidad - MotoGP Desktop</h1>

    <?php if (!empty($errores)): ?>
        <article>
            <h2>Se han producido errores:</h2>
            <ul>
                <?php foreach ($errores as $error): ?>
                    <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
    <?php endif; ?>

    <?php if ($estadoPrueba === 'inicial'): ?>

        <!-- Paso 1: datos de usuario + dispositivo -->
        <section>
            <h2>Datos del usuario y configuración inicial</h2>
            <form method="post">
                <input type="hidden" name="accion" value="empezar"/>

                <p>
                    <label for="id_usuario">ID de usuario (1-13): </label>
                    <input type="number" id="id_usuario" name="id_usuario" min="1" max="13" required
                           value="<?php echo htmlspecialchars($idUsuario, ENT_QUOTES, 'UTF-8'); ?>"/>
                </p>

                <p>
                    <label for="edad">Edad: </label>
                    <input type="number" id="edad" name="edad" min="1" max="120" required
                           value="<?php echo htmlspecialchars($edad, ENT_QUOTES, 'UTF-8'); ?>"/>
                </p>

                <p>
                    <label for="genero">Género: </label>
                    <select id="genero" name="genero" required>
                        <option value="">-- Selecciona --</option>
                        <option value="Hombre" <?php echo ($genero === 'Hombre') ? 'selected' : ''; ?>>Hombre</option>
                        <option value="Mujer" <?php echo ($genero === 'Mujer') ? 'selected' : ''; ?>>Mujer</option>
                        <option value="Otro" <?php echo ($genero === 'Otro') ? 'selected' : ''; ?>>Otro</option>
                    </select>
                </p>

                <p>
                    <label for="profesion">Profesión: </label>
                    <input type="text" id="profesion" name="profesion" required
                           value="<?php echo htmlspecialchars($profesion, ENT_QUOTES, 'UTF-8'); ?>"/>
                </p>

                <p>
                    <label for="pericia_informatica">Pericia informática (0-10): </label>
                    <input type="number" id="pericia_informatica" name="pericia_informatica" min="0" max="10" required
                           value="<?php echo htmlspecialchars($pericia, ENT_QUOTES, 'UTF-8'); ?>"/>
                </p>

                <p>
                    <label for="dispositivo">Dispositivo para la prueba: </label>
                    <select id="dispositivo" name="dispositivo" required>
                        <option value="">-- Selecciona --</option>
                        <option value="Ordenador" <?php echo ($dispositivo === 'Ordenador') ? 'selected' : ''; ?>>Ordenador</option>
                        <option value="Tableta" <?php echo ($dispositivo === 'Tableta') ? 'selected' : ''; ?>>Tableta</option>
                        <option value="Telefono" <?php echo ($dispositivo === 'Telefono') ? 'selected' : ''; ?>>Teléfono</option>
                    </select>
                </p>

                <p>
                    <button type="submit">Empezar Test</button>
                </p>
            </form>
        </section>

    <?php elseif ($estadoPrueba === 'preguntas'): ?>

        <!-- Paso 2: test -->
        <section>
            <h2>Test de MotoGP-Desktop</h2>
            <p>Responde a todas las preguntas basadas en la información del proyecto MotoGP-Desktop.</p>

            <form method="post">
                <input type="hidden" name="accion" value="terminar_test"/>

                <?php
                $enunciados = [
                    1 => "¿Cúantos puntos obtuvo Pedro Acosta en el último mundial de MotoGP?",
                    2 => "¿Cuántas aplicaciones hay en la sección 'Juegos'?",
                    3 => "¿Cúantos habitantes tiene Misano?",
                    4 => "¿Qué dorsal lleva actualmente Pedro Acosta?",
                    5 => "Completa el juego de cartas e indica cuánto tiempo te ha llevado.",
                    6 => "¿Cuántas imágenes distintas del Misano World Circuit se muestran en la página de inicio?",
                    7 => "¿Cuál es el gentilicio de Misano?",
                    8 => "¿Qué temperatura media hacía el día antes de la carrera en Misano World Circuit?",
                    9 => "¿Dónde nació Pedro Acosta?",
                    10 => "¿En qué apartado se muestran las noticias más recientes relacionadas con MotoGP?"
                ];

                for ($i = 1; $i <= 10; $i++): ?>
                    <fieldset>
                        <legend>Pregunta <?php echo $i; ?></legend>
                        <p><?php echo htmlspecialchars($enunciados[$i], ENT_QUOTES, 'UTF-8'); ?></p>
                        <textarea name="p<?php echo $i; ?>" rows="3" cols="60" required><?php
                            echo htmlspecialchars($preguntas[$i] ?? "", ENT_QUOTES, 'UTF-8');
                        ?></textarea>
                    </fieldset>
                <?php endfor; ?>

                <p>
                    <button type="submit">Terminar Test</button>
                </p>
            </form>
        </section>

    <?php elseif ($estadoPrueba === 'post_test'): ?>

        <!-- Paso 3: comentarios, propuestas, valoración, facilitador -->
        <section>
            <h2>Cuestionario posterior al test</h2>
            <p>El usuario debe rellenar los siguientes campos tras completar las tareas del test.</p>

            <form method="post">
                <input type="hidden" name="accion" value="guardar_resultados"/>

                <p>
                    <label for="comentarios_usuario">Comentarios del usuario sobre la prueba:</label><br/>
                    <textarea id="comentarios_usuario" name="comentarios_usuario"
                              rows="4" cols="70"><?php
                        echo htmlspecialchars($comentariosUsuarioForm, ENT_QUOTES, 'UTF-8');
                    ?></textarea>
                </p>

                <p>
                    <label for="propuestas_mejora">Propuestas de mejora por parte del usuario:</label><br/>
                    <textarea id="propuestas_mejora" name="propuestas_mejora"
                              rows="4" cols="70"><?php
                        echo htmlspecialchars($propuestasMejora, ENT_QUOTES, 'UTF-8');
                    ?></textarea>
                </p>

                <p>
                    <label for="valoracion">Valoración global de la aplicación (0-10): </label>
                    <input type="number" id="valoracion" name="valoracion"
                           min="0" max="10" required
                           value="<?php echo htmlspecialchars($valoracion, ENT_QUOTES, 'UTF-8'); ?>"/>
                </p>

                <h3>Datos del facilitador</h3>
                <p>
                    <label for="tarea_completada">¿La tarea se ha completado?</label>
                    <select id="tarea_completada" name="tarea_completada" required>
                        <option value="">-- Selecciona --</option>
                        <option value="Si" <?php echo ($tareaCompletadaForm === 'Si') ? 'selected' : ''; ?>>Sí</option>
                        <option value="No" <?php echo ($tareaCompletadaForm === 'No') ? 'selected' : ''; ?>>No</option>
                    </select>
                </p>

                <p>
                    <label for="comentarios_facilitador">Comentarios del facilitador:</label><br/>
                    <textarea id="comentarios_facilitador" name="comentarios_facilitador"
                              rows="4" cols="70" required><?php
                        echo htmlspecialchars($comentariosFacilitador, ENT_QUOTES, 'UTF-8');
                    ?></textarea>
                </p>

                <p>
                    <button type="submit">Guardar resultados</button>
                </p>
            </form>
        </section>

    <?php elseif ($estadoPrueba === 'finalizada'): ?>

        <section>
            <h2>Prueba finalizada</h2>
            <p>
                La prueba de usabilidad ha finalizado y los datos se han almacenado en la base de datos.
            </p>
        </section>

    <?php endif; ?>

</main>

</body>
</html>
        <?php
    }
}

$test = new Test();
$test->run();