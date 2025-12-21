class Cronometro {
    constructor(){
        this.tiempo = 0;
        this.inicio = null;
        this.corriendo = null;

        this.#mostrar();
    }

    arrancar() {
        if (this.corriendo != null) return;

        try {
            const ahora = Temporal.Now.instant().epochMilliseconds;
            const inicioMs = ahora - this.tiempo;
            this.inicio = Temporal.Instant.fromEpochMilliseconds(inicioMs);
        } catch (e) {
            const ahora = Date.now();
            const inicioMs = ahora - this.tiempo;
            this.inicio = new Date(inicioMs);
        }

        this.corriendo = setInterval(this.#actualizar.bind(this), 100);
    }

    #actualizar() {
        if (!this.inicio) return;

        let actualMs, inicioMs;

        if (this.inicio instanceof Date) {
            actualMs = Date.now();
            inicioMs = this.inicio.getTime();
        } else {
            actualMs = Temporal.Now.instant().epochMilliseconds;
            inicioMs = this.inicio.epochMilliseconds;
        }

        this.tiempo = Math.max(0, Math.floor(actualMs - inicioMs));
        this.#mostrar();
    }

    #mostrar() {
        const ms = Math.max(0, Math.floor(this.tiempo));
        const minutos = Math.floor(ms / 60000);
        const resto = ms % 60000;
        const segundos = Math.floor(resto / 1000);
        const decimas = Math.floor((resto % 1000) / 100);

        const mm = String(minutos).padStart(2, "0");
        const ss = String(segundos).padStart(2, "0");
        const d  = String(decimas);

        const p = document.querySelector("main p");
        if (p) p.textContent = `${mm}:${ss}.${d}`;
    }

    parar() {
        if (this.corriendo != null) {
            clearInterval(this.corriendo);
            this.corriendo = null;
        }
    }

    reiniciar() {
        if (this.corriendo != null) {
            clearInterval(this.corriendo);
            this.corriendo = null;
        }
        this.tiempo = 0;
        this.inicio = null;
        this.#mostrar();
    }
}

const cronometro = new Cronometro();