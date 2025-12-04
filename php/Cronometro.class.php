<?php
/**
 * Clase Cronometro externalizada.
 * No gestiona sesiones ni HTML, solo mide tiempo.
 */
class Cronometro {

    private float $tiempo;
    private ?float $inicio;

    public function __construct() {
        $this->tiempo = 0.0;
        $this->inicio = null;
    }

    // Arranca el cronómetro
    public function arrancar(): void {
        $this->inicio = microtime(true);
    }

    // Para el cronómetro y calcula el tiempo transcurrido
    public function parar(): void {
        if ($this->inicio !== null) {
            $fin = microtime(true);
            $this->tiempo = $fin - $this->inicio;
            $this->inicio = null;
        }
    }

    /**
     * Devuelve el tiempo total en segundos
     * (es lo que guardaremos en tiempo_segundos en la BD)
     */
    public function getTiempoSegundos(): float {
        return $this->tiempo;
    }

    // Formato opcional mm:ss.d (por si lo quieres usar en otra página)
    public function mostrar(): string {
        $totalDecimas = (int) round($this->tiempo * 10);
        $decimas = $totalDecimas % 10;

        $totalSegundos = intdiv($totalDecimas, 10);
        $segundos = $totalSegundos % 60;
        $minutos = intdiv($totalSegundos, 60);

        return sprintf("%02d:%02d.%1d", $minutos, $segundos, $decimas);
    }
}