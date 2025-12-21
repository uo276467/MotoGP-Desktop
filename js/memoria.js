class Memoria {
    #tablero_bloqueado;
    #primera_carta;
    #segunda_carta;
    #cronometro;

    constructor() {
        this.#tablero_bloqueado = true;
        this.#primera_carta = null;
        this.#segunda_carta = null;

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", () => {
            this.barajarCartas();
            this.#tablero_bloqueado = false;
            });
        } else {
            this.barajarCartas();
            this.#tablero_bloqueado = false;
        }
        this.assignListeners();
        this.#cronometro = new Cronometro();
        this.#cronometro.arrancar();
    }

    assignListeners() {
        const cartas = document.querySelectorAll("main article");
        cartas.forEach(carta => {
            carta.addEventListener("click", () => {
                this.voltearCarta(carta);
            });
        });
    }

    voltearCarta(carta) {
        if (this.#tablero_bloqueado) return;
        const estado = carta.getAttribute("data-estado");
        if (estado === "revelada" || estado === "volteada") return;

        carta.setAttribute("data-estado", "volteada");

        if (!this.#primera_carta) {
            this.#primera_carta = carta;
            return;
        }

        this.#segunda_carta = carta;

        this.#tablero_bloqueado = true;

        this.comprobarPareja();
    }

    barajarCartas() {
        const main = document.querySelector("main");
        const cartas = Array.from(document.querySelectorAll("main article"));

        for (let i = cartas.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [cartas[i], cartas[j]] = [cartas[j], cartas[i]];
        }

        cartas.forEach(c => main.appendChild(c));
    }

    reiniciarAtributos() {
        this.#tablero_bloqueado = false;
        this.#primera_carta = null;
        this.#segunda_carta = null;
    }

    deshabilitarCartas() {
        if (this.#primera_carta) this.#primera_carta.setAttribute("data-estado", "revelada");
        if (this.#segunda_carta) this.#segunda_carta.setAttribute("data-estado", "revelada");

        this.comprobarJuego();

        this.reiniciarAtributos();
    }

    comprobarJuego() {
        const todas = document.querySelectorAll("main article");
        const quedan = Array.from(todas).some(el => el.getAttribute("data-estado") !== "revelada");
        if (!quedan) {
            this.#cronometro.parar();
        }
    }

    cubrirCartas() {
        this.#tablero_bloqueado = true;

        const c1 = this.#primera_carta;
        const c2 = this.#segunda_carta;

        if (!c1 || !c2) {
            this.reiniciarAtributos();
            return;
        }

        setTimeout(() => {
            c1.removeAttribute("data-estado");
            c2.removeAttribute("data-estado");

            this.reiniciarAtributos();
            this.#tablero_bloqueado = false;
        }, 1500);
    }

    comprobarPareja() {
        if (!this.#primera_carta || !this.#segunda_carta) return;

        const img1 = this.#primera_carta.children[0];
        const img2 = this.#segunda_carta.children[0];

        const src1 = img1 ? img1.getAttribute("src") : "";
        const src2 = img2 ? img2.getAttribute("src") : "";

        (src1 === src2) ? this.deshabilitarCartas() : this.cubrirCartas();
    }
}

const memoria = new Memoria();