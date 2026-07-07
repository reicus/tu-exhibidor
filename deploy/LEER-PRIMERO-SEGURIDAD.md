# Tu Exhibidor — Seguridad y actualizaciones

> Guía para aplicar en **rooster.hostingplus.cl** (cPanel). No incluye contraseñas.

## Estado producción (2026-07-07)

| Componente | Versión producción | Notas |
|------------|-------------------|--------|
| WordPress | 7.0 | Al día |
| PHP | **8.2** (ea-php82) | Aplicado en MultiPHP Manager |
| WooCommerce | **10.9.3** | Actualizado |
| WPBakery (js_composer) | 5.7 | Parche PHP 8.2 aplicado — ver `deploy/patches/wpbakery-php82-ternary.md` |
| LayerSlider | 6.8.1 | Pendiente actualizar (requiere licencia Envato) |
| ACF PRO | 5.7.13 | Pendiente actualizar (requiere licencia) |
| Envato Market | 2.0.1 | **Activado** — falta token API en Ajustes |
| Sitio estático | `/site/` | Footer: Desarrollado por Tecnotix Solutions |
| Homepage | redirect 302 | `RewriteRule ^$ /site/` en `.htaccess` |

### Licencias pendientes (3 plugins)

Para actualizar ACF PRO, LayerSlider y WPBakery:

1. Ir a **Envato Market** en wp-admin
2. Generar token en https://build.envato.com/create-token/
3. Pegar token y guardar — aparecerán compras de ThemeForest/CodeCanyon
4. Actualizar en **Escritorio → Actualizaciones**

Sin token Envato, los 3 plugins premium no pueden descargar actualizaciones.

---

## Estado backup JetBackup (referencia histórica)

| Componente | Versión backup | Riesgo |
|------------|----------------|--------|
| WordPress | 7.0 | Medio — mantener actualizado |
| PHP | **7.4** (ea-php74) | **Alto — EOL, sin soporte** |
| WooCommerce | **3.6.7** | **Crítico — muy desactualizado** |
| WPBakery (js_composer) | 5.7 | Alto |
| LayerSlider | 6.8.1 | Alto |
| Contact Form 7 | 6.1.6 | Bajo |
| Tema | Aurum + aurum-child | Medio |

---

## 1. Migración PHP 7.4 → 8.2 (prioridad máxima)

### En cPanel
1. **MultiPHP Manager** → seleccionar dominio `tuexhibidor.cl` → **PHP 8.2** (o 8.3 si hosting lo ofrece).
2. **MultiPHP INI Editor** → verificar:
   - `memory_limit = 256M`
   - `max_execution_time = 240`
   - `upload_max_filesize = 64M`
   - `post_max_size = 64M`

### Después del cambio
- Revisar `/wp-admin/` y home.
- Si hay pantalla blanca: volver temporalmente a 8.1 y revisar `wp-content/debug.log`.

### Incompatibilidades conocidas
- **WooCommerce 3.6.7** no es compatible con PHP 8.x → **actualizar WooCommerce ANTES o junto con PHP**.
- **WPBakery 5.7** puede fallar en PHP 8.2 → actualizar a versión compatible con WP 7.x o sustituir páginas por bloques nativos.

---

## 2. Orden de actualización de plugins (staging recomendado)

Hacer **backup completo JetBackup** antes de cada fase.

| Orden | Plugin | Acción |
|-------|--------|--------|
| 1 | Backup JetBackup | Snapshot completo |
| 2 | WooCommerce | 3.6.7 → última compatible con WP 7 (vía dashboard o manual) |
| 3 | Contact Form 7 | Actualizar |
| 4 | WP Mail SMTP | Actualizar |
| 5 | Advanced Custom Fields PRO | Actualizar licencia Laborator/Envato |
| 6 | WPBakery | Actualizar o planificar migración |
| 7 | LayerSlider | Actualizar o desactivar si no se usa |
| 8 | Akismet, Classic Editor, WPS Hide Login | Actualizar |
| 9 | Desactivar plugins `-OFF` (Mailchimp, Facebook, YITH) si no se usan | Eliminar |

### Plugins a eliminar si no se usan
- `hello-dolly`
- `maintenance` + `wp-maintenance-mode` (duplicados — dejar uno)
- Carpetas `*-OFF`

---

## 3. Seguridad — ya incluido en backup

Archivo creado localmente:
```
wp-content/mu-plugins/tuexhibidor-security.php
```

**Subir a producción** vía FTP/cPanel File Manager.

Incluye:
- Cabeceras `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `HSTS`
- Ocultar versión WP
- Desactivar XML-RPC
- Restringir listado de usuarios REST

### wp-config.php (añadir si no existen)
```php
define('DISALLOW_FILE_EDIT', true);
define('WP_AUTO_UPDATE_CORE', 'minor');
```

### Credenciales
- Rotar contraseña DB si el backup fue compartido.
- Regenerar salts: https://api.wordpress.org/secret-key/1.1/salt/

### Login
- Mantener **WPS Hide Login** con URL personalizada.
- Usuarios admin: solo los necesarios, 2FA si el hosting lo permite.

---

## 4. Tema aurum-child — fixes aplicados

- Eliminación widgets demo footer (Suiza/Europa/Américas)
- Cabeceras seguridad complementarias en `functions.php`

**Desplegar:** subir `wp-content/themes/aurum-child/functions.php` actualizado.

---

## 5. WooCommerce catálogo sin precios

Ya configurado en `aurum-child/functions.php`:
- Precios ocultos
- Carrito deshabilitado
- Botones WhatsApp Alfonso + Leder

Tras actualizar WooCommerce, verificar que los filtros siguen activos.

---

## 6. Importar productos nuevos

1. Subir imágenes de `public/images/catalog/` → `wp-content/uploads/catalog/`
2. **WooCommerce → Productos → Importar** → `import/catalogo-completo.csv`
3. Mapear columnas; **no importar precios**
4. Verificar categorías: vitrina, cadenas-y-collares, aretes-y-anillos, pulseras-y-relojes

---

## 7. Checklist post-actualización

- [x] PHP 8.2 activo
- [x] WooCommerce ≥ 8.x funcional
- [ ] Formulario contacto envía email
- [ ] WhatsApp flotante operativo
- [ ] Sin widgets demo en footer
- [ ] `/shop/` carga productos
- [ ] SSL válido (AutoSSL)
- [ ] JetBackup programado semanal

---

## 8. Bloqueadores que requieren acceso servidor

| Tarea | Requiere |
|-------|----------|
| Cambiar PHP | cPanel |
| Actualizar WooCommerce mayor | wp-admin + backup |
| Importar CSV masivo | wp-admin |
| Canva live sync | Export PDF manual desde Canva (link compartido) |

**Canva:** https://canva.link/v74u1w40pocahcs — design `DAGtWc0EeJQ`. El scraping web está bloqueado; usar export PDF (ya en `PhotosDrive/Documentos/`).
