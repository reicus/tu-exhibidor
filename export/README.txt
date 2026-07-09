EXPORTACIÓN TU EXHIBIDOR
==========================

Fecha: 08-07-2026, 5:33:29 p. m.

CONTENIDO
---------
1. imagenes-en-uso/   — Copia de todas las imágenes referenciadas en el sitio
   - catalogo/         85 archivos (productos del catálogo)
   - hero/             84 archivos (slider principal)
   - galeria/          30 archivos (galería premium)
   - categorias/       84 archivos (tarjetas de categoría)
   - home/             4 archivos (imágenes estáticas del home)
   - marca/            5 archivos (logo, favicon, etc.)

2. catalogo-productos-precios.xlsx — Lista de 85 productos con:
   - SKU, nombre, categoría
   - Ruta local de imagen + miniatura en Excel
   - URL del producto en tuexhibidor.cl
   - Precio actual en WooCommerce (si existe)
   - Columna editable "Precio competencia" para su investigación

CÓMO COMPLETAR PRECIOS DE COMPETENCIA
-------------------------------------
1. Abra catalogo-productos-precios.xlsx en Excel.
2. En la hoja "Catálogo", llene la columna G: "Precio competencia (editable)".
3. Use montos en CLP sin símbolo (ej: 12500).
4. Agregue notas en columna H si lo desea.
5. Guarde el archivo.

CÓMO DEVOLVER PARA PUBLICAR EN LA TIENDA
----------------------------------------
Cuando quiera publicar precios en WooCommerce, devuelva este archivo Excel
(o indique la ruta) y podremos importarlos con:

   node scripts/import_prices_from_xlsx.mjs export/catalogo-productos-precios.xlsx

Columna clave para importación: "Precio competencia (editable)"
Identificador de producto: columna "SKU"

NOTAS
-----
- Solo se copiaron imágenes referenciadas en catalog-data.js, site-data.js e index.html.
- No se modificó el sitio en vivo.
- Ediciones manuales conservadas del Excel anterior: 0 precios competencia, 0 notas.
- Regenerar exportación: node scripts/export_catalog_and_images.mjs
