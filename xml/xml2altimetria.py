"""
xml2altimetria.py
Lee el XML de un circuito (namespace y genera un SVG
con la altimetría del circuito.

Uso:
    python3 xml2svg.py --input circuitoEsquema.xml --output altimetria.svg
"""

import sys
import argparse
from xml.etree import ElementTree as ET

UNI_NS = "http://www.uniovi.es"

class Svg:
    def __init__(self, width=800, height=400, padding=20, outfile=None):
        self.width = width
        self.height = height
        self.padding = padding
        self.outfile = outfile
        self._lines = []

    # --- métodos principales ---
    def write_header(self):
        header = (
            f'<svg xmlns="http://www.w3.org/2000/svg" '
            f'width="{self.width}" height="{self.height}" '
            f'viewBox="0 0 {self.width} {self.height}">\n'
            f'  <title>Altimetría del circuito</title>\n'
            f'  <desc>Altitud (m) de los puntos del circuito</desc>\n'
            f'  <rect width="100%" height="100%" fill="white" />\n'  # fondo blanco
        )
        self._write(header)

    def write_polyline(self, points, stroke="red", stroke_width=2, fill="none"):
        pts_str = " ".join(f"{x},{y}" for x, y in points)
        poly = (
            f'  <polyline points="{pts_str}" '
            f'stroke="{stroke}" stroke-width="{stroke_width}" '
            f'fill="{fill}" />\n'
        )
        self._write(poly)

    def write_footer(self):
        self._write("</svg>\n")

    # --- helper interno ---
    def _write(self, text):
        if self.outfile is not None:
            self.outfile.write(text)
        else:
            self._lines.append(text)

    def getvalue(self):
        return "".join(self._lines)


def extract_altitudes(xml_path: str):
    """Extrae las ALTITUDES usando XPath con el namespace correspondiente."""
    tree = ET.parse(xml_path)
    root = tree.getroot()
    ns = {"ns": UNI_NS}

    alts = []
    for alt_el in root.findall(".//ns:puntos/ns:tramo/ns:coordenadas/ns:altitud", ns):
        txt = (alt_el.text or "").strip()
        if not txt:
            continue
        try:
            alts.append(float(txt))
        except ValueError:
            continue
    return alts


def build_svg_points(alts, width=800, height=400, padding=40):
    """Genera coordenadas (x,y) equiespaciadas según la altitud."""
    if not alts:
        return []
    n = len(alts)
    drawable_width = width - 2 * padding
    drawable_height = height - 2 * padding

    # X equiespaciadas
    step_x = drawable_width / (n - 1) if n > 1 else drawable_width / 2
    xs = [padding + i * step_x for i in range(n)]

    min_alt, max_alt = min(alts), max(alts)
    points = []
    for i, alt in enumerate(alts):
        if max_alt == min_alt:
            norm = 0.5
        else:
            norm = (alt - min_alt) / (max_alt - min_alt)
        # invertimos eje y
        y = padding + (1 - norm) * drawable_height
        points.append((xs[i], y))
    return points


def main():
    parser = argparse.ArgumentParser(
        description="Convierte altitudes de un XML de circuito en un SVG de altimetría."
    )
    parser.add_argument("--input", "-i", required=True, help="XML de entrada")
    parser.add_argument("--output", "-o", required=True, help="SVG de salida")
    parser.add_argument("--width", type=int, default=800, help="Ancho del SVG")
    parser.add_argument("--height", type=int, default=400, help="Alto del SVG")
    args = parser.parse_args()

    alts = extract_altitudes(args.input)
    if not alts:
        print("No se encontraron altitudes en el XML.", file=sys.stderr)
        sys.exit(2)

    points = build_svg_points(alts, width=args.width, height=args.height, padding=40)

    with open(args.output, "w", encoding="utf-8") as f:
        svg = Svg(width=args.width, height=args.height, padding=40, outfile=f)
        svg.write_header()
        svg.write_polyline(points, stroke="red", stroke_width=2)
        svg.write_footer()

    print(f"SVG generado con {len(points)} puntos -> {args.output}")


if __name__ == "__main__":
    main()