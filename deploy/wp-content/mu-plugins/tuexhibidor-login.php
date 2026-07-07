<?php
/**
 * Plugin Name: Tu Exhibidor — Login personalizado
 * Description: Pantalla de acceso con diseño premium y aviso de uso administrativo.
 * Version: 1.0.1
 * Author: Tecnotix Solutions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'login_enqueue_scripts', 'tuex_login_styles' );
add_filter( 'login_headerurl', 'tuex_login_header_url' );
add_filter( 'login_headertext', 'tuex_login_header_text' );
add_filter( 'login_message', 'tuex_login_admin_notice' );
add_action( 'login_footer', 'tuex_login_footer_links' );

function tuex_login_header_url(): string {
	return home_url( '/site/' );
}

function tuex_login_header_text(): string {
	return 'Tu Exhibidor — Exhibidores de alta joyería';
}

function tuex_login_admin_notice( string $message ): string {
	$notice = '
	<div class="tuex-login-notice" role="status">
		<p class="tuex-login-notice__eyebrow">Acceso restringido</p>
		<p class="tuex-login-notice__title">Solo administradores autorizados</p>
		<p class="tuex-login-notice__text">
			Esta área es para gestión interna del sitio. Si llegaste aquí por error,
			<a href="' . esc_url( home_url( '/site/' ) ) . '">vuelve al sitio público</a>.
		</p>
	</div>';

	return $notice . $message;
}

function tuex_login_social_links(): string {
	$facebook = 'https://facebook.com/tuexhibidor.cl';
	$instagram = 'https://www.instagram.com/tuexhibidor/';

	$icon_fb = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>';
	$icon_ig = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>';

	return '
	<div class="tuex-login-socials" aria-label="Síguenos en redes sociales">
		<a class="tuex-login-social" href="' . esc_url( $facebook ) . '" target="_blank" rel="noopener noreferrer" aria-label="Facebook Tu Exhibidor">' . $icon_fb . '</a>
		<a class="tuex-login-social" href="' . esc_url( $instagram ) . '" target="_blank" rel="noopener noreferrer" aria-label="Instagram Tu Exhibidor">' . $icon_ig . '</a>
	</div>';
}

function tuex_login_footer_links(): void {
	$home = esc_url( home_url( '/site/' ) );
	$pdf  = esc_url( home_url( '/wp-content/uploads/2026/07/catalogo_tuexhibidor.pdf' ) );
	echo tuex_login_social_links();
	echo '<p class="tuex-login-catalog"><a class="tuex-login-catalog-btn" href="' . $pdf . '" target="_blank" rel="noopener">Catálogo completo (PDF)</a></p>';
	echo '<p class="tuex-login-back"><a href="' . $home . '">&larr; Ir al sitio de Tu Exhibidor</a></p>';
}

function tuex_login_styles(): void {
	$logo = esc_url( home_url( '/public/images/brand/logo-tuexhibidor-gold-96.webp' ) );

	wp_enqueue_style(
		'tuex-login-fonts',
		'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=Poppins:wght@400;500;600&display=swap',
		array(),
		null
	);

	$css = "
		body.login {
			background: linear-gradient(160deg, #ebe3d8 0%, #e3d9cc 45%, #ddd3c8 100%);
			font-family: 'Poppins', sans-serif;
			color: #2b2926;
			min-height: 100vh;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
		}
		body.login #login {
			width: min(420px, calc(100vw - 32px));
			padding: 24px 0 40px;
		}
		body.login #login h1 a {
			background-image: url('{$logo}');
			background-size: contain;
			background-position: center;
			background-repeat: no-repeat;
			width: 88px;
			height: 88px;
			margin: 0 auto 8px;
		}
		.tuex-login-notice {
			background: #fff;
			border: 1px solid rgba(184, 147, 95, 0.35);
			border-left: 4px solid #b8935f;
			border-radius: 14px;
			padding: 18px 20px;
			margin-bottom: 20px;
			box-shadow: 0 8px 28px rgba(43, 41, 38, 0.08);
		}
		.tuex-login-notice__eyebrow {
			margin: 0 0 4px;
			font-size: 11px;
			letter-spacing: 0.14em;
			text-transform: uppercase;
			color: #96723f;
			font-weight: 600;
		}
		.tuex-login-notice__title {
			margin: 0 0 8px;
			font-family: 'Playfair Display', serif;
			font-size: 20px;
			font-weight: 600;
			color: #2b2926;
			line-height: 1.3;
		}
		.tuex-login-notice__text {
			margin: 0;
			font-size: 13.5px;
			line-height: 1.55;
			color: #5a544c;
		}
		.tuex-login-notice__text a {
			color: #96723f;
			font-weight: 500;
			text-decoration: none;
		}
		.tuex-login-notice__text a:hover {
			color: #b8935f;
			text-decoration: underline;
		}
		body.login form {
			background: #fff;
			border: 1px solid rgba(184, 147, 95, 0.22);
			border-radius: 16px;
			box-shadow: 0 12px 36px rgba(43, 41, 38, 0.1);
			padding: 26px 24px 20px;
			margin-top: 0;
		}
		body.login form .input,
		body.login input[type='text'],
		body.login input[type='password'] {
			border: 1px solid #d8cfc4;
			border-radius: 10px;
			background: #faf7f2;
			font-size: 15px;
			padding: 10px 12px;
			box-shadow: none;
		}
		body.login form .input:focus,
		body.login input[type='text']:focus,
		body.login input[type='password']:focus {
			border-color: #b8935f;
			box-shadow: 0 0 0 3px rgba(184, 147, 95, 0.18);
		}
		body.login label {
			font-size: 13px;
			font-weight: 500;
			color: #5a544c;
		}
		body.login .button-primary {
			background: linear-gradient(135deg, #b8935f, #96723f) !important;
			border: none !important;
			border-radius: 999px !important;
			box-shadow: 0 6px 18px rgba(150, 114, 63, 0.35) !important;
			font-weight: 600;
			letter-spacing: 0.04em;
			text-shadow: none !important;
			padding: 8px 18px !important;
			height: auto !important;
			transition: transform 0.2s ease, box-shadow 0.2s ease;
		}
		body.login .button-primary:hover,
		body.login .button-primary:focus {
			background: linear-gradient(135deg, #c9a36f, #a67f45) !important;
			transform: translateY(-1px);
			box-shadow: 0 10px 22px rgba(150, 114, 63, 0.4) !important;
		}
		body.login #nav,
		body.login #backtoblog {
			text-align: center;
			padding: 0;
		}
		body.login #nav a,
		body.login #backtoblog a {
			color: #96723f !important;
			font-size: 13px;
			font-weight: 500;
		}
		body.login #nav a:hover,
		body.login #backtoblog a:hover {
			color: #b8935f !important;
		}
		body.login .message,
		body.login .success,
		body.login #login_error {
			border-radius: 12px;
			border-left-width: 4px;
			box-shadow: 0 4px 16px rgba(43, 41, 38, 0.06);
		}
		.tuex-login-back {
			text-align: center;
			margin: 14px 0 0;
		}
		.tuex-login-socials {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 12px;
			margin: 20px 0 0;
		}
		.tuex-login-social {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 42px;
			height: 42px;
			border-radius: 50%;
			color: #96723f;
			background: rgba(255, 255, 255, 0.75);
			border: 1px solid rgba(184, 147, 95, 0.3);
			text-decoration: none;
			transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
		}
		.tuex-login-social:hover {
			background: #b8935f;
			color: #fff;
			transform: translateY(-2px);
		}
		.tuex-login-catalog {
			text-align: center;
			margin: 12px 0 0;
		}
		.tuex-login-catalog-btn {
			display: inline-block;
			padding: 7px 16px;
			border-radius: 999px;
			border: 1px solid rgba(184, 147, 95, 0.45);
			color: #96723f;
			background: rgba(255, 255, 255, 0.75);
			font-size: 12px;
			font-weight: 500;
			text-decoration: none;
			transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
		}
		.tuex-login-catalog-btn:hover {
			background: #b8935f;
			border-color: #b8935f;
			color: #fff;
			transform: translateY(-1px);
		}
		.tuex-login-back a {
			display: inline-block;
			padding: 10px 20px;
			border-radius: 999px;
			background: rgba(255, 255, 255, 0.75);
			border: 1px solid rgba(184, 147, 95, 0.3);
			color: #96723f !important;
			text-decoration: none;
			font-size: 13px;
			font-weight: 500;
			transition: background 0.2s ease, color 0.2s ease;
		}
		.tuex-login-back a:hover {
			background: #fff;
			color: #b8935f !important;
		}
		body.login #backtoblog {
			display: none;
		}
		body.login .privacy-policy-page-link {
			text-align: center;
			margin-top: 12px;
		}
		body.login .language-switcher {
			margin-top: 16px;
		}
	";

	wp_enqueue_style( 'login' );
	wp_add_inline_style( 'login', $css );
}
