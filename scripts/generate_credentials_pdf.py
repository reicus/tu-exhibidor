#!/usr/bin/env python3
"""Genera PDF local con credenciales Tu Exhibidor (NO subir a GitHub)."""
from datetime import date
from pathlib import Path

from fpdf import FPDF

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "docs" / "CREDENCIALES-TU-EXHIBIDOR.pdf"
FONT = Path("C:/Windows/Fonts/arial.ttf")
FONT_BOLD = Path("C:/Windows/Fonts/arialbd.ttf")


class CredPDF(FPDF):
    def __init__(self):
        super().__init__()
        self.add_font("Arial", "", str(FONT))
        self.add_font("Arial", "B", str(FONT_BOLD))

    def header(self):
        self.set_font("Arial", "B", 14)
        self.cell(0, 10, "Tu Exhibidor - Credenciales y accesos", new_x="LMARGIN", new_y="NEXT")
        self.set_font("Arial", "", 9)
        self.cell(0, 6, f"Documento confidencial - Generado {date.today().isoformat()}", new_x="LMARGIN", new_y="NEXT")
        self.ln(4)

    def section(self, title: str):
        self.ln(2)
        self.set_font("Arial", "B", 11)
        self.set_fill_color(235, 227, 216)
        self.cell(0, 8, f"  {title}", new_x="LMARGIN", new_y="NEXT", fill=True)
        self.ln(2)

    def row(self, label: str, value: str):
        self.set_font("Arial", "B", 9)
        self.cell(0, 5, label, new_x="LMARGIN", new_y="NEXT")
        self.set_font("Arial", "", 9)
        self.multi_cell(0, 5, value)
        self.ln(1)


def main():
    pdf = CredPDF()
    pdf.set_auto_page_break(auto=True, margin=15)
    pdf.add_page()

    pdf.set_font("Arial", "I", 9)
    pdf.multi_cell(
        0,
        5,
        "Guarda este archivo en lugar seguro. No lo subas a repositorios públicos ni lo compartas por canales inseguros. "
        "Se recomienda rotar contraseñas si fueron expuestas en chats o correos.",
    )
    pdf.ln(4)

    pdf.section("1. Hosting — Área de clientes HostingPlus")
    pdf.row("URL", "https://clientes.hostingplus.cl/clientarea.php")
    pdf.row("Producto / servicio", "ID #2816 (tuexhibidor.cl)")
    pdf.row("Usuario (email)", "luismejiaredes@gmail.com")
    pdf.row("Contraseña", "Tecno2025..")
    pdf.row("SSO a cPanel", "https://clientes.hostingplus.cl/clientarea.php?action=productdetails&id=2816&dosinglesignon=1")

    pdf.section("2. cPanel y FTP")
    pdf.row("URL cPanel", "https://rooster.hostingplus.cl:2083")
    pdf.row("Servidor FTP", "rooster.hostingplus.cl")
    pdf.row("Usuario FTP/cPanel", "tuexhibi")
    pdf.row("Contraseña FTP/cPanel", "Tecno2025..")
    pdf.row("Carpeta web", "public_html/")
    pdf.row("Alternativa FTP", "ftp.tuexhibidor.cl (mismo usuario)")

    pdf.section("3. Base de datos MySQL (WordPress)")
    pdf.row("Host", "localhost (desde el servidor)")
    pdf.row("Base de datos", "tuexhibi_dor")
    pdf.row("Usuario DB", "tuexhibi_dor")
    pdf.row("Contraseña DB", "moises180693")
    pdf.row("Nota", "Contraseña del backup JetBackup; puede diferir en producción si se rotó en cPanel.")

    pdf.section("4. WordPress / WooCommerce")
    pdf.row("Sitio público", "https://tuexhibidor.cl")
    pdf.row("Sitio estático (home)", "https://tuexhibidor.cl/site/")
    pdf.row("Tienda / catálogo", "https://tuexhibidor.cl/shop/")
    pdf.row("Login personalizado", "https://tuexhibidor.cl/login")
    pdf.row("wp-admin (si sesión activa)", "https://tuexhibidor.cl/wp-admin/")
    pdf.row("Usuario WP observado", "admin")
    pdf.row("Contraseña WP", "No documentada en el proyecto. Usar la que configuraste o recuperar desde cPanel / correo.")
    pdf.row("Panel imágenes sitio", "https://tuexhibidor.cl/imagenes")
    pdf.row("Medios WordPress", "https://tuexhibidor.cl/medios")
    pdf.row("Softaculous WP", "cPanel → Softaculous → WordPress")

    pdf.section("5. GitHub (código fuente)")
    pdf.row("Repositorio", "https://github.com/reicus/tu-exhibidor")
    pdf.row("Rama principal", "main")
    pdf.row("Clonar", "git clone https://github.com/reicus/tu-exhibidor.git")
    pdf.row("Autenticación", "Cuenta GitHub del propietario (token o credenciales guardadas en el equipo)")

    pdf.section("6. Proyecto local")
    pdf.row("Carpeta", r"C:\Users\Lenovo\Downloads\Tu Exhibidor")
    pdf.row("Preview local", "npx serve . -l 3000 → http://localhost:3000/site/")
    pdf.row("Deploy FTP", "Subir deploy/, site/, public/ a public_html/")

    pdf.section("7. Contacto del negocio (referencia)")
    pdf.row("Email", "info@tuexhibidor.cl")
    pdf.row("WhatsApp Alfonso", "+56 9 3749 0214")
    pdf.row("WhatsApp Leder", "+56 9 9132 7813")
    pdf.row("RUT", "77.036.189-3")

    pdf.section("8. Seguridad recomendada")
    pdf.row("Acción", "Cambiar contraseña cPanel/FTP en cPanel → Password & Security.")
    pdf.row("Acción", "Rotar contraseña MySQL si el backup fue compartido.")
    pdf.row("Acción", "No commitear este PDF ni archivos .env al repositorio.")
    pdf.row("Desarrollo", "Tecnotix Solutions - https://tecnotix.cl")

    OUT.parent.mkdir(parents=True, exist_ok=True)
    pdf.output(str(OUT))
    print(OUT)


if __name__ == "__main__":
    main()
