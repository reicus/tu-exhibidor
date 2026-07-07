<?php
/**
 * Página 404 amigable — estilo sitio premium Tu Exhibidor.
 *
 * @package Aurum Child
 */

get_header();

$wa_msg = rawurlencode( 'Hola, estaba buscando algo en tuexhibidor.cl y no encontré la página. ¿Me pueden ayudar?' );
$home   = esc_url( home_url( '/site/' ) );
$shop   = esc_url( home_url( '/shop/' ) );
$catalog = esc_url( home_url( '/site/#catalogo' ) );
$logo   = esc_url( home_url( '/public/images/brand/logo-tuexhibidor-gold-96.webp' ) );
$path   = isset( $_SERVER['REQUEST_URI'] ) ? esc_html( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
?>
<main class="te-404" id="te-404">
	<div class="te-404__inner">
		<p class="te-404__watermark" aria-hidden="true">404</p>
		<img class="te-404__logo" src="<?php echo $logo; ?>" alt="Tu Exhibidor" width="56" height="56" loading="lazy">
		<p class="te-404__eyebrow">Página no encontrada</p>
		<h1 class="te-404__title">Este enlace ya no está disponible</h1>
		<p class="te-404__lead">Puede que el producto haya cambiado de nombre, que el enlace sea antiguo o que simplemente te hayas equivocado de dirección. No te preocupes: cotiza directo con quien fabrica tus exhibidores.</p>
		<?php if ( $path ) : ?>
			<p class="te-404__path"><span>Ruta buscada:</span> <code><?php echo $path; ?></code></p>
		<?php endif; ?>

		<div class="te-404__card">
			<h2 class="te-404__card-title">¿Con quién quieres hablar?</h2>
			<p class="te-404__card-sub">Atención directa del taller — respuesta el mismo día</p>
			<div class="te-404__contacts">
				<a class="te-404__contact" href="https://wa.me/56937490214?text=<?php echo esc_attr( $wa_msg ); ?>" target="_blank" rel="nofollow noopener">
					<span class="te-404__contact-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
					</span>
					<span class="te-404__contact-name">Alfonso Orozco</span>
					<span class="te-404__contact-num">+56 9 3749 0214</span>
				</a>
				<a class="te-404__contact" href="https://wa.me/56991327813?text=<?php echo esc_attr( $wa_msg ); ?>" target="_blank" rel="nofollow noopener">
					<span class="te-404__contact-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
					</span>
					<span class="te-404__contact-name">Leder Mejia</span>
					<span class="te-404__contact-num">+56 9 9132 7813</span>
				</a>
			</div>
			<p class="te-404__email">También por correo: <a href="mailto:info@tuexhibidor.cl">info@tuexhibidor.cl</a></p>
			<?php echo te_social_links_html( 'te-404__social social-links', 'social-link', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

		<nav class="te-404__nav" aria-label="Enlaces útiles">
			<a class="te-404__btn te-404__btn--gold" href="<?php echo $home; ?>">Volver al inicio</a>
			<a class="te-404__btn te-404__btn--outline" href="<?php echo $catalog; ?>">Explorar catálogo</a>
			<a class="te-404__btn te-404__btn--outline" href="<?php echo $shop; ?>">Ver tienda</a>
		</nav>
	</div>
</main>
<?php
get_footer();
