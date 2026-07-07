# Envato Market — parches producción (2026-07-07)

El plugin **Envato Market 2.0.1** fallaba al guardar tokens por dos bugs conocidos en entornos actuales.

## 1. User-Agent bloqueado por Envato API

Envato devuelve `403 {"error":"Blocked"}` al User-Agent por defecto del plugin.

**Archivo:** `wp-content/plugins/envato-market/inc/class-envato-market-api.php`

Cambiar el User-Agent de:
```php
'User-Agent'    => 'WordPress - Envato Market ' . envato_market()->get_version(),
```
a:
```php
'User-Agent'    => 'Mozilla/5.0 (compatible; EnvatoMarket/' . envato_market()->get_version() . ')',
```

## 2. Rechazo por permisos extra en el token

Si el token tiene más scopes de los mínimos, el plugin lo rechazaba con "Incorrect token permissions".

**Archivo:** `wp-content/plugins/envato-market/inc/admin/class-envato-market-admin.php`

En `authorize_token_permissions()`, eliminar o comentar el bloque que marca `too-many-permissions` cuando hay scopes adicionales.

## Estado tras configurar token

- Token **verificado** en wp-admin → Envato Market
- Cuenta asociada: **luismejiaredes** (luismejiaredes@gmail.com)
- **0 compras** de temas/plugins WordPress en esa cuenta Envato

Los plugins premium del sitio (Aurum, WPBakery, LayerSlider, ACF PRO) no aparecen para actualizar vía Envato Market con esta cuenta.

### Alternativas para actualizar los 3 plugins pendientes

1. **Token de la cuenta Envato que compró el tema Aurum** (ThemeForest)
2. **Purchase codes** individuales en cada plugin:
   - WPBakery → Product License
   - LayerSlider → Options
   - ACF PRO → Custom Fields → Updates
3. **Subir ZIP manualmente** desde la cuenta de compra en envato.com/downloads

## Seguridad

No guardar tokens en Git. Si un token se comparte por chat, revocarlo en [build.envato.com](https://build.envato.com/) y generar uno nuevo.
