class Ciudad {

  constructor(nombre, pais, gentilicio) {
    this.nombre = nombre;
    this.pais = pais;
    this.gentilicio = gentilicio;
    this.poblacion = null;
    this.coordenadas = null;

    this.fechaCarrera = "2025-09-14";
    this.timezone = "Europe/Rome";
    this.diasEntrenos = 3;

    this.rellenarDatos(24500, { lat: 43.96139, lon: 12.68333 });
    this.escribirCoordenadas();
    this.getInfoSecundariaHTML();
    this.iniciar();
  }

  // utilidades DOM
  _sec() { return document.querySelector("main"); }
  _append(el) { this._sec().appendChild(el); }
  _mk(tag, text) { const e = document.createElement(tag); if (text) e.textContent = text; return e; }
  _ulFromObj(obj) {
    const ul = this._mk("ul");
    Object.keys(obj).forEach(k => {
      const li = this._mk("li", `${k}: ${obj[k]}`);
      ul.appendChild(li);
    });
    return ul;
  }

  rellenarDatos(poblacion, coordenadas) {
    this.poblacion = poblacion;
    this.coordenadas = coordenadas;
  }

  getNombreCiudad() {
    return this.nombre;
  }

  getNombrePais() {
    return this.pais;
  }

  getInfoSecundariaHTML() {
    const ul = document.createElement("ul");
    const liGentilicio = document.createElement("li");
    liGentilicio.textContent = `Gentilicio: ${this.gentilicio}`;
    ul.appendChild(liGentilicio);

    const liPoblacion = document.createElement("li");
    liPoblacion.textContent = `Población: ${this.poblacion.toString()}`;
    ul.appendChild(liPoblacion);

    this._append(ul);
  }

  escribirCoordenadas() {
    const p = document.createElement("p");
    p.textContent = `Coordenadas de ${this.nombre}: lat=${this.coordenadas.lat}, lon=${this.coordenadas.lon}`;

    this._append(p);
  }

  // URL al endpoint con variables horarias/diarias
  #construirUrl(rango, hourlyVars = [], dailyVars = []) {
    const base = "https://archive-api.open-meteo.com/v1/era5";
    const params = new URLSearchParams({
      latitude: this.coordenadas.lat,
      longitude: this.coordenadas.lon,
      start_date: rango.inicio,
      end_date: rango.fin,
      timezone: this.timezone,
      hourly: hourlyVars.join(","),
      daily: dailyVars.join(",")
    });
    return `${base}?${params.toString()}`;
  }

  // Obtener tiempo del día de la carrera en franjas horarias
  getMeteorologiaCarrera() {
    const fecha = this.fechaCarrera;
    const url = this.#construirUrl(
      { inicio: fecha, fin: fecha },
      [
        "temperature_2m",
        "apparent_temperature",
        "precipitation",
        "relative_humidity_2m",
        "wind_speed_10m",
        "wind_direction_10m"
      ],
      ["sunrise", "sunset"]
    );

    return $.ajax({ url, dataType: "json", method: "GET" });
  }

  // Procesar JSON del día de la carrera
  procesarJSONCarrera(json) {
    const h = json.hourly || {};
    const d = json.daily || {};

    const horas = (h.time || []).map((t, i) => ({
      time: t,
      temperatura: h.temperature_2m?.[i],
      sensacion: h.apparent_temperature?.[i],
      precipitacion: h.precipitation?.[i],
      humedad: h.relative_humidity_2m?.[i],
      viento_vel: h.wind_speed_10m?.[i],
      viento_dir: h.wind_direction_10m?.[i]
    }));

    // Filtrar solo la hora 14:00
    const horaCarrera = horas.filter(h => h.time.endsWith("14:00"));

    const amanecer = d.sunrise?.[0];
    const atardecer = d.sunset?.[0];

    return { horas: horaCarrera, amanecer, atardecer, metadata: {
      timezone: json.timezone, lat: json.latitude, lon: json.longitude
    }};
  }

  // Tabla del día de la carrera
  pintarCarrera(data) {
    const art = this._mk("article");
    art.appendChild(this._mk("h3", `Meteorología — Día de carrera (${this.fechaCarrera})`));

    const ulDia = this._ulFromObj({
      "Amanecer": data.amanecer,
      "Atardecer": data.atardecer
    });
    art.appendChild(ulDia);

    const tabla = this._mk("table");
    const thead = this._mk("thead");
    const trh = this._mk("tr");
    ["Hora", "Temp (°C)", "Sensación (°C)", "Lluvia (mm)", "Humedad (%)", "Viento (km/h)", "Dir (°)"]
      .forEach(txt => trh.appendChild(this._mk("th", txt)));
    thead.appendChild(trh);
    tabla.appendChild(thead);

    const tbody = this._mk("tbody");
    data.horas.forEach(h => {
      const tr = this._mk("tr");
      const hora = (typeof h.time === 'string' && h.time.includes('T')) ? h.time.split('T')[1] : h.time;
      [hora, h.temperatura, h.sensacion, h.precipitacion, h.humedad, h.viento_vel, h.viento_dir]
        .forEach(val => tr.appendChild(this._mk("td", String(val))));
      tbody.appendChild(tr);
    });
    tabla.appendChild(tbody);
    art.appendChild(tabla);

    this._append(art);
  }

  // Obtener meteo de los 3 días de entrenos
  getMeteorologiaEntrenos() {
    const f = new Date(this.fechaCarrera);
    const f1 = new Date(f); f1.setDate(f.getDate() - this.diasEntrenos);
    const f2 = new Date(f); f2.setDate(f.getDate() - 1);

    const iso = d => d.toISOString().slice(0, 10);
    const url = this.#construirUrl(
      { inicio: iso(f1), fin: iso(f2) },
      [
        "temperature_2m",
        "relative_humidity_2m",
        "wind_speed_10m",
        "precipitation"
      ],
      []
    );

    return $.ajax({ url, dataType: "json", method: "GET" });
  }

  // Procesar json de entrenos
  procesarJSONEntrenos(json) {
    const h = json.hourly || {};
    const series = (h.time || []).map((t, i) => ({
      fecha: t.slice(0,10),
      temperatura: h.temperature_2m?.[i],
      humedad: h.relative_humidity_2m?.[i],
      viento: h.wind_speed_10m?.[i],
      lluvia: h.precipitation?.[i]
    }));

    const porDia = {};
    series.forEach(x => {
      porDia[x.fecha] ||= { n:0, temperatura:0, humedad:0, viento:0, lluvia:0 };
      porDia[x.fecha].n++;
      porDia[x.fecha].temperatura += Number(x.temperatura);
      porDia[x.fecha].humedad += Number(x.humedad);
      porDia[x.fecha].viento += Number(x.viento);
      porDia[x.fecha].lluvia += Number(x.lluvia);
    });

    const medias = Object.keys(porDia).sort().map(fecha => {
      const a = porDia[fecha];
      const div = v => (a.n ? (v / a.n) : 0);
      const f2 = n => Number.parseFloat(n).toFixed(2);
      return {
        fecha,
        temperatura_med: f2(div(a.temperatura)),
        humedad_med:     f2(div(a.humedad)),
        viento_med:      f2(div(a.viento)),
        lluvia_med:      f2(div(a.lluvia))
      };
    });

    return { medias, metadata: { timezone: json.timezone, lat: json.latitude, lon: json.longitude } };
  }

  // Pintar medias de entrenos
  pintarEntrenos(data) {
    const art = this._mk("article");
    art.appendChild(this._mk("h3", "Meteorología — Entrenamientos (3 días previos)"));

    const tabla = this._mk("table");
    const thead = this._mk("thead");
    const trh = this._mk("tr");
    ["Fecha", "Temp media (°C)", "Humedad media (%)", "Viento medio (km/h)", "Lluvia media (mm/h)"]
      .forEach(txt => trh.appendChild(this._mk("th", txt)));
    thead.appendChild(trh);
    tabla.appendChild(thead);

    const tbody = this._mk("tbody");
    data.medias.forEach(dia => {
      const tr = this._mk("tr");
      [dia.fecha, dia.temperatura_med, dia.humedad_med, dia.viento_med, dia.lluvia_med]
        .forEach(val => tr.appendChild(this._mk("td", String(val))));
      tbody.appendChild(tr);
    });
    tabla.appendChild(tbody);

    art.appendChild(tabla);
    this._append(art);
  }

  iniciar() {
    this.getMeteorologiaCarrera()
      .done(json => {
        const datos = this.procesarJSONCarrera(json);
        this.pintarCarrera(datos);
      })
      .fail((e) => {
        console.error(e);
      });

    this.getMeteorologiaEntrenos()
      .done(json => {
        const datos = this.procesarJSONEntrenos(json);
        this.pintarEntrenos(datos);
      })
      .fail((e) => {
        console.error(e);
      });
  }
}

const c = new Ciudad("Misano", "Italia", "misanoense");