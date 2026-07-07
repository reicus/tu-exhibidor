# WPBakery (js_composer) — parche PHP 8.2

Tras subir PHP a **8.2**, WPBakery 5.7 rompe el sitio con:

```
PHP Fatal error: Unparenthesized `a ? b : c ? d : e` is not supported
in wp-content/plugins/js_composer/include/classes/editors/class-vc-frontend-editor.php on line 646
```

## Parche aplicado en producción (2026-07-07)

Archivo: `wp-content/plugins/js_composer/include/classes/editors/class-vc-frontend-editor.php`

**Antes (línea ~646):**
```php
$host = isset( $s['HTTP_X_FORWARDED_HOST'] ) ? $s['HTTP_X_FORWARDED_HOST'] : isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : $s['SERVER_NAME'];
```

**Después:**
```php
$host = isset( $s['HTTP_X_FORWARDED_HOST'] ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : $s['SERVER_NAME'] );
```

## Nota

Este parche es temporal. Al actualizar WPBakery a **8.7.3** (vía licencia Envato), el archivo se reemplaza y el fix queda incluido en versiones recientes.
