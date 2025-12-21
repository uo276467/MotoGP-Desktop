"""
xml2html.py
Procesa el XML de un circuito (namespace http://www.uniovi.es) y genera
un HTML con toda la información relevante.

Uso:
    python3 xml2html.py --input circuitoEsquema.xml --output InfoCircuito.html
"""

import sys
import argparse
from xml.etree import ElementTree as ET

UNI_NS = "http://www.uniovi.es"

class Html:
    def __init__(self, title="Circuito", outfile=None):
        self.title = title
        self.outfile = outfile
        self._lines = []

    def write_header(self):
        head = (
            "<!DOCTYPE html>\n"
            "<html lang=\"es\">\n"
            "<head>\n"
            f"  <meta charset=\"UTF-8\" />\n"
            f"  <title>{self.title}</title>\n"
            f"  <link rel=\"stylesheet\" type=\"text/css\" href=\"../estilo/estilo.css\" />\n"
            "</head>\n"
            "<body>\n"
            f"  <h1>{self.title}</h1>\n"
        )
        self._write(head)

    def write_section(self, title, content_html):
        sec = (
            f"  <section>\n"
            f"    <h2>{title}</h2>\n"
            f"{content_html}\n"
            f"  </section>\n"
        )
        self._write(sec)

    def write_footer(self):
        self._write("</body>\n</html>\n")

    def _write(self, text):
        if self.outfile is not None:
            self.outfile.write(text)
        else:
            self._lines.append(text)

    def getvalue(self):
        return "".join(self._lines)


def parse_xml_data(xml_path: str) -> dict:
    """Lee el XML del circuito (sin namespaces) y devuelve un diccionario con los datos."""
    tree = ET.parse(xml_path)
    root = tree.getroot()

    datos = {}

    # Atributos generales del circuito
    datos["nombre"] = root.get("nombre", "")
    datos["fecha2025"] = root.get("fecha2025", "")
    datos["horaInicio"] = root.get("horaInicio", "")
    datos["nVueltas"] = root.get("nVueltas", "")
    datos["pais"] = root.get("pais", "")
    datos["localidad"] = root.get("localidad", "")
    datos["patrocinador"] = root.get("patrocinador", "")

    # Extensión y anchura
    ext_el = root.find("extension")
    datos["extension"] = ext_el.text.strip() if ext_el is not None and ext_el.text else ""
    datos["extension_unid"] = ext_el.get("unidades", "") if ext_el is not None else ""

    anch_el = root.find("anchura")
    datos["anchura"] = anch_el.text.strip() if anch_el is not None and anch_el.text else ""
    datos["anchura_unid"] = anch_el.get("unidades", "") if anch_el is not None else ""

    # Bibliografía
    biblio = []
    for ref in root.findall("bibliografia/referencia"):
        enlace = ref.get("enlace", "")
        texto = (ref.text or "").strip()
        biblio.append({"enlace": enlace, "texto": texto})
    datos["bibliografia"] = biblio

    # Galería de fotos
    fotos = []
    for foto in root.findall("galeriaFotos/foto"):
        enlace = foto.get("enlace", "")
        alt = foto.get("alt", "")
        fotos.append({"enlace": enlace, "alt": alt})
    datos["fotos"] = fotos

    # Galería de vídeos
    videos = []
    for video in root.findall("galeriaVideos/video"):
        enlace = video.get("enlace", "")
        desc = video.get("descripcion", "")
        videos.append({"enlace": enlace, "descripcion": desc})
    datos["videos"] = videos

    # Vencedor
    venc_el = root.find("vencedor")
    if venc_el is not None:
        nombre_v = venc_el.get("nombre", "")
        dur_el = venc_el.find("duracion")
        dur = dur_el.text.strip() if dur_el is not None and dur_el.text else ""
        datos["vencedor"] = {"nombre": nombre_v, "duracion": dur}
    else:
        datos["vencedor"] = None

    # Top 3
    top3 = []
    for pil in root.findall("top3/piloto"):
        puesto = pil.get("puesto", "")
        puntos = pil.get("puntos", "")
        nombre_p = (pil.text or "").strip()
        top3.append({"puesto": puesto, "puntos": puntos, "nombre": nombre_p})
    datos["top3"] = top3

    # (Coordenadas y puntos del circuito: si el ejercicio no lo pide,
    # no hace falta procesarlos aquí.)

    return datos


def html_from_data(datos: dict, outfile_path: str):
    """Genera el HTML usando la clase Html."""
    title = datos.get("nombre") or "Circuito"
    with open(outfile_path, "w", encoding="utf-8") as f:
        html = Html(title=title, outfile=f)
        html.write_header()

        # Datos generales (valores en <strong>)
        gen_html = "    <ul>\n"
        gen_html += f"      <li>Nombre: <strong>{datos.get('nombre','')}</strong></li>\n"
        gen_html += f"      <li>Fecha (2025): <strong>{datos.get('fecha2025','')}</strong></li>\n"
        gen_html += f"      <li>Hora inicio: <strong>{datos.get('horaInicio','')}</strong></li>\n"
        gen_html += f"      <li>Número de vueltas: <strong>{datos.get('nVueltas','')}</strong></li>\n"
        gen_html += f"      <li>País: <strong>{datos.get('pais','')}</strong></li>\n"
        gen_html += f"      <li>Localidad: <strong>{datos.get('localidad','')}</strong></li>\n"
        gen_html += f"      <li>Patrocinador: <strong>{datos.get('patrocinador','')}</strong></li>\n"
        gen_html += "    </ul>"
        html.write_section("Datos generales", gen_html)

        # Dimensiones
        dim_html = "    <ul>\n"
        dim_html += f"      <li>Longitud: <strong>{datos.get('extension','')} {datos.get('extension_unid','')}</strong></li>\n"
        dim_html += f"      <li>Anchura media: <strong>{datos.get('anchura','')} {datos.get('anchura_unid','')}</strong></li>\n"
        dim_html += "    </ul>"
        html.write_section("Dimensiones", dim_html)

        # Bibliografía
        biblio = datos.get("bibliografia", [])
        if biblio:
            bib_html = "    <ol>\n"
            for ref in biblio:
                enlace = ref["enlace"]
                texto = ref["texto"]
                bib_html += f'      <li><a href="{enlace}">{texto or enlace}</a></li>\n'
            bib_html += "    </ol>"
        else:
            bib_html = "    <p>No hay referencias.</p>"
        html.write_section("Referencias y bibliografía", bib_html)

        # Galería de fotografías
        fotos = datos.get("fotos", [])
        if fotos:
            fot_html = ""
            for foto in fotos:
                fot_html += (
                    "      <figure>\n"
                    f'        <img src="{foto["enlace"]}" alt="{foto["alt"]}" />\n'
                    f'        <figcaption>{foto["alt"]}</figcaption>\n'
                    "      </figure>\n"
                )
        else:
            fot_html = "      <p>No hay fotografías.</p>\n"

        html.write_section("Galería de fotografías", fot_html)

       # Galería de vídeos
        videos = datos.get("videos", [])
        if videos:
            vid_html = ""
            for video in videos:
                vid_html += (
                    "      <video controls>\n"
                    f'        <source src="{video["enlace"]}" type="video/mp4" />\n'
                    "      </video>\n"
                )
        else:
            vid_html = "      <p>No hay vídeos.</p>\n"

        html.write_section("Galería de vídeos", vid_html)

        # Vencedor 
        vencedor = datos.get("vencedor")
        if vencedor:
            v_html = "    <ul>\n"
            v_html += f"      <li>Nombre: <strong>{vencedor.get('nombre','')}</strong></li>\n"
            v_html += f"      <li>Duración: <strong>{vencedor.get('duracion','')}</strong></li>\n"
            v_html += "    </ul>"
        else:
            v_html = "    <p>No hay información del vencedor.</p>"
        html.write_section("Vencedor", v_html)

        # Top 3
        top3 = datos.get("top3", [])
        if top3:
            t_html = "    <table>\n"
            t_html += "      <thead><tr><th>Puesto</th><th>Piloto</th><th>Puntos</th></tr></thead>\n"
            t_html += "      <tbody>\n"
            for pil in top3:
                t_html += f"        <tr><td>{pil['puesto']}</td><td><strong>{pil['nombre']}</strong></td><td><strong>{pil['puntos']}</strong></td></tr>\n"
            t_html += "      </tbody>\n"
            t_html += "    </table>\n"
        else:
            t_html = "    <p>No hay clasificación.</p>"
        html.write_section("Clasificación (Top 3)", t_html)

        html.write_footer()


def main():
    parser = argparse.ArgumentParser(description="Convierte el XML del circuito en un HTML formateado.")
    parser.add_argument("--input", "-i", required=True, help="XML de entrada")
    parser.add_argument("--output", "-o", required=True, help="HTML de salida")
    args = parser.parse_args()

    datos = parse_xml_data(args.input)
    html_from_data(datos, args.output)
    print(f"HTML generado -> {args.output}")


if __name__ == "__main__":
    main()