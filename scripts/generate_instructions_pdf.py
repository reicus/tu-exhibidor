#!/usr/bin/env python3
"""Genera PDF de instrucciones operativas Tu Exhibidor."""
from datetime import date
from pathlib import Path

from fpdf import FPDF

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "docs" / "INSTRUCCIONES-TU-EXHIBIDOR.pdf"
FONT = Path("C:/Windows/Fonts/arial.ttf")
FONT_BOLD = Path("C:/Windows/Fonts/arialbd.ttf")


class GuidePDF(FPDF):
    def __init__(self):
        super().__init__()
        self.add_font("Arial", "", str(FONT))
        self.add_font("Arial", "B", str(FONT_BOLD))

    def header(self):
        self.set_font("Arial", "B", 14)
        self.cell(0, 10, "Tu Exhibidor - Guia de instrucciones", new_x="LMARGIN", new_y="NEXT")
        self.set_font("Arial", "", 9)
        self.cell(
            0,
            6,
            f"Sitio web y tienda - Actualizado {date.today().isoformat()}",
            new_x="LMARGIN",
            new_y="NEXT",
        )
        self.ln(3)

    def section(self, title: str):
        self.ln(2)
        self.set_font("Arial", "B", 11)
        self.set_fill_color(235, 227, 216)
        self.cell(0, 8, f"  {title}", new_x="LMARGIN", new_y="NEXT", fill=True)
        self.ln(2)

    def body(self, text: str):
        self.set_font("Arial", "", 9)
        self.multi_cell(0, 5, text)
        self.ln(2)

    def bullet(self, text: str):
        self.set_font("Arial", "", 9)
        self.multi_cell(0, 5, f"  - {text}")
        self.ln(1)

    def row(self, label: str, value: str):
        self.set_font("Arial", "B", 9)
        self.cell(0, 5, label, new_x="LMARGIN", new_y="NEXT")
        self.set_font("Arial", "", 9)
        self.multi_cell(0, 5, value)
        self.ln(1)


def main():
    pdf = GuidePDF()
    pdf.set_auto_page_break(auto=True, margin=14)
    pdf.add_page()

    pdf.section("1. Como esta armado el sitio")
    pdf.body(
        "Tu Exhibidor tiene dos capas en el mismo dominio:\n"
        "A) Sitio premium estatico en /site/ (home, hero, galeria, catalogo visual).\n"
        "B) Tienda WooCommerce en /shop/ (catalogo con busqueda, fichas de producto, WhatsApp).\n"
        "La raiz tuexhibidor.cl redirige al sitio premium. La tienda vive en /shop/."
    )

    pdf.section("2. URLs utiles")
    pdf.row("Sitio publico (home)", "https://tuexhibidor.cl/site/")
    pdf.row("Tienda / catalogo", "https://tuexhibidor.cl/shop/")
    pdf.row("Login administracion", "https://tuexhibidor.cl/login")
    pdf.row("Gestionar imagenes del sitio", "https://tuexhibidor.cl/imagenes")
    pdf.row("Medios WordPress", "https://tuexhibidor.cl/medios")
    pdf.row("Panel WordPress", "https://tuexhibidor.cl/wp-admin/")
    pdf.row("Repositorio codigo", "https://github.com/reicus/tu-exhibidor")

    pdf.section("3. Reglas generales para fotos")
    pdf.bullet("Sube siempre la foto mas grande y nítida posible; el sistema achica, no agranda bien.")
    pdf.bullet("Fondo recomendado: crema #ddd3c8 (no blanco puro).")
    pdf.bullet("Producto completo centrado, con aire alrededor (la web usa object-fit: contain).")
    pdf.bullet("Formato preferido: JPG para catalogo y carruseles.")
    pdf.bullet("Evita capturas de pantalla, collages o logos incrustados en la foto.")

    pdf.section("4. Dimensiones por tipo de imagen")
    pdf.row(
        "Producto de catalogo (85 fichas)",
        "Ideal: 1200 x 1200 px (cuadrado 1:1). Minimo: 900 px lado corto. Peso: ~150-180 KB JPG.",
    )
    pdf.row(
        "Tarjetas tienda WooCommerce",
        "Se muestran a 800 px; sube origen 1200 x 1200 px para buena calidad.",
    )
    pdf.row(
        "Ficha de producto (zoom)",
        "1200-1600 px lado largo, cuadrado o casi cuadrado.",
    )
    pdf.row(
        "Hero (7 slides arriba del home)",
        "1600 px de ancho, horizontal, proporcion 4:3. Peso ~200 KB.",
    )
    pdf.row(
        "Galeria (fotos en accion)",
        "1200 px ancho max., horizontal ~16:10. Peso ~150 KB.",
    )
    pdf.row(
        "Logo / marca",
        "512 px lado largo (PNG con transparencia o JPG). En web se ve ~52 px header.",
    )
    pdf.row(
        "Favicon / icono movil",
        "32 x 32 px y 180 x 180 px (apple-touch-icon).",
    )
    pdf.body(
        "El sitio genera versiones responsive en 400, 800, 1200 y 1600 px para hero y galeria."
    )

    pdf.add_page()
    pdf.section("5. Cambiar imagenes desde WordPress (forma facil)")
    pdf.body("No necesitas FTP para cambios habituales de imagenes del sitio premium.")
    pdf.bullet("Entra a https://tuexhibidor.cl/login con usuario admin.")
    pdf.bullet("Menu lateral: Sitio Premium (o abre /imagenes).")
    pdf.bullet("Pestanas: Catalogo | Hero | Galeria | Marca.")
    pdf.bullet("Clic en Cambiar imagen, elige de la biblioteca o sube nueva.")
    pdf.bullet("Los cambios se publican en tuexhibidor.cl/site/ al instante.")

    pdf.section("6. Preparar fotos nuevas (flujo tecnico recomendado)")
    pdf.body(
        "Carpeta del proyecto en PC: C:\\Users\\Lenovo\\Downloads\\Tu Exhibidor\n"
        "Herramienta ImgSEO: Herramienta imagenes seo y compresor\\image-seo-processor\\image-seo-processor\\"
    )
    pdf.bullet("Catalogo: copiar JPG a public/images/catalog/")
    pdf.bullet(
        "ImgSEO catalogo: --project tuexhibidor --use product-mockup --max-width 1200 "
        "--quality 85 --target-kb 180 --no-bg --lang es"
    )
    pdf.bullet("Revisar propuesta_nombres.csv y procesar con --names-csv")
    pdf.bullet("Unificar fondos crema: npm run warm:images")
    pdf.bullet("Regenerar datos del sitio: npm run build:site")
    pdf.bullet("Subir al servidor: public/images/ y site/ via FTP o panel")

    pdf.section("7. Donde va cada archivo en el servidor")
    pdf.row("Sitio estatico", "public_html/site/")
    pdf.row("Imagenes publicas", "public_html/public/images/")
    pdf.row("Catalogo JPG", "public_html/public/images/catalog/")
    pdf.row("Hero", "public_html/public/images/hero/")
    pdf.row("Galeria", "public_html/public/images/premium/")
    pdf.row("Logo / favicon", "public_html/public/images/brand/")
    pdf.row("Tema WordPress", "public_html/wp-content/themes/aurum-child/")

    pdf.section("8. Tienda y buscador")
    pdf.bullet("El buscador de la lupa busca en /shop/ (productos y categorias).")
    pdf.bullet("Primera clic en lupa: abre campo para escribir.")
    pdf.bullet("Segunda clic (con texto) o Enter: muestra resultados.")
    pdf.bullet("Productos sin precio: cotizacion por WhatsApp (Alfonso / Leder).")

    pdf.section("9. Comandos utiles en la PC (PowerShell)")
    pdf.row("Preview local", "npx serve . -l 3000  ->  http://localhost:3000/site/")
    pdf.row("Regenerar catalogo JS", "npm run build:site")
    pdf.row("Calentar fondos JPG", "npm run warm:images")
    pdf.row("Empaquetar deploy", "npm run deploy:pack")
    pdf.row("Regenerar PDF instrucciones", "py scripts/generate_instructions_pdf.py")

    pdf.section("10. Paleta y estilo (no cambiar sin motivo)")
    pdf.row("Crema fondo", "#ebe3d8 / superficie #ddd3c8")
    pdf.row("Dorado marca", "#b8935f")
    pdf.row("Texto", "#2b2926")
    pdf.row("Tipografia", "Poppins + Playfair Display")
    pdf.row("Imagenes", "object-fit: contain (producto completo visible, sin recorte agresivo)")

    pdf.section("11. Soporte y contacto")
    pdf.row("Email", "info@tuexhibidor.cl")
    pdf.row("WhatsApp Alfonso", "+56 9 3749 0214")
    pdf.row("WhatsApp Leder", "+56 9 9132 7813")
    pdf.row("Desarrollo web", "Tecnotix Solutions - https://tecnotix.cl")
    pdf.body(
        "Para credenciales de hosting/FTP/WordPress ver el documento separado "
        "CREDENCIALES-TU-EXHIBIDOR.pdf (confidencial, no subir a GitHub)."
    )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    pdf.output(str(OUT))
    print(OUT)


if __name__ == "__main__":
    main()
