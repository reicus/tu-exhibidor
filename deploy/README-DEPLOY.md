# Deploy Tu Exhibidor

## 1. Imágenes (85 productos)
Subir `wp-content/uploads/catalog/*.jpg` → servidor:
`public_html/wp-content/uploads/catalog/`

## 2. Importar productos WooCommerce
WP Admin → Productos → Importar → `import/catalogo-completo.csv`
- Sin precios (modo cotización WhatsApp ya configurado en tema)

## 3. Tema y seguridad
- `wp-content/themes/aurum-child/` → reemplazar en servidor
- `wp-content/mu-plugins/tuexhibidor-security.php` → subir
- `wp-content/mu-plugins/tuexhibidor-site-manager.php` + carpeta `tuexhibidor-site-manager/` → panel **Sitio Premium** en wp-admin

## 4. PHP y plugins
Ver LEER-PRIMERO-SEGURIDAD.md — PHP 8.2 + actualizar WooCommerce primero.

Generado: 2026-07-07T14:31:17.051Z
