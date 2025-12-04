class Circuito {
    constructor() {
        const inputHtml = document.querySelector("input[accept='.html']");

        this.comprobarApiFile();

        inputHtml.addEventListener("change", (evento) => {
            this.leerArchivoHTML(evento);
        });

    }

    crearContenedor() {
        const main = document.querySelector("main");
        const seccion = document.createElement("section");

        const parrafoInput = document.querySelector("main > p:nth-of-type(1)");

        if (parrafoInput.nextSibling) {
            main.insertBefore(seccion, parrafoInput.nextSibling);
        } else {
            main.appendChild(seccion);
        }

        this.contenedor = seccion;
    }

    comprobarApiFile(){
        if(window.File && window.FileReader && window.FileList && window.Blob){
            console.log("La API File está soportada");
        }else{
            const div = document.querySelector("div");

            const parrafoMensaje = document.createElement("p");
            parrafoMensaje.textContent = "Este navegador no soporta la API File de HTML5.";

            div.before(parrafoMensaje);
        }
    }

    leerArchivoHTML(evento) {
        const archivo = evento.target.files[0];
        if (!archivo) {
            return;
        }

        const lector = new FileReader();

        lector.onload = (e) => {
            const contenido = e.target.result;
            this.procesarHTML(contenido);
        };

        lector.readAsText(archivo, "UTF-8");
    }

    procesarHTML(textoHTML) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(textoHTML, "text/html");

        const bodyOrigen = doc.body;

        this.crearContenedor();
        while (this.contenedor.firstChild) {
            this.contenedor.removeChild(this.contenedor.firstChild);
        }

        const clonBody = bodyOrigen.cloneNode(true);

        // Transformar los títulos h1 a h3 y h2 a h4
        this.transformarTitulos(clonBody);

        while (clonBody.firstChild) {
            this.contenedor.appendChild(clonBody.firstChild);
        }
    }

    transformarTitulos(nodo) {
        if (nodo.nodeType === Node.ELEMENT_NODE) {
            const tag = nodo.tagName.toLowerCase();

            if (tag === "h1") {
                const nuevo = document.createElement("h3");
                nuevo.innerHTML = nodo.innerHTML;
                nodo.replaceWith(nuevo);
                return;
            }

            if (tag === "h2") {
                const nuevo = document.createElement("h4");
                nuevo.innerHTML = nodo.innerHTML;
                nodo.replaceWith(nuevo);
                return;
            }
        }
        const hijos = [...nodo.childNodes];
        for (const hijo of hijos) {
            this.transformarTitulos(hijo);
        }
    }
}

class CargadorSVG {

    constructor() {
        const inputSvg = document.querySelector("input[accept='.svg']");

        inputSvg.addEventListener("change", (evento) => {
            cargadorsvg.leerArchivoSVG(evento.target.files);
        });
    }

     leerArchivoSVG(files){
        var archivo = files[0];

        if (archivo && archivo.type === 'image/svg+xml') {
            const lector = new FileReader();
            lector.onload = (e) => this.insertarSVG(e.target.result);
            lector.readAsText(archivo);
        } else {
            console.error('Selecciona un archivo SVG válido.')
        }
     }

     insertarSVG(content){
        const parser = new DOMParser();
        const documentoSVG = parser.parseFromString(content, 'image/svg+xml');
        const elementoSVG = documentoSVG.documentElement;
        this.contenedor = document.querySelector('main');
        this.contenedor.appendChild(elementoSVG);
     }
}


class CargadorKML {

    constructor() {
        this.origen = null;
        this.tramos = [];

        this.marcadorOrigen = null;
        this.polilineaCircuito = null;

        const inputKml = document.querySelector("input[accept='.kml']");

        inputKml.addEventListener("change", (evento) => {
            cargadorkml.leerArchivoKML(evento.target.files);
        });
    }

    leerArchivoKML(files){
        const archivo = files[0];
        if (!archivo) {
            return;
        }

        const lector = new FileReader();
        lector.onload = (e) => {
            const contenido = e.target.result;
            this.procesarKML(contenido);
        };
        lector.readAsText(archivo, "UTF-8");
    }

    procesarKML(textoKML) {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(textoKML, "application/xml");

        const coordOrigenNode = xmlDoc.querySelector("Point > coordinates");
        const coordTramosNode = xmlDoc.querySelector("LineString > coordinates");

        let origen = null;
        let tramos = [];

        if (coordOrigenNode && coordTramosNode) {
            origen = this._parsearPrimeraCoordenada(coordOrigenNode.textContent);

            tramos = this._parsearListaCoordenadas(coordTramosNode.textContent);
        } else {
            const coordsElements = xmlDoc.getElementsByTagName("coordinates");
            if (coordsElements.length === 0) {
                console.error("No se han encontrado elementos <coordinates> en el KML.");
                return;
            }

            const coordsTexto = coordsElements[0].textContent;
            tramos = this._parsearListaCoordenadas(coordsTexto);
            if (tramos.length === 0) {
                console.error("No se han podido obtener coordenadas del KML.");
                return;
            }
            origen = tramos[0];
        }

        this.origen = origen;
        this.tramos = tramos;

        this.insertarCapaKML();
    }

    _parsearListaCoordenadas(texto) {
        const puntos = [];
        const trozos = texto.trim().split(/\s+/);

        trozos.forEach(t => {
            const coords = t.split(",");
            if (coords.length >= 2) {
                const lon = parseFloat(coords[0]);
                const lat = parseFloat(coords[1]);
                if (!isNaN(lat) && !isNaN(lon)) {
                    puntos.push({ lat: lat, lng: lon });
                }
            }
        });

        return puntos;
    }

    _parsearPrimeraCoordenada(texto) {
        const lista = this._parsearListaCoordenadas(texto);
        return lista.length > 0 ? lista[0] : null;
    }

    insertarCapaKML(){
        if (!this.origen || this.tramos.length === 0) {
            console.error("No hay datos de circuito cargados para mostrar en el mapa.");
            return;
        }

        if (!googleMap.mapa) {
            console.error("El mapa aún no está inicializado.");
            return;
        }

        // Centrar el mapa en el punto de origen y ajustar el zoom
        googleMap.mapa.setCenter(this.origen);
        googleMap.mapa.setZoom(16);

        if (this.marcadorOrigen) {
            this.marcadorOrigen.setMap(null);
        }
        if (this.polilineaCircuito) {
            this.polilineaCircuito.setMap(null);
        }

        // Marcador en el punto de origen
        this.marcadorOrigen = new google.maps.Marker({
            position: this.origen,
            map: googleMap.mapa,
            title: "Punto de origen del circuito"
        });

        // Polilínea con los tramos del circuito
        this.polilineaCircuito = new google.maps.Polyline({
            path: this.tramos,
            geodesic: true,
            strokeColor: "#FF0000",
            strokeOpacity: 1.0,
            strokeWeight: 2
        });

        this.polilineaCircuito.setMap(googleMap.mapa);
    }
}

class GoogleMap {
    constructor(selector) {
        this.selector = selector;
        this.mapa = null;
        this.initMap();
    }

    initMap() {
        const centroInicial = { lat: 43.96139, lng: 12.68333 };

        this.mapa = new google.maps.Map(
            document.querySelector(this.selector),
            {
                zoom: 8,
                center: centroInicial
            }
        );
    }
}


new Circuito();
const googleMap = new GoogleMap("main > div");
const cargadorsvg = new CargadorSVG();
const cargadorkml = new CargadorKML();

