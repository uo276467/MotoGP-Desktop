"""
xml2kml.py
Lee el XML del circuito, extrae los puntos con las
coordenadas y genera un KML.

Uso:
  python xml2kml.py --input circuitoEsquema.xml --output circuito.kml
"""

import sys
import argparse
from xml.etree import ElementTree as ET
from xml.dom import minidom

UNI_NS = "http://www.uniovi.es"
KML_NS = "http://www.opengis.net/kml/2.2"


def parse_points(xml_path: str):
    """Extrae las coordenadas de cada tramo usando XPath."""
    tree = ET.parse(xml_path)
    root = tree.getroot()
    ns = {"ns": UNI_NS}

    coords = []
    for tramo in root.findall(".//ns:puntos/ns:tramo", ns):
        lon_el = tramo.find(".//ns:coordenadas/ns:longitud", ns)
        lat_el = tramo.find(".//ns:coordenadas/ns:latitud", ns)
        if lon_el is None or lat_el is None:
            continue
        try:
            lon = float((lon_el.text or "").strip())
            lat = float((lat_el.text or "").strip())
            coords.append((lon, lat, 0.0))  # altitud = 0
        except (ValueError, AttributeError):
            continue
    return coords


def _coords_equal(a, b, eps=1e-9):
    return abs(a[0] - b[0]) < eps and abs(a[1] - b[1]) < eps


class Kml:
    """Encapsula toda la funcionalidad de escritura de un archivo KML."""

    def __init__(self, name="Ruta desde XML"):
        self.name = name
        ET.register_namespace("", KML_NS)
        # <kml>
        self.kml = ET.Element(f"{{{KML_NS}}}kml")
        # <Document>
        self.doc = ET.SubElement(self.kml, f"{{{KML_NS}}}Document")
        ET.SubElement(self.doc, f"{{{KML_NS}}}name").text = self.name

    def add_red_line_style(self, style_id="redLine", color="ff0000ff", width="3"):
        """Crea un estilo de línea en rojo."""
        style = ET.SubElement(self.doc, f"{{{KML_NS}}}Style", id=style_id)
        ls = ET.SubElement(style, f"{{{KML_NS}}}LineStyle")
        ET.SubElement(ls, f"{{{KML_NS}}}color").text = color  # KML: aabbggrr
        ET.SubElement(ls, f"{{{KML_NS}}}width").text = width

    def add_polyline(self, coords, style_id="redLine", placemark_name="Trayecto", close_loop=True):
        """Añade un Placemark con una LineString y las coord dadas."""
        placemark = ET.SubElement(self.doc, f"{{{KML_NS}}}Placemark")
        ET.SubElement(placemark, f"{{{KML_NS}}}name").text = placemark_name
        if style_id:
            ET.SubElement(placemark, f"{{{KML_NS}}}styleUrl").text = f"#{style_id}"

        linestr = ET.SubElement(placemark, f"{{{KML_NS}}}LineString")
        coords_el = ET.SubElement(linestr, f"{{{KML_NS}}}coordinates")

        pts = list(coords)
        if close_loop and len(pts) >= 2 and not _coords_equal(pts[0], pts[-1]):
            pts = pts + [pts[0]]

        coords_el.text = " ".join(f"{lon},{lat},0" for lon, lat, _ in pts)

    def to_pretty_xml(self) -> bytes:
        rough = ET.tostring(self.kml, encoding="utf-8")
        reparsed = minidom.parseString(rough)
        return reparsed.toprettyxml(indent="  ", encoding="utf-8")

    def write_to_file(self, filepath: str):
        xml_bytes = self.to_pretty_xml()
        with open(filepath, "wb") as f:
            f.write(xml_bytes)


def main():
    parser = argparse.ArgumentParser(
        description="Convierte puntos de un XML a KML (línea roja, bucle cerrado)."
    )
    parser.add_argument("--input", "-i", required=True, help="Ruta al XML de entrada")
    parser.add_argument("--output", "-o", required=True, help="Ruta al KML de salida")
    parser.add_argument(
        "--name", "-n", default="Ruta desde XML", help="Nombre del KML/Placemark"
    )
    args = parser.parse_args()

    coords = parse_points(args.input)
    if not coords:
        print("No se encontraron puntos en el XML (¿ruta o namespace correctos?).", file=sys.stderr)
        sys.exit(2)

    # Aquí usamos la clase Kml
    kml = Kml(name=args.name)
    kml.add_red_line_style()
    kml.add_polyline(coords, style_id="redLine", placemark_name="Trayecto", close_loop=True)
    kml.write_to_file(args.output)

    print(f"KML generado con {len(coords)} puntos -> {args.output}")


if __name__ == "__main__":
    main()