class Noticias {
  constructor(busqueda) {
    this.busqueda = busqueda;
    this.url = "https://api.thenewsapi.com/v1/news/all";

    this.apiToken = "q5pp3gHRDSaKT0KIPXijjnIiN7jQXnUaPzKSvKjY";
    this.language = "es";
    this.limit = 6;
  }

  async buscar() {
    const endpoint =
      `${this.url}?api_token=${this.apiToken}` +
      `&search=${encodeURIComponent(this.busqueda)}` +
      `&language=${this.language}` +
      `&limit=${this.limit}`;

    return fetch(endpoint)
      .then(resp => {
        return resp.json();
      });
  }

  procesarInformacion(json) {
    const lista = Array.isArray(json?.data) ? json.data : [];
    return lista.map(n => ({
      titulo: n.title,
      entradilla: n.description,
      enlace: n.url,
      fuente: n.source,
    }));
  }

  pintar(noticias) {
    const main = document.querySelector("main") || document.body;

    const section = document.createElement("section");
    const h2 = document.createElement("h2");
    h2.textContent = "Últimas noticias";
    section.appendChild(h2);

    noticias.forEach(item => {
      const art = document.createElement("article");

      const h3 = document.createElement("h3");
      h3.textContent = item.titulo;
      art.appendChild(h3);

      const p = document.createElement("p");
      p.textContent = item.entradilla;
      art.appendChild(p);

      const pFuente = document.createElement("p");
      const a = document.createElement("a");
      a.href = item.enlace;
      a.textContent = "Leer noticia";
      pFuente.appendChild(a);
      pFuente.appendChild(document.createTextNode(`  ·  Fuente: ${item.fuente}`));
      art.appendChild(pFuente);

      section.appendChild(art);
    });

    main.appendChild(section);
  }

  iniciar() {
    this.buscar()
      .then(json => this.procesarInformacion(json))
      .then(noticias => this.pintar(noticias))
      .catch(e => {
        console.error(e);
      });
  }
}

const noticias = new Noticias("MotoGP");
noticias.iniciar();