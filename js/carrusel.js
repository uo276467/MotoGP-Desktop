class Carrusel {
  constructor() {
    this.busqueda = "Misano World Circuit";
    this.actual = 0;
    this.maximo = 4; 

    this.fotos = [];
    this.timer = null;
    this.img = null;

    this.iniciar();
  }

  getFotografias() {
    const tags = `motogp, ${this.busqueda}`;
    const url  = 'https://www.flickr.com/services/feeds/photos_public.gne?jsoncallback=?';
    return $.getJSON(url,
        {
            tags: tags,
            tagmode: "any",
            format: "json"
        });
  }

  procesarJSONFotografias(json) {
    const items = Array.isArray(json?.items) ? json.items : [];
    this.fotos = items.slice(0, 5).map(it => {
      console.log(it);
      const m = it.media.m;
      const url640 = m.replace("_m.jpg", "_z.jpg"); // 640
      console.log(url640);
      return { url: url640, title: it.title };
    });
  }

  mostrarFotografias() {
    const article = $("<article>");
    const h2 = $("<h2>").text(`Imágenes del circuito ${this.busqueda}`);
    const figure = $("<figure>");
    const imgCarr = $("<img>").attr("alt", this.fotos[0].title).attr("src", this.fotos[0].url);

    figure.append(imgCarr);
    article.append(h2).append(figure);
    const main = $("main");
    (main.length ? main : $("body")).append(article);

    this.img = imgCarr;
    this.actual = 0;
    this.timer = setInterval(this.cambiarFotografia.bind(this), 3000);
  }

  cambiarFotografia() {
    this.actual = (this.actual + 1) % (this.maximo + 1); 
    const f = this.fotos[this.actual];
    this.img.attr("src", f.url).attr("alt", f.title);
  }

  iniciar() {
    this.getFotografias()
      .then((json) => {
        this.procesarJSONFotografias(json);
        this.mostrarFotografias();
      })
      .fail((e) => {
        console.error("Error al obtener las fotografías:", e);
      });
  }
}

const carrusel = new Carrusel();