<?php
/**
 * Plugin Name: WF Restaurant
 * Plugin URI:  https://www.we-frame.fr
 * Description: Éléments YOOtheme pour restaurateurs (Horaires/Statut, Menu, Réservation, Annonce) + une interface mobile ultra-simple (WeFrame → Mon restaurant) : horaires, ouvert/fermé, fermeture exceptionnelle, menu pilotable (plat du jour / épuisé), bandeau d'annonce, et réservations de table reçues sur le site. Les bandeaux du site se mettent à jour tout seuls (source centrale).
 * Version:     1.17.0
 * Requires PHP: 7.4
 * Author:      WeFrame Studio
 * Author URI:  https://www.we-frame.fr
 * License:     GPL-2.0+
 * Text Domain: wf-restaurant
 */

defined( 'ABSPATH' ) || exit;

define( 'WF_RESTO_VER', '1.17.0' );
define( 'WF_RESTO_OPT', 'wf_resto' );
define( 'WF_RESTO_MENU_OPT', 'wf_resto_menu' );
define( 'WF_RESTO_CAP', 'wf_resto_manage' );

/* ============================================================
 *  1. CHARGEMENT DES ÉLÉMENTS YOOTHEME
 * ========================================================== */

add_action( 'after_setup_theme', 'wf_resto_load_builder', 5 );
function wf_resto_load_builder() {
	if ( ! class_exists( 'YOOtheme\\Application', false ) ) { return; }
	$path = __DIR__ . '/modules/bootstrap.php';
	if ( is_file( $path ) ) {
		YOOtheme\Application::getInstance()->load( $path );
	}
}

/* ============================================================
 *  2. STORE CENTRAL (option wf_resto)
 * ========================================================== */

function wf_resto_days_keys() {
	return array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );
}
function wf_resto_days_labels() {
	return array(
		'mon' => 'Lundi', 'tue' => 'Mardi', 'wed' => 'Mercredi', 'thu' => 'Jeudi',
		'fri' => 'Vendredi', 'sat' => 'Samedi', 'sun' => 'Dimanche',
	);
}

function wf_resto_defaults() {
	$mk = function ( $en, $lo, $lc, $do, $dc ) {
		return array( 'enabled' => $en, 'lunch_open' => $lo, 'lunch_close' => $lc, 'dinner_open' => $do, 'dinner_close' => $dc );
	};
	return array(
		'tz'        => 'Europe/Paris',
		'override'  => 'auto',
		'exception' => array( 'active' => 0, 'from' => '', 'to' => '', 'message' => '' ),
		'announce'  => array( 'active' => 0, 'message' => '', 'link_text' => '', 'link_url' => '' ),
		'resa_email' => '',
		'resa'      => array( 'enabled' => 1, 'interval' => 30, 'last_before' => 60, 'max_lunch' => 0, 'max_dinner' => 0, 'max_per_slot' => 0, 'days_ahead' => 30, 'min_party' => 1, 'max_party' => 0, 'buffer_min' => 0, 'blocked' => array() ),
		'days'      => array(
			'mon' => $mk( 1, '12:00', '14:30', '19:00', '22:30' ),
			'tue' => $mk( 1, '12:00', '14:30', '19:00', '22:30' ),
			'wed' => $mk( 1, '12:00', '14:30', '19:00', '22:30' ),
			'thu' => $mk( 1, '12:00', '14:30', '19:00', '22:30' ),
			'fri' => $mk( 1, '12:00', '14:30', '19:00', '23:00' ),
			'sat' => $mk( 1, '12:00', '14:30', '19:00', '23:00' ),
			'sun' => $mk( 0, '', '', '', '' ),
		),
	);
}

/** Retourne le store fusionné avec les valeurs par défaut. */
function wf_resto_get() {
	$def = wf_resto_defaults();
	$opt = get_option( WF_RESTO_OPT, array() );
	if ( ! is_array( $opt ) ) { $opt = array(); }
	$out = array(
		'tz'         => ! empty( $opt['tz'] ) ? $opt['tz'] : $def['tz'],
		'override'   => isset( $opt['override'] ) && in_array( $opt['override'], array( 'auto', 'open', 'closed' ), true ) ? $opt['override'] : 'auto',
		'exception'  => isset( $opt['exception'] ) && is_array( $opt['exception'] ) ? array_merge( $def['exception'], $opt['exception'] ) : $def['exception'],
		'announce'   => isset( $opt['announce'] ) && is_array( $opt['announce'] ) ? array_merge( $def['announce'], $opt['announce'] ) : $def['announce'],
		'resa_email' => isset( $opt['resa_email'] ) ? $opt['resa_email'] : '',
		'resa'       => isset( $opt['resa'] ) && is_array( $opt['resa'] ) ? array_merge( $def['resa'], $opt['resa'] ) : $def['resa'],
		'days'       => array(),
	);
	foreach ( wf_resto_days_keys() as $d ) {
		$row          = isset( $opt['days'][ $d ] ) && is_array( $opt['days'][ $d ] ) ? $opt['days'][ $d ] : $def['days'][ $d ];
		$out['days'][ $d ] = array(
			'enabled'      => ! empty( $row['enabled'] ) ? 1 : 0,
			'lunch_open'   => isset( $row['lunch_open'] ) ? $row['lunch_open'] : '',
			'lunch_close'  => isset( $row['lunch_close'] ) ? $row['lunch_close'] : '',
			'dinner_open'  => isset( $row['dinner_open'] ) ? $row['dinner_open'] : '',
			'dinner_close' => isset( $row['dinner_close'] ) ? $row['dinner_close'] : '',
		);
	}
	return $out;
}

/** HH:MM valide, sinon ''. */
function wf_resto_time( $v ) {
	$v = trim( (string) $v );
	if ( '' === $v ) { return ''; }
	if ( preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', $v ) ) {
		return sprintf( '%02d:%02d', (int) substr( $v, 0, strpos( $v, ':' ) ), (int) substr( $v, strpos( $v, ':' ) + 1 ) );
	}
	return '';
}

/**
 * Plage horaire en minutes depuis minuit : [ début, fin ].
 *
 * Un service du soir 19:00 → 01:00 a une heure de fin plus petite que son
 * heure de début. Le code comparait bêtement les deux et concluait « plage
 * vide » : zéro créneau réservable et un badge « fermé » toute la soirée.
 * Ici la fin repasse au-delà de minuit (01:00 devient 1500), et c'est la
 * seule règle à connaître dans tout le plugin.
 *
 * @return array|null [ $debut, $fin ] ou null si la plage est vide.
 */
function wf_resto_window( $open, $close ) {
	$o = wf_resto_hm( $open );
	$c = wf_resto_hm( $close );
	if ( null === $o || null === $c ) { return null; }
	if ( $c === $o ) { return null; }
	if ( $c < $o ) { $c += 1440; }
	return array( $o, $c );
}

/** Les deux plages d'une journée, minuit déjà pris en compte. */
function wf_resto_day_windows( $row ) {
	$out = array();
	if ( empty( $row['enabled'] ) ) { return $out; }
	foreach ( array( 'lunch', 'dinner' ) as $svc ) {
		$w = wf_resto_window( $row[ $svc . '_open' ] ?? '', $row[ $svc . '_close' ] ?? '' );
		if ( $w ) { $out[] = array( 'from' => $w[0], 'to' => $w[1], 'service' => 'lunch' === $svc ? 'midi' : 'soir' ); }
	}
	return $out;
}

/** Clé du jour précédant $key. */
function wf_resto_prev_day( $key ) {
	$k = wf_resto_days_keys();
	$i = array_search( $key, $k, true );
	if ( false === $i ) { return $k[0]; }
	return $k[ ( $i + 6 ) % 7 ];
}

/** Fuseau du restaurant, jamais celui de WordPress. */
function wf_resto_tz() {
	$s = wf_resto_get();
	try {
		return new DateTimeZone( $s['tz'] ? $s['tz'] : 'Europe/Paris' );
	} catch ( Exception $e ) {
		return new DateTimeZone( 'Europe/Paris' );
	}
}

/**
 * Date du jour dans le fuseau du restaurant.
 *
 * current_time() suit le fuseau de WordPress, resté sur UTC sur beaucoup
 * d'installations. Les horaires, eux, sont calculés dans le fuseau du resto :
 * après minuit à Paris, les deux ne désignaient plus le même jour et l'écran
 * « Aujourd'hui » listait les réservations de la veille.
 */
function wf_resto_today() {
	$now = new DateTime( 'now', wf_resto_tz() );
	return $now->format( 'Y-m-d' );
}

/** Minutes écoulées depuis minuit, dans le fuseau du restaurant. */
function wf_resto_now_min() {
	$now = new DateTime( 'now', wf_resto_tz() );
	return (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' );
}

/** État courant calculé depuis le store (exception > override > horaires). */
function wf_resto_is_open_now() {
	$s   = wf_resto_get();
	$now = new DateTime( 'now', wf_resto_tz() );
	$dow   = strtolower( $now->format( 'D' ) ); // mon,tue…
	$map   = array( 'mon' => 'mon', 'tue' => 'tue', 'wed' => 'wed', 'thu' => 'thu', 'fri' => 'fri', 'sat' => 'sat', 'sun' => 'sun' );
	$key   = isset( $map[ $dow ] ) ? $map[ $dow ] : 'mon';
	$min   = (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' );
	$label = 'Fermé';
	$open  = false;

	// Les plages du jour, puis la queue de la veille : à 00h30 on est encore
	// dans le service d'hier soir si celui-ci va jusqu'à 01h00.
	foreach ( wf_resto_day_windows( $s['days'][ $key ] ) as $w ) {
		if ( $min >= $w['from'] && $min < $w['to'] ) { $open = true; break; }
	}
	if ( ! $open ) {
		foreach ( wf_resto_day_windows( $s['days'][ wf_resto_prev_day( $key ) ] ) as $w ) {
			if ( $w['to'] > 1440 && $min < $w['to'] - 1440 ) { $open = true; break; }
		}
	}
	// Exception d'abord, puis override manuel, puis horaires.
	// C'est la période qui compte, pas la case « active » : une fermeture
	// programmée pour plus tard ne doit pas neutraliser le bouton Ouvert/Fermé.
	$msg    = '';
	$in_exc = false;
	if ( ! empty( $s['exception']['active'] ) ) {
		$today = $now->format( 'Y-m-d' );
		$from  = $s['exception']['from'];
		$to    = $s['exception']['to'];
		$ge    = ( '' === $from || $today >= $from );
		$le    = ( '' === $to || $today <= $to );
		$in_exc = ( $ge && $le );
	}
	if ( $in_exc ) {
		$open = false;
		$msg  = $s['exception']['message'];
	} elseif ( 'open' === $s['override'] ) {
		$open = true;
	} elseif ( 'closed' === $s['override'] ) {
		$open = false;
	}
	if ( $open ) { $label = 'Ouvert'; } elseif ( '' !== $msg ) { $label = $msg; }
	return array( 'open' => $open, 'label' => $label, 'message' => $msg );
}
function wf_resto_hm( $s ) {
	$s = (string) $s;
	if ( '' === $s || false === strpos( $s, ':' ) ) { return null; }
	$p = explode( ':', $s );
	return (int) $p[0] * 60 + (int) $p[1];
}

/* Purge des caches pour rafraîchir les bandeaux après un changement. */
function wf_resto_purge() {
	if ( function_exists( 'wp_cache_flush' ) ) { wp_cache_flush(); }
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%yootheme%' OR option_name LIKE '\\_transient\\_timeout\\_%yootheme%'" );
	if ( function_exists( 'rocket_clean_domain' ) ) { rocket_clean_domain(); }
	if ( defined( 'LSCWP_V' ) ) { do_action( 'litespeed_purge_all' ); }
	if ( function_exists( 'w3tc_flush_all' ) ) { w3tc_flush_all(); }
	if ( defined( 'WPFC_MAIN_PATH' ) ) { do_action( 'wpfc_clear_all_cache', true ); }
}

/* ============================================================
 *  3. RÔLE « RESTAURATEUR » + CAPACITÉ
 * ========================================================== */

register_activation_hook( __FILE__, 'wf_resto_activate' );
function wf_resto_activate() {
	wf_resto_setup_roles();
}
function wf_resto_setup_roles() {
	add_role( 'wf_restaurateur', 'Restaurateur', array( 'read' => true, WF_RESTO_CAP => true ) );
	$admin = get_role( 'administrator' );
	if ( $admin && ! $admin->has_cap( WF_RESTO_CAP ) ) { $admin->add_cap( WF_RESTO_CAP ); }
	$role = get_role( 'wf_restaurateur' );
	if ( $role && ! $role->has_cap( WF_RESTO_CAP ) ) { $role->add_cap( WF_RESTO_CAP ); }
	update_option( 'wf_resto_setup', WF_RESTO_VER );
}
/* Idempotent : garantit rôle + capacité même après une simple mise à jour du plugin. */
add_action( 'admin_init', 'wf_resto_maybe_setup', 1 );
function wf_resto_maybe_setup() {
	if ( get_option( 'wf_resto_setup' ) !== WF_RESTO_VER ) { wf_resto_setup_roles(); }
}

/* ============================================================
 *  4. INTERFACE MOBILE : WeFrame → Mon restaurant
 * ========================================================== */

add_action( 'admin_menu', 'wf_resto_menu' );
function wf_resto_menu() {
	add_menu_page( 'Mon restaurant', 'Mon restaurant', WF_RESTO_CAP, 'wf-restaurant', 'wf_resto_render', 'dashicons-store', 4 );
	add_submenu_page( 'wf-restaurant', 'Réglages', 'Réglages', WF_RESTO_CAP, 'wf-restaurant', 'wf_resto_render' );
	add_submenu_page( 'wf-restaurant', 'Menu', 'Menu', WF_RESTO_CAP, 'wf-restaurant-menu', 'wf_resto_menu_render' );

	$new   = wf_resa_count_new();
	$label = 'Réservations' . ( $new > 0 ? ' <span class="awaiting-mod"><span class="pending-count">' . (int) $new . '</span></span>' : '' );
	add_submenu_page( 'wf-restaurant', 'Réservations', $label, WF_RESTO_CAP, 'wf-restaurant-resa', 'wf_resa_render' );
}

/* Enregistrement / sauvegarde. */
add_action( 'admin_post_wf_resto_quick', 'wf_resto_quick' );
function wf_resto_quick() {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { wp_die( 'Refusé.' ); }
	check_admin_referer( 'wf_resto' );
	$val = isset( $_POST['override'] ) ? sanitize_key( $_POST['override'] ) : 'auto';
	if ( ! in_array( $val, array( 'auto', 'open', 'closed' ), true ) ) { $val = 'auto'; }
	$s             = wf_resto_get();
	$s['override'] = $val;
	update_option( WF_RESTO_OPT, $s );
	wf_resto_purge();
	set_transient( 'wf_resto_notice_' . get_current_user_id(), 'Statut mis à jour.', 30 );
	wp_safe_redirect( admin_url( 'admin.php?page=wf-restaurant' ) );
	exit;
}

add_action( 'admin_post_wf_resto_save', 'wf_resto_save' );
function wf_resto_save() {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { wp_die( 'Refusé.' ); }
	check_admin_referer( 'wf_resto' );

	$s = wf_resto_get();

	if ( isset( $_POST['tz'] ) ) {
		$tz = sanitize_text_field( wp_unslash( $_POST['tz'] ) );
		if ( in_array( $tz, timezone_identifiers_list(), true ) ) { $s['tz'] = $tz; }
	}
	$ov = isset( $_POST['override'] ) ? sanitize_key( $_POST['override'] ) : 'auto';
	$s['override'] = in_array( $ov, array( 'auto', 'open', 'closed' ), true ) ? $ov : 'auto';

	$days = isset( $_POST['days'] ) && is_array( $_POST['days'] ) ? wp_unslash( $_POST['days'] ) : array();
	foreach ( wf_resto_days_keys() as $d ) {
		$row                = isset( $days[ $d ] ) && is_array( $days[ $d ] ) ? $days[ $d ] : array();
		$s['days'][ $d ] = array(
			'enabled'      => ! empty( $row['enabled'] ) ? 1 : 0,
			'lunch_open'   => wf_resto_time( $row['lunch_open']   ?? '' ),
			'lunch_close'  => wf_resto_time( $row['lunch_close']  ?? '' ),
			'dinner_open'  => wf_resto_time( $row['dinner_open']  ?? '' ),
			'dinner_close' => wf_resto_time( $row['dinner_close'] ?? '' ),
		);
	}

	$exc = isset( $_POST['exc'] ) && is_array( $_POST['exc'] ) ? wp_unslash( $_POST['exc'] ) : array();
	$from = isset( $exc['from'] ) ? preg_replace( '/[^0-9\-]/', '', $exc['from'] ) : '';
	$to   = isset( $exc['to'] ) ? preg_replace( '/[^0-9\-]/', '', $exc['to'] ) : '';
	$s['exception'] = array(
		'active'  => ! empty( $exc['active'] ) ? 1 : 0,
		'from'    => $from,
		'to'      => $to,
		'message' => isset( $exc['message'] ) ? sanitize_text_field( $exc['message'] ) : '',
	);

	$an = isset( $_POST['announce'] ) && is_array( $_POST['announce'] ) ? wp_unslash( $_POST['announce'] ) : array();
	$s['announce'] = array(
		'active'    => ! empty( $an['active'] ) ? 1 : 0,
		'message'   => isset( $an['message'] ) ? sanitize_text_field( $an['message'] ) : '',
		'link_text' => isset( $an['link_text'] ) ? sanitize_text_field( $an['link_text'] ) : '',
		'link_url'  => isset( $an['link_url'] ) ? esc_url_raw( trim( $an['link_url'] ) ) : '',
	);

	$s['resa_email'] = isset( $_POST['resa_email'] ) && '' !== trim( $_POST['resa_email'] ) ? sanitize_email( wp_unslash( $_POST['resa_email'] ) ) : '';

	$r = isset( $_POST['resa'] ) && is_array( $_POST['resa'] ) ? wp_unslash( $_POST['resa'] ) : array();
	$blocked = array();
	if ( isset( $r['blocked'] ) ) {
		foreach ( preg_split( '/[\s,;]+/', (string) $r['blocked'] ) as $d ) {
			$d = trim( $d );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ) { $blocked[] = $d; }
		}
		$blocked = array_values( array_unique( $blocked ) );
	}
	$s['resa'] = array(
		'enabled'      => ! empty( $r['enabled'] ) ? 1 : 0,
		'interval'     => max( 5, min( 120, (int) ( $r['interval'] ?? 30 ) ) ),
		'last_before'  => max( 0, min( 240, (int) ( $r['last_before'] ?? 60 ) ) ),
		'max_lunch'    => max( 0, (int) ( $r['max_lunch'] ?? 0 ) ),
		'max_dinner'   => max( 0, (int) ( $r['max_dinner'] ?? 0 ) ),
		'max_per_slot' => max( 0, (int) ( $r['max_per_slot'] ?? 0 ) ),
		'days_ahead'   => max( 1, min( 365, (int) ( $r['days_ahead'] ?? 30 ) ) ),
		'min_party'    => max( 1, min( 50, (int) ( $r['min_party'] ?? 1 ) ) ),
		'max_party'    => max( 0, min( 50, (int) ( $r['max_party'] ?? 0 ) ) ),
		'buffer_min'   => max( 0, min( 1440, (int) ( $r['buffer_min'] ?? 0 ) ) ),
		'blocked'      => $blocked,
	);

	update_option( WF_RESTO_OPT, $s );
	wf_resto_purge();
	set_transient( 'wf_resto_notice_' . get_current_user_id(), 'Réglages enregistrés.', 30 );
	wp_safe_redirect( admin_url( 'admin.php?page=wf-restaurant' ) );
	exit;
}

function wf_resto_render() {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { return; }
	$s      = wf_resto_get();
	$state  = wf_resto_is_open_now();
	$labels = wf_resto_days_labels();
	$notice = get_transient( 'wf_resto_notice_' . get_current_user_id() );
	if ( false !== $notice ) { delete_transient( 'wf_resto_notice_' . get_current_user_id() ); }
	$post = esc_url( admin_url( 'admin-post.php' ) );
	?>
	<style>
		.wf-r-wrap{max-width:1180px;margin:10px 0}
		.wf-r-wrap *{box-sizing:border-box}
		.wf-r-grid{column-count:2;column-gap:16px}
		.wf-r-grid .wf-r-card{break-inside:avoid;-webkit-column-break-inside:avoid;margin:0 0 16px}
		@media(max-width:900px){.wf-r-grid{column-count:1}}
		.wf-r-live{display:flex;align-items:center;gap:12px;padding:16px 18px;border-radius:14px;margin:14px 0;font-size:18px;font-weight:700;
			background:<?php echo $state['open'] ? '#e6f4ea' : '#fde8e8'; ?>;color:<?php echo $state['open'] ? '#0a7d32' : '#b32d2e'; ?>}
		.wf-r-live .dot{width:14px;height:14px;border-radius:50%;background:currentColor;flex:0 0 auto;animation:wfrp 2s ease-in-out infinite}
		@keyframes wfrp{0%,100%{opacity:1}50%{opacity:.4}}
		.wf-r-card{background:#fff;border:1px solid #dcdcde;border-radius:14px;padding:18px 20px;margin:14px 0}
		.wf-r-card h2{margin:0 0 4px;font-size:17px}
		.wf-r-card p.d{color:#646970;font-size:13px;margin:0 0 14px}
		.wf-r-quick{display:flex;max-width:460px;border:1px solid #d0d5db;border-radius:12px;overflow:hidden;background:#fff}
		.wf-r-quick form{margin:0;flex:1;display:flex}
		.wf-r-quick button{flex:1;width:100%;min-height:54px;padding:8px 6px;border:0;border-left:1px solid #e5e7eb;background:#fff;font-size:15px;font-weight:700;cursor:pointer;line-height:1.15;color:#374151;transition:background .15s,color .15s}
		.wf-r-quick form:first-child button{border-left:0}
		.wf-r-quick button:hover{background:#f3f4f6}
		.wf-r-quick button .s{display:block;font-size:11px;font-weight:600;opacity:.6;margin-top:2px}
		.wf-r-quick button.on{color:#fff}
		.wf-r-quick button.on.auto{background:#2271b1}
		.wf-r-quick button.on.open{background:#0a7d32}
		.wf-r-quick button.on.closed{background:#b32d2e}
		.wf-r-quick button.on .s{opacity:.85}
		@media(max-width:520px){.wf-r-quick{max-width:100%}}
		.wf-r-day{display:grid;grid-template-columns:1fr;gap:6px;padding:10px 0;border-top:1px solid #f0f0f1}
		.wf-r-day:first-of-type{border-top:0}
		.wf-r-day .hd{display:flex;align-items:center;justify-content:space-between;font-weight:600}
		.wf-r-times{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:4px}
		.wf-r-times label{font-size:11px;color:#646970;display:block}
		.wf-r-times input[type=time]{width:100%;padding:9px;border:1px solid #dcdcde;border-radius:8px;font-size:15px}
		.wf-r-switch{position:relative;display:inline-flex;align-items:center;gap:8px;font-size:13px;color:#646970}
		.wf-r-btn{display:inline-block;width:100%;padding:15px;border:0;border-radius:12px;background:#2271b1;color:#fff;font-size:16px;font-weight:700;cursor:pointer;margin-top:6px}
		.wf-r-in{width:100%;padding:11px;border:1px solid #dcdcde;border-radius:8px;font-size:15px}
		@media(max-width:600px){.wf-r-times{grid-template-columns:1fr 1fr}}
	</style>

	<div class="wrap wf-r-wrap">
		<h1 style="font-size:22px;">🍽️ Mon restaurant</h1>

		<?php if ( false !== $notice ) : ?>
			<div class="notice notice-success is-dismissible" style="margin:10px 0;"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>

		<div class="wf-r-live"><span class="dot"></span><span><?php echo esc_html( $state['open'] ? 'Actuellement OUVERT' : ( '' !== $state['message'] ? $state['message'] : 'Actuellement FERMÉ' ) ); ?></span></div>

		<!-- Actions rapides : ouvert / fermé / auto -->
		<div class="wf-r-card">
			<h2>Ouvert / Fermé maintenant</h2>
			<p class="d">« Suivre les horaires » = automatique. « Ouvert » / « Fermé » force l'état, quel que soit l'horaire (utile coup de feu / imprévu).</p>
			<div class="wf-r-quick">
				<?php
				$btns = array(
					'auto'   => array( 'Suivre horaires', 'auto' ),
					'open'   => array( 'Ouvert', 'forcé' ),
					'closed' => array( 'Fermé', 'forcé' ),
				);
				foreach ( $btns as $val => $b ) :
					$on = ( $s['override'] === $val ) ? ' on ' . $val : '';
					?>
					<form method="post" action="<?php echo $post; ?>">
						<?php wp_nonce_field( 'wf_resto' ); ?>
						<input type="hidden" name="action" value="wf_resto_quick">
						<input type="hidden" name="override" value="<?php echo esc_attr( $val ); ?>">
						<button type="submit" class="<?php echo esc_attr( trim( $on ) ); ?>"><?php echo esc_html( $b[0] ); ?><span class="s"><?php echo esc_html( $b[1] ); ?></span></button>
					</form>
				<?php endforeach; ?>
			</div>
		</div>

		<form method="post" action="<?php echo $post; ?>">
			<?php wp_nonce_field( 'wf_resto' ); ?>
			<input type="hidden" name="action" value="wf_resto_save">
			<input type="hidden" name="override" value="<?php echo esc_attr( $s['override'] ); ?>">

			<div class="wf-r-grid">
			<!-- Fermeture exceptionnelle -->
			<div class="wf-r-card">
				<h2>Fermeture exceptionnelle</h2>
				<p class="d">Congés, jour férié, imprévu. Prioritaire sur tout : le bandeau affiche votre message aux dates indiquées.</p>
				<label class="wf-r-switch" style="font-size:15px;color:#1d2327;font-weight:600;margin-bottom:12px;">
					<input type="checkbox" name="exc[active]" value="1" <?php checked( ! empty( $s['exception']['active'] ) ); ?>> Activer une fermeture exceptionnelle
				</label>
				<div class="wf-r-times" style="grid-template-columns:1fr 1fr;">
					<div><label>Du</label><input class="wf-r-in" type="date" name="exc[from]" value="<?php echo esc_attr( $s['exception']['from'] ); ?>"></div>
					<div><label>Au</label><input class="wf-r-in" type="date" name="exc[to]" value="<?php echo esc_attr( $s['exception']['to'] ); ?>"></div>
				</div>
				<div style="margin-top:10px;"><label style="font-size:11px;color:#646970;">Message affiché</label>
					<input class="wf-r-in" type="text" name="exc[message]" value="<?php echo esc_attr( $s['exception']['message'] ); ?>" placeholder="Fermé pour congés, réouverture le 5 septembre" maxlength="120"></div>
			</div>

			<!-- Bandeau d'annonce -->
			<div class="wf-r-card">
				<h2>Bandeau d'annonce</h2>
				<p class="d">Message ponctuel affiché en haut du site (« terrasse ouverte », « menu spécial »…). Nécessite l'élément « Bandeau annonce » placé dans la page.</p>
				<label class="wf-r-switch" style="font-size:15px;color:#1d2327;font-weight:600;margin-bottom:12px;">
					<input type="checkbox" name="announce[active]" value="1" <?php checked( ! empty( $s['announce']['active'] ) ); ?>> Afficher le bandeau
				</label>
				<div style="margin-bottom:10px;"><label style="font-size:11px;color:#646970;">Message</label>
					<input class="wf-r-in" type="text" name="announce[message]" value="<?php echo esc_attr( $s['announce']['message'] ); ?>" placeholder="Ex : Terrasse ouverte ce week-end ☀️" maxlength="140"></div>
				<div class="wf-r-times" style="grid-template-columns:1fr 1fr;">
					<div><label style="font-size:11px;color:#646970;">Texte du lien (optionnel)</label><input class="wf-r-in" type="text" name="announce[link_text]" value="<?php echo esc_attr( $s['announce']['link_text'] ); ?>" placeholder="Réserver"></div>
					<div><label style="font-size:11px;color:#646970;">Lien (optionnel)</label><input class="wf-r-in" type="url" name="announce[link_url]" value="<?php echo esc_attr( $s['announce']['link_url'] ); ?>" placeholder="https://…"></div>
				</div>
			</div>

			<!-- Réservations : créneaux + capacité -->
			<?php $rz = $s['resa']; ?>
			<div class="wf-r-card">
				<h2>Réservations — créneaux & capacité</h2>
				<p class="d">Les créneaux proposés au client sont générés depuis vos horaires. La capacité limite le nombre total de couverts par service (midi / soir).</p>
				<label class="wf-r-switch" style="font-size:15px;color:#1d2327;font-weight:600;margin-bottom:12px;">
					<input type="checkbox" name="resa[enabled]" value="1" <?php checked( ! empty( $rz['enabled'] ) ); ?>> Activer les réservations en ligne
				</label>
				<div class="wf-r-times" style="grid-template-columns:1fr 1fr;">
					<div><label>Intervalle des créneaux</label>
						<select class="wf-r-in" name="resa[interval]">
							<?php foreach ( array( 15, 20, 30, 45, 60 ) as $iv ) : ?>
								<option value="<?php echo $iv; ?>" <?php selected( (int) $rz['interval'], $iv ); ?>><?php echo $iv; ?> min</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div><label>Dernière place avant fermeture</label>
						<select class="wf-r-in" name="resa[last_before]">
							<?php foreach ( array( 0 => 'à la fermeture', 30 => '30 min avant', 45 => '45 min avant', 60 => '1 h avant', 90 => '1 h 30 avant' ) as $lb => $lbl ) : ?>
								<option value="<?php echo (int) $lb; ?>" <?php selected( (int) $rz['last_before'], (int) $lb ); ?>><?php echo esc_html( $lbl ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div><label>Couverts max — midi (0 = illimité)</label><input class="wf-r-in" type="number" min="0" name="resa[max_lunch]" value="<?php echo (int) $rz['max_lunch']; ?>"></div>
					<div><label>Couverts max — soir (0 = illimité)</label><input class="wf-r-in" type="number" min="0" name="resa[max_dinner]" value="<?php echo (int) $rz['max_dinner']; ?>"></div>
					<div><label>Couverts max par créneau (0 = illimité)</label><input class="wf-r-in" type="number" min="0" name="resa[max_per_slot]" value="<?php echo (int) $rz['max_per_slot']; ?>"></div>
					<div><label>Réservable jusqu'à (jours à l'avance)</label><input class="wf-r-in" type="number" min="1" max="365" name="resa[days_ahead]" value="<?php echo (int) $rz['days_ahead']; ?>"></div>
					<div><label>Couverts min par réservation</label><input class="wf-r-in" type="number" min="1" max="50" name="resa[min_party]" value="<?php echo (int) $rz['min_party']; ?>"></div>
					<div><label>Couverts max par réservation (0 = illimité)</label><input class="wf-r-in" type="number" min="0" max="50" name="resa[max_party]" value="<?php echo (int) $rz['max_party']; ?>"></div>
					<div><label>Délai minimum avant réservation (min)</label><input class="wf-r-in" type="number" min="0" max="1440" step="15" name="resa[buffer_min]" value="<?php echo (int) $rz['buffer_min']; ?>"><span style="font-size:11px;color:#646970;">Ex : 120 = pas de résa &lt; 2 h. Masque aussi les créneaux passés du jour.</span></div>
				</div>
				<div style="margin-top:12px;">
					<label style="font-size:11px;color:#646970;">Jours fermés à la réservation — une date AAAA-MM-JJ par ligne</label>
					<textarea class="wf-r-in" name="resa[blocked]" rows="2" placeholder="2026-12-25&#10;2027-01-01"><?php echo esc_textarea( implode( "\n", (array) $rz['blocked'] ) ); ?></textarea>
				</div>
			</div>

			<!-- Horaires -->
			<div class="wf-r-card">
				<h2>Horaires de la semaine</h2>
				<p class="d">Midi et soir. Laissez vide un créneau non utilisé. Décochez un jour de fermeture hebdomadaire.</p>
				<?php foreach ( wf_resto_days_keys() as $d ) : $row = $s['days'][ $d ]; ?>
					<div class="wf-r-day">
						<div class="hd">
							<span><?php echo esc_html( $labels[ $d ] ); ?></span>
							<label class="wf-r-switch"><input type="checkbox" name="days[<?php echo $d; ?>][enabled]" value="1" <?php checked( ! empty( $row['enabled'] ) ); ?>> ouvert ce jour</label>
						</div>
						<div class="wf-r-times">
							<div><label>Midi — ouverture</label><input type="time" name="days[<?php echo $d; ?>][lunch_open]" value="<?php echo esc_attr( $row['lunch_open'] ); ?>"></div>
							<div><label>Midi — fermeture</label><input type="time" name="days[<?php echo $d; ?>][lunch_close]" value="<?php echo esc_attr( $row['lunch_close'] ); ?>"></div>
							<div><label>Soir — ouverture</label><input type="time" name="days[<?php echo $d; ?>][dinner_open]" value="<?php echo esc_attr( $row['dinner_open'] ); ?>"></div>
							<div><label>Soir — fermeture</label><input type="time" name="days[<?php echo $d; ?>][dinner_close]" value="<?php echo esc_attr( $row['dinner_close'] ); ?>"></div>
						</div>
					</div>
				<?php endforeach; ?>
				<div style="margin-top:14px;">
					<label style="font-size:11px;color:#646970;">Fuseau horaire</label>
					<select name="tz" class="wf-r-in">
						<?php foreach ( array( 'Europe/Paris', 'Europe/Brussels', 'Europe/London', 'America/Montreal', 'America/New_York' ) as $tz ) : ?>
							<option value="<?php echo esc_attr( $tz ); ?>" <?php selected( $s['tz'], $tz ); ?>><?php echo esc_html( $tz ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div style="margin-top:12px;">
					<label style="font-size:11px;color:#646970;">E-mail qui reçoit les réservations (vide = admin du site)</label>
					<input class="wf-r-in" type="email" name="resa_email" value="<?php echo esc_attr( $s['resa_email'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
				</div>
			</div>

			</div><!-- /wf-r-grid -->

			<button type="submit" class="wf-r-btn">Enregistrer</button>
		</form>

		<p style="color:#8a8f98;font-size:12px;margin-top:14px;">
			💡 Astuce : ouvrez cette page sur votre téléphone puis « Ajouter à l'écran d'accueil » — vous l'aurez comme une appli.
			Placez l'élément <strong>Horaires / Statut</strong> (mode « Réglages du restaurant ») dans votre page YOOtheme : il suivra automatiquement ce que vous réglez ici.
		</p>
	</div>
	<?php
}

/* ============================================================
 *  5. MODE « RESTAURATEUR » : interface réduite (non-admin)
 * ========================================================== */

/** L'utilisateur a la capacité resto mais n'est pas admin. */
function wf_resto_is_client() {
	return current_user_can( WF_RESTO_CAP ) && ! current_user_can( 'manage_options' );
}

/* Redirige le restaurateur vers sa page (sauf profil / soumissions). */
add_action( 'admin_init', 'wf_resto_client_redirect' );
function wf_resto_client_redirect() {
	if ( ! wf_resto_is_client() ) { return; }
	if ( wp_doing_ajax() ) { return; }
	$page  = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
	$now   = isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '';
	$allow = array( 'profile.php', 'admin-post.php', 'admin-ajax.php', 'user-edit.php' );
	if ( 0 === strpos( $page, 'wf-restaurant' ) ) { return; }
	if ( in_array( $now, $allow, true ) ) { return; }
	wp_safe_redirect( admin_url( 'admin.php?page=wf-restaurant' ) );
	exit;
}

/* Épure le menu admin pour le restaurateur. */
add_action( 'admin_menu', 'wf_resto_client_menu', 999 );
function wf_resto_client_menu() {
	if ( ! wf_resto_is_client() ) { return; }
	foreach ( array( 'index.php', 'edit.php', 'upload.php', 'edit.php?post_type=page', 'edit-comments.php', 'themes.php', 'plugins.php', 'users.php', 'tools.php', 'options-general.php' ) as $slug ) {
		remove_menu_page( $slug );
	}
}

/*
 * Émoji rendus nativement sur les écrans du plugin.
 *
 * WordPress remplace chaque émoji par une <img> servie par s.w.org. Dès que
 * ce domaine est bloqué (extension de confidentialité, filtrage réseau,
 * intranet), tous les émoji de l'interface deviennent des icônes cassées.
 * Sur nos pages, on retire ce remplacement : le navigateur affiche alors
 * l'émoji de la police système, sans aucune requête.
 */
add_action( 'admin_init', function () {
	$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
	if ( 0 !== strpos( $page, 'wf-restaurant' ) ) { return; }
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	add_filter( 'emoji_svg_url', '__return_false' );
} );

/* Alignement des dashicons utilisées comme puces de menu. */
add_action( 'admin_head', function () {
	echo '<style>#adminmenu .wf-mi{font-size:17px;width:17px;height:17px;vertical-align:-3px;margin-right:2px;opacity:.85}</style>';
} );

/* Masque les éléments d'interface superflus pour le restaurateur. */
add_action( 'admin_head', 'wf_resto_client_css' );
function wf_resto_client_css() {
	if ( ! wf_resto_is_client() ) { return; }
	echo '<style>#wpadminbar .ab-top-menu>li:not(#wp-admin-bar-my-account):not(#wp-admin-bar-site-name),#wp-admin-bar-updates,#wp-admin-bar-comments,#wp-admin-bar-new-content{display:none!important}.update-nag,#screen-meta-links,#wp-admin-bar-wp-logo{display:none!important}#adminmenuback,#adminmenuwrap{}</style>';
}

/* ============================================================
 *  6. SHORTCODE DE SECOURS (thèmes sans l'élément YOOtheme)
 * ========================================================== */

add_shortcode( 'wf_statut_resto', 'wf_resto_shortcode' );
function wf_resto_shortcode( $atts ) {
	$st  = wf_resto_is_open_now();
	$bg  = $st['open'] ? 'rgba(34,197,94,.15)' : 'rgba(239,68,68,.15)';
	$col = $st['open'] ? '#0a7d32' : '#b32d2e';
	return '<span class="wf-statut-resto" style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:100px;font-weight:600;background:' . $bg . ';color:' . $col . ';">'
		. '<span style="width:10px;height:10px;border-radius:50%;background:currentColor;"></span>'
		. esc_html( $st['label'] ) . '</span>';
}

/* ============================================================
 *  7. MENU PILOTABLE (store wf_resto_menu)
 * ========================================================== */

/** Ingrédients d'un plat (meta _wf_ingredients, JSON). */
function wf_resto_ingredients_get( $pid ) {
	$raw = get_post_meta( $pid, '_wf_ingredients', true );
	if ( is_array( $raw ) ) { return array_values( $raw ); }
	$dec = json_decode( (string) $raw, true );
	return is_array( $dec ) ? array_values( $dec ) : array();
}
function wf_resto_ingredients_set( $pid, $list ) {
	$clean = array();
	foreach ( (array) $list as $item ) {
		$name = trim( wp_strip_all_tags( (string) $item ) );
		if ( '' !== $name && ! in_array( $name, $clean, true ) ) { $clean[] = $name; }
	}
	update_post_meta( $pid, '_wf_ingredients', wp_json_encode( $clean, JSON_UNESCAPED_UNICODE ) );
	return $clean;
}

/** Le menu est-il piloté par les produits WooCommerce ? (WC actif + non forcé « interne »). */
function wf_resto_menu_use_wc() {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) { return false; }
	return 'internal' !== get_option( 'wf_resto_menu_source', 'auto' );
}

/** Plats depuis les produits WooCommerce (nom, prix, photo, catégorie, stock=épuisé, ordre, badges meta). */
function wf_resto_menu_items_wc() {
	$q = new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => array( 'publish', 'private', 'draft' ),
		'posts_per_page' => 300,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'no_found_rows'  => true,
		'fields'         => 'ids',
	) );
	$out = array();
	foreach ( $q->posts as $pid ) {
		$p = wc_get_product( $pid );
		if ( ! $p ) { continue; }
		$img_id = $p->get_image_id();
		$photo  = $img_id ? wp_get_attachment_image_url( $img_id, 'large' ) : '';
		$terms  = wp_get_post_terms( $pid, 'product_cat', array( 'fields' => 'names' ) );
		$cat    = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0] : '';
		$price  = $p->get_regular_price();
		if ( '' === (string) $price ) { $price = $p->get_price(); }
		$out[] = array(
			'id'          => (int) $pid,
			'name'        => $p->get_name(),
			'cat'         => $cat,
			'photo'       => $photo ? $photo : '',
			'photo_id'    => (int) $img_id,
			'desc'        => wp_strip_all_tags( $p->get_short_description() ),
			'price'       => ( '' !== (string) $price ) ? (string) $price : '',
			'status'      => get_post_status( $pid ),
			'ingredients' => wf_resto_ingredients_get( $pid ),
			'sold_out'    => ( 'outofstock' === $p->get_stock_status() ) ? 1 : 0,
			'featured' => (int) get_post_meta( $pid, '_wf_featured', true ),
			'popular'  => (int) get_post_meta( $pid, '_wf_popular', true ),
			'veg'      => (int) get_post_meta( $pid, '_wf_veg', true ),
			'vegan'    => (int) get_post_meta( $pid, '_wf_vegan', true ),
			'spicy'    => (int) get_post_meta( $pid, '_wf_spicy', true ),
			'gf'       => (int) get_post_meta( $pid, '_wf_gf', true ),
		);
	}
	return $out;
}

/** Liste normalisée des plats (WooCommerce si actif, sinon store interne wf_resto_menu). */
function wf_resto_menu_items() {
	if ( wf_resto_menu_use_wc() ) { return wf_resto_menu_items_wc(); }
	$raw = get_option( WF_RESTO_MENU_OPT, array() );
	if ( ! is_array( $raw ) ) { return array(); }
	$out = array();
	foreach ( $raw as $row ) {
		if ( ! is_array( $row ) ) { continue; }
		$name = isset( $row['name'] ) ? trim( (string) $row['name'] ) : '';
		if ( '' === $name ) { continue; }
		$out[] = array(
			'name'     => $name,
			'cat'      => isset( $row['cat'] ) ? trim( (string) $row['cat'] ) : '',
			'photo'    => isset( $row['photo'] ) ? trim( (string) $row['photo'] ) : '',
			'desc'     => isset( $row['desc'] ) ? (string) $row['desc'] : '',
			'price'    => isset( $row['price'] ) ? trim( (string) $row['price'] ) : '',
			'featured' => ! empty( $row['featured'] ) ? 1 : 0,
			'sold_out' => ! empty( $row['sold_out'] ) ? 1 : 0,
			'veg'      => ! empty( $row['veg'] ) ? 1 : 0,
			'vegan'    => ! empty( $row['vegan'] ) ? 1 : 0,
			'spicy'    => ! empty( $row['spicy'] ) ? 1 : 0,
			'gf'       => ! empty( $row['gf'] ) ? 1 : 0,
			'popular'  => ! empty( $row['popular'] ) ? 1 : 0,
		);
	}
	return $out;
}

add_action( 'admin_post_wf_resto_menu_save', 'wf_resto_menu_save' );
function wf_resto_menu_save() {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { wp_die( 'Refusé.' ); }
	check_admin_referer( 'wf_resto_menu' );
	if ( wf_resto_menu_use_wc() ) { wf_resto_menu_save_wc(); return; }

	$items = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array();
	$clean = array();
	foreach ( $items as $row ) {
		if ( ! is_array( $row ) ) { continue; }
		$name = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
		if ( '' === trim( $name ) ) { continue; }
		$clean[] = array(
			'name'     => $name,
			'cat'      => isset( $row['cat'] ) ? sanitize_text_field( $row['cat'] ) : '',
			'photo'    => isset( $row['photo'] ) ? esc_url_raw( trim( $row['photo'] ) ) : '',
			'desc'     => isset( $row['desc'] ) ? sanitize_text_field( $row['desc'] ) : '',
			'price'    => isset( $row['price'] ) ? sanitize_text_field( $row['price'] ) : '',
			'featured' => ! empty( $row['featured'] ) ? 1 : 0,
			'sold_out' => isset( $row['available'] ) ? ( ! empty( $row['available'] ) ? 0 : 1 ) : ( ! empty( $row['sold_out'] ) ? 1 : 0 ),
			'veg'      => ! empty( $row['veg'] ) ? 1 : 0,
			'vegan'    => ! empty( $row['vegan'] ) ? 1 : 0,
			'spicy'    => ! empty( $row['spicy'] ) ? 1 : 0,
			'gf'       => ! empty( $row['gf'] ) ? 1 : 0,
			'popular'  => ! empty( $row['popular'] ) ? 1 : 0,
		);
	}
	update_option( WF_RESTO_MENU_OPT, $clean );
	wf_resto_purge();
	set_transient( 'wf_resto_notice_' . get_current_user_id(), 'Menu enregistré (' . count( $clean ) . ' plat(s)).', 30 );
	wp_safe_redirect( admin_url( 'admin.php?page=wf-restaurant-menu' ) );
	exit;
}

/** Enregistre le menu vers les produits WooCommerce (upsert + corbeille des retirés). */
function wf_resto_menu_save_wc() {
	$items     = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array();
	$loaded    = array();
	$submitted = array();
	if ( isset( $_POST['loaded_ids'] ) ) {
		foreach ( explode( ',', (string) wp_unslash( $_POST['loaded_ids'] ) ) as $x ) {
			$x = (int) $x;
			if ( $x > 0 ) { $loaded[ $x ] = 1; }
		}
	}
	$badges = array( 'featured', 'popular', 'veg', 'vegan', 'spicy', 'gf' );
	$n = 0; $pos = 0;
	foreach ( $items as $row ) {
		if ( ! is_array( $row ) ) { continue; }
		$name = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
		if ( '' === trim( $name ) ) { continue; }
		$id = isset( $row['id'] ) ? (int) $row['id'] : 0;
		if ( $id > 0 ) { $submitted[ $id ] = 1; }

		$p = ( $id > 0 ) ? wc_get_product( $id ) : null;
		if ( ! $p ) { $p = new WC_Product_Simple(); }
		$p->set_name( $name );
		$p->set_short_description( isset( $row['desc'] ) ? sanitize_text_field( $row['desc'] ) : '' );
		if ( ! $p->is_type( 'variable' ) ) {
			$price = isset( $row['price'] ) ? preg_replace( '/[^0-9.]/', '', str_replace( ',', '.', (string) $row['price'] ) ) : '';
			$p->set_regular_price( $price );
		}
		$avail = isset( $row['available'] ) ? ! empty( $row['available'] ) : empty( $row['sold_out'] );
		// Menu = dispo/épuisé simple : on ne gère PAS de quantité, sinon WC force "outofstock"
		// quand la quantité tombe à 0 et écrase notre statut (bug « ça revient en épuisé »).
		$p->set_manage_stock( false );
		$p->set_stock_status( $avail ? 'instock' : 'outofstock' );
		$p->set_menu_order( $pos );
		$p->set_status( ! empty( $row['masque'] ) ? 'draft' : 'publish' );

		$photo = isset( $row['photo'] ) ? esc_url_raw( trim( $row['photo'] ) ) : '';
		$aid   = isset( $row['photo_id'] ) ? (int) $row['photo_id'] : 0;
		if ( ! $aid && $photo ) { $aid = (int) attachment_url_to_postid( $photo ); }
		if ( $aid ) { $p->set_image_id( $aid ); }
		elseif ( '' === $photo ) { $p->set_image_id( 0 ); }

		$pid = $p->save();
		if ( ! $pid ) { continue; }
		$n++; $pos++;

		$cat = isset( $row['cat'] ) ? sanitize_text_field( $row['cat'] ) : '';
		if ( '' !== $cat ) {
			$term = get_term_by( 'name', $cat, 'product_cat' );
			if ( $term ) {
				$term_id = (int) $term->term_id;
			} else {
				$t       = wp_insert_term( $cat, 'product_cat' );
				$term_id = ( ! is_wp_error( $t ) && isset( $t['term_id'] ) ) ? (int) $t['term_id'] : 0;
			}
			if ( $term_id ) { wp_set_object_terms( $pid, $term_id, 'product_cat' ); }
		}
		foreach ( $badges as $k ) {
			update_post_meta( $pid, '_wf_' . $k, ! empty( $row[ $k ] ) ? 1 : 0 );
		}
		if ( isset( $row['ingredients'] ) ) { wf_resto_ingredients_set( $pid, (array) $row['ingredients'] ); }
	}

	// Corbeille des produits retirés de l'éditeur (chargés mais non renvoyés).
	$trashed = 0;
	foreach ( $loaded as $lid => $_v ) {
		if ( empty( $submitted[ $lid ] ) ) { wp_trash_post( $lid ); $trashed++; }
	}

	wf_resto_purge();
	if ( function_exists( 'wc_delete_product_transients' ) ) { wc_delete_product_transients(); }
	$msg = 'Menu WooCommerce enregistré (' . $n . ' produit(s)' . ( $trashed ? ', ' . $trashed . ' mis à la corbeille' : '' ) . ').';
	set_transient( 'wf_resto_notice_' . get_current_user_id(), $msg, 30 );
	wp_safe_redirect( admin_url( 'admin.php?page=wf-restaurant-menu' ) );
	exit;
}

/* Médiathèque WP sur la page Menu (sélecteur de photo). */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( isset( $_GET['page'] ) && 'wf-restaurant-menu' === $_GET['page'] ) {
		wp_enqueue_media();
	}
} );

function wf_resto_menu_render() {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { return; }
	if ( function_exists( 'wp_enqueue_media' ) ) { wp_enqueue_media(); }
	$items  = wf_resto_menu_items();
	$notice = get_transient( 'wf_resto_notice_' . get_current_user_id() );
	if ( false !== $notice ) { delete_transient( 'wf_resto_notice_' . get_current_user_id() ); }
	$post   = esc_url( admin_url( 'admin-post.php' ) );
	if ( empty( $items ) ) {
		$items = array( array( 'name' => '', 'cat' => '', 'photo' => '', 'desc' => '', 'price' => '', 'featured' => 0, 'sold_out' => 0, 'veg' => 0, 'vegan' => 0, 'spicy' => 0, 'gf' => 0, 'popular' => 0 ) );
	}
	$useWc  = wf_resto_menu_use_wc();
	$badges = array( 'featured' => 'Plat du jour', 'popular' => 'Populaire', 'veg' => 'Végé', 'vegan' => 'Vegan', 'spicy' => 'Épicé', 'gf' => 'Sans gluten' );

	// Liste des catégories pour les menus déroulants.
	$cats = array();
	if ( $useWc ) {
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		if ( ! is_wp_error( $terms ) ) { foreach ( $terms as $t ) { $cats[] = $t->name; } }
	} else {
		$cats = array_values( array_unique( array_filter( wp_list_pluck( $items, 'cat' ) ) ) );
	}

	// Rendu d'une carte plat (index + valeurs). Utilisée dans la boucle ET le <template>.
	$card = function ( $idx, $it ) use ( $badges, $cats, $useWc ) {
		$photo = isset( $it['photo'] ) ? (string) $it['photo'] : '';
		$sold  = ! empty( $it['sold_out'] );
		$masq  = $useWc && isset( $it['status'] ) && 'draft' === $it['status'];
		$curcat = isset( $it['cat'] ) ? (string) $it['cat'] : '';
		$opts   = $cats;
		if ( '' !== $curcat && ! in_array( $curcat, $opts, true ) ) { $opts[] = $curcat; }
		ob_start(); ?>
		<div class="wf-mm-card<?php echo $sold ? ' is-off' : ''; ?>">
			<div class="wf-mm-card__media<?php echo ( '' === $photo ) ? ' is-empty' : ''; ?>">
				<img src="<?php echo $photo ? esc_url( $photo ) : 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='; ?>" alt="">
				<button type="button" class="wf-mm-card__photo wf-mm-pick">📷 Photo</button>
			</div>
			<div class="wf-mm-card__body">
				<input type="hidden" name="items[<?php echo $idx; ?>][id]" value="<?php echo esc_attr( isset( $it['id'] ) ? $it['id'] : '' ); ?>">
				<input type="hidden" class="wf-mm-photo-id" name="items[<?php echo $idx; ?>][photo_id]" value="<?php echo esc_attr( isset( $it['photo_id'] ) ? $it['photo_id'] : '' ); ?>">
				<input type="hidden" class="wf-mm-photo-input" name="items[<?php echo $idx; ?>][photo]" value="<?php echo esc_attr( $photo ); ?>">

				<div class="wf-mm-card__top">
					<span class="wf-mm-grip" title="Glisser pour réordonner" draggable="true">⠿</span>
					<input class="wf-mm-card__name" type="text" name="items[<?php echo $idx; ?>][name]" value="<?php echo esc_attr( isset( $it['name'] ) ? $it['name'] : '' ); ?>" placeholder="Nom du plat">
				</div>

				<div class="wf-mm-card__row">
					<select class="wf-mm-card__cat" name="items[<?php echo $idx; ?>][cat]">
						<option value="">— Catégorie —</option>
						<?php foreach ( $opts as $c ) : ?>
							<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $curcat, $c ); ?>><?php echo esc_html( $c ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="wf-mm-card__price"><input type="text" name="items[<?php echo $idx; ?>][price]" value="<?php echo esc_attr( isset( $it['price'] ) ? $it['price'] : '' ); ?>" placeholder="0.00"><span class="wf-mm-card__eur">€</span></span>
				</div>

				<label class="wf-mm-toggle">
					<input type="hidden" name="items[<?php echo $idx; ?>][available]" value="0">
					<input type="checkbox" class="wf-mm-avail" name="items[<?php echo $idx; ?>][available]" value="1" <?php echo checked( ! $sold, true, false ); ?>>
					<span class="wf-mm-toggle__track"></span>
					<span class="wf-mm-toggle__lbl"><?php echo $sold ? 'Épuisé' : 'Disponible'; ?></span>
				</label>

				<div>
					<div class="wf-mm-card__ing-lbl">Ingrédients</div>
					<div class="wf-mm-ings">
						<?php foreach ( (array) ( isset( $it['ingredients'] ) ? $it['ingredients'] : array() ) as $ing ) : $ing = trim( (string) $ing ); if ( '' === $ing ) { continue; } ?>
							<span class="wf-mm-ing"><?php echo esc_html( $ing ); ?><input type="hidden" name="items[<?php echo $idx; ?>][ingredients][]" value="<?php echo esc_attr( $ing ); ?>"><button type="button" class="wf-mm-ing__x" title="Retirer">×</button></span>
						<?php endforeach; ?>
						<input type="text" class="wf-mm-ing__add" placeholder="+ Ajouter">
					</div>
				</div>

				<div class="wf-mm-badges">
					<?php foreach ( $badges as $k => $lbl ) : ?>
						<label class="wf-mm-bchip"><input type="checkbox" name="items[<?php echo $idx; ?>][<?php echo $k; ?>]" value="1" <?php echo checked( ! empty( $it[ $k ] ), true, false ); ?>> <?php echo esc_html( $lbl ); ?></label>
					<?php endforeach; ?>
				</div>

				<?php $hasdesc = '' !== trim( (string) ( isset( $it['desc'] ) ? $it['desc'] : '' ) ); ?>
				<button type="button" class="wf-mm-card__desc-toggle">Description ▾</button>
				<textarea class="wf-mm-card__desc" rows="2" name="items[<?php echo $idx; ?>][desc]" placeholder="Description (optionnel)" style="<?php echo $hasdesc ? '' : 'display:none;'; ?>"><?php echo esc_textarea( isset( $it['desc'] ) ? $it['desc'] : '' ); ?></textarea>

				<div class="wf-mm-card__foot">
					<button type="button" class="wf-mm-card__dup">Dupliquer</button>
					<button type="button" class="wf-mm-card__del">Supprimer</button>
					<?php if ( $useWc ) : ?>
					<label class="wf-mm-card__draft"><input type="checkbox" name="items[<?php echo $idx; ?>][masque]" value="1" <?php echo checked( $masq, true, false ); ?>> Masqué (brouillon)</label>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php return ob_get_clean();
	};
	$empty = array( 'name' => '', 'cat' => '', 'photo' => '', 'desc' => '', 'price' => '', 'ingredients' => array() );
	?>
	<style>
		.wf-menu-manager{--l:#e6e8ec;--muted:#6b7177;--blue:#2271b1;max-width:1560px}
		.wf-menu-manager,.wf-menu-manager *{box-sizing:border-box}
		.wf-mm-topbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:14px 0;position:sticky;top:32px;z-index:6;background:#f0f0f1;padding:10px 0}
		.wf-mm-save{padding:11px 20px;border:0;border-radius:10px;background:var(--blue);color:#fff;font-size:15px;font-weight:700;cursor:pointer}
		.wf-mm-add{display:flex;gap:10px;flex-wrap:wrap;align-items:center;background:#f6f7f9;border:1px dashed #cfd3da;border-radius:14px;padding:14px;margin-bottom:12px}
		.wf-mm-add__name{flex:1 1 220px}.wf-mm-add__price{width:100px}
		.wf-mm-add input,.wf-mm-add select{border:1px solid var(--l);border-radius:10px;padding:9px 12px;font-size:14px;background:#fff}
		.wf-mm-add__btn{border:0;border-radius:10px;padding:10px 16px;font-size:14px;font-weight:600;cursor:pointer;background:var(--blue);color:#fff}
		.wf-mm-count{color:var(--muted);font-size:13px;margin:2px 2px 10px}
		.wf-mm-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;margin-top:10px}
		.wf-mm-card{display:flex;gap:16px;border:1px solid var(--l);border-radius:16px;padding:18px;background:#fff;align-items:flex-start;box-shadow:0 1px 2px rgba(16,24,40,.04)}
		.wf-mm-card.is-off{opacity:.62;border-color:#f0c9c0;background:#fff9f7}
		.wf-mm-card__media{position:relative;width:96px;flex:0 0 96px}
		.wf-mm-card__media img{width:96px;height:96px;object-fit:cover;border-radius:12px;display:block;background:#eef0f2}
		.wf-mm-card__media.is-empty img{visibility:hidden}
		.wf-mm-card__media.is-empty::before{content:"📷";position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:26px;background:#eef0f2;border-radius:12px}
		.wf-mm-card__photo{position:absolute;left:0;right:0;bottom:0;background:rgba(0,0,0,.62);color:#fff;padding:5px;font-size:12px;border:0;border-radius:0 0 12px 12px;cursor:pointer}
		.wf-mm-card__body{flex:1 1 auto;min-width:0;display:flex;flex-direction:column;gap:12px}
		.wf-mm-card__top{display:flex;align-items:center;gap:8px}
		.wf-mm-grip{cursor:grab;color:#9aa0a6;font-size:18px;line-height:1;user-select:none;flex:0 0 auto}
		.wf-mm-card__name{flex:1 1 auto;min-width:0;font-weight:700;font-size:15px;border:1px solid var(--l);border-radius:10px;padding:9px 12px;font-family:inherit;background:#fff}
		.wf-mm-card__row{display:flex;gap:8px;align-items:center}
		.wf-mm-card__cat{flex:1 1 auto;min-width:0;border:1px solid var(--l);border-radius:10px;padding:9px 12px;font-size:14px;background:#fff;cursor:pointer}
		.wf-mm-card__price{display:inline-flex;align-items:center;gap:4px}
		.wf-mm-card__price input{width:72px;text-align:right;border:1px solid var(--l);border-radius:10px;padding:9px 10px;font-size:14px;font-family:inherit}
		.wf-mm-card__eur{color:var(--muted);font-weight:600}
		.wf-mm-toggle{display:inline-flex;align-items:center;gap:10px;cursor:pointer;user-select:none;align-self:flex-start}
		.wf-mm-toggle input{position:absolute;opacity:0;width:0;height:0}
		.wf-mm-toggle__track{width:44px;height:26px;border-radius:999px;background:#cfd3da;position:relative;transition:background .15s;flex:0 0 auto}
		.wf-mm-toggle__track::after{content:"";position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;background:#fff;transition:transform .15s}
		.wf-mm-toggle input:checked + .wf-mm-toggle__track{background:#1a8a3c}
		.wf-mm-toggle input:checked + .wf-mm-toggle__track::after{transform:translateX(18px)}
		.wf-mm-toggle__lbl{font-size:13px;font-weight:600;color:var(--muted)}
		.wf-mm-card.is-off .wf-mm-toggle__lbl{color:#c0392b}
		.wf-mm-card__ing-lbl{font-size:12px;font-weight:600;color:var(--muted);margin-bottom:5px}
		.wf-mm-ings{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
		.wf-mm-ing{display:inline-flex;align-items:center;gap:4px;background:#eef1fb;color:#23306b;border-radius:999px;padding:4px 6px 4px 10px;font-size:12.5px;font-weight:600}
		.wf-mm-ing__x{border:0;background:rgba(35,48,107,.12);color:#23306b;border-radius:50%;width:16px;height:16px;line-height:1;cursor:pointer;font-size:13px}
		.wf-mm-ing__x:hover{background:rgba(192,57,43,.85);color:#fff}
		.wf-mm-ing__add{border:1px dashed #c2c6cf;border-radius:999px;padding:4px 10px;font-size:12.5px;width:110px;background:#fff}
		.wf-mm-badges{display:flex;flex-wrap:wrap;gap:6px}
		.wf-mm-bchip{font-size:12px;color:#3c434a;display:inline-flex;align-items:center;gap:4px;background:#f6f7f7;border:1px solid #e0e0e0;border-radius:100px;padding:4px 9px;cursor:pointer}
		.wf-mm-bchip:has(input:checked){background:#e7f0fb;border-color:var(--blue);color:#0a4b78}
		.wf-mm-bchip input{margin:0}
		.wf-mm-card__desc-toggle{background:#f2f3f5;color:#1f2430;align-self:flex-start;border:0;border-radius:10px;padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer}
		.wf-mm-card__desc{width:100%;resize:vertical;border:1px solid var(--l);border-radius:10px;padding:9px 12px;font-size:14px;font-family:inherit}
		.wf-mm-card__foot{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:4px}
		.wf-mm-card__dup,.wf-mm-card__del{flex:1 1 0;min-width:0;white-space:nowrap;text-align:center;border:0;border-radius:10px;padding:9px 14px;font-size:14px;font-weight:600;cursor:pointer}
		.wf-mm-card__dup{background:#eef1fb;color:#23306b}.wf-mm-card__dup:hover{background:#e2e7fb}
		.wf-mm-card__del{background:#fbecea;color:#c0392b}.wf-mm-card__del:hover{background:#c0392b;color:#fff}
		.wf-mm-card__draft{flex:1 1 100%;font-size:12.5px;color:var(--muted);display:inline-flex;align-items:center;gap:6px;cursor:pointer}
		.wf-mm-card.drag{opacity:.4;outline:2px dashed var(--blue)}
		@media(max-width:1080px){.wf-mm-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
		@media(max-width:782px){.wf-mm-grid{grid-template-columns:1fr}.wf-mm-card{flex-direction:column}.wf-mm-card__media,.wf-mm-card__media img{width:100%}.wf-mm-card__media img{height:160px}.wf-mm-topbar{position:static}}
	</style>
	<div class="wrap">
		<h1 style="font-size:22px;">🍽️ Menu — pilotable</h1>
		<p class="description" style="max-width:820px;">Une carte par plat : photo (médiathèque), nom, catégorie, prix, <strong>Disponible</strong> (interrupteur), ingrédients, description. Glissez la poignée ⠿ pour réordonner.</p>
		<?php if ( $useWc ) : ?><p style="background:#eef6ec;border:1px solid #cfe6c8;border-radius:8px;padding:8px 12px;color:#2e6b2b;font-size:13px;max-width:820px;">🛒 <strong>Synchronisé avec WooCommerce</strong> — ces cartes <strong>sont vos produits</strong> (nom, prix, photo, catégorie, stock, ordre). « Supprimer » met le produit à la <strong>corbeille</strong> (réversible).</p><?php endif; ?>
		<?php if ( false !== $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>

		<div class="wf-menu-manager">
			<form method="post" action="<?php echo $post; ?>" id="wf-menu-form">
				<?php wp_nonce_field( 'wf_resto_menu' ); ?>
				<input type="hidden" name="action" value="wf_resto_menu_save">
				<?php if ( $useWc ) : ?>
				<input type="hidden" name="loaded_ids" value="<?php echo esc_attr( implode( ',', array_filter( array_map( function ( $x ) { return (int) ( isset( $x['id'] ) ? $x['id'] : 0 ); }, $items ) ) ) ); ?>">
				<?php endif; ?>

				<div class="wf-mm-topbar">
					<button type="submit" class="wf-mm-save">Enregistrer le menu</button>
					<span class="description">Ajout, modif, ordre : tout est enregistré en un clic.</span>
				</div>

				<div class="wf-mm-add">
					<input class="wf-mm-add__name" type="text" id="wf-mm-add-name" placeholder="Nom du nouveau plat">
					<input class="wf-mm-add__price" type="text" id="wf-mm-add-price" placeholder="Prix €">
					<select class="wf-mm-add__cat" id="wf-mm-add-cat">
						<option value="">— Catégorie —</option>
						<?php foreach ( $cats as $c ) : ?><option value="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $c ); ?></option><?php endforeach; ?>
					</select>
					<button type="button" class="wf-mm-add__btn" id="wf-menu-add">+ Ajouter le plat</button>
				</div>

				<div class="wf-mm-count"><?php echo (int) count( array_filter( $items, function ( $x ) { return '' !== trim( (string) ( isset( $x['name'] ) ? $x['name'] : '' ) ); } ) ); ?> plat(s)</div>

				<div class="wf-mm-grid" id="wf-menu-rows">
					<?php $i = 0; foreach ( $items as $it ) { echo $card( $i, $it ); $i++; } ?>
				</div>
				<p style="margin-top:16px;"><button type="submit" class="wf-mm-save">Enregistrer le menu</button></p>
			</form>

			<template id="wf-menu-tpl"><?php echo $card( '__i__', $empty ); ?></template>
		</div>
		<script>
		(function(){
			var rows = document.getElementById('wf-menu-rows');
			var form = document.getElementById('wf-menu-form');
			var nextIdx = <?php echo (int) $i; ?>;

			function makeCard(){
				var html = document.getElementById('wf-menu-tpl').innerHTML.split('__i__').join(String(nextIdx));
				nextIdx++;
				var tmp = document.createElement('div');
				tmp.innerHTML = html.trim();
				return tmp.firstElementChild;
			}
			document.getElementById('wf-menu-add').addEventListener('click', function(){
				var card = makeCard();
				var nm = document.getElementById('wf-mm-add-name');
				var pr = document.getElementById('wf-mm-add-price');
				var ct = document.getElementById('wf-mm-add-cat');
				if (nm && nm.value) { card.querySelector('.wf-mm-card__name').value = nm.value; }
				if (pr && pr.value) { card.querySelector('.wf-mm-card__price input').value = pr.value; }
				if (ct && ct.value) { var sel = card.querySelector('.wf-mm-card__cat'); if (sel) { sel.value = ct.value; } }
				rows.appendChild(card);
				if (nm) { nm.value=''; } if (pr) { pr.value=''; } if (ct) { ct.value=''; }
				card.scrollIntoView({behavior:'smooth', block:'nearest'});
			});

			// Médiathèque (délégué, test wp.media au clic).
			document.addEventListener('click', function(e){
				var pick = e.target.closest ? e.target.closest('.wf-mm-pick') : null;
				if (!pick) return;
				e.preventDefault();
				if (!window.wp || !wp.media) { alert("Médiathèque indisponible. Rechargez la page."); return; }
				var card = pick.closest('.wf-mm-card');
				var frame = wp.media({ title:'Choisir une photo', library:{ type:'image' }, multiple:false, button:{ text:'Utiliser cette photo' } });
				frame.on('select', function(){
					var a = frame.state().get('selection').first().toJSON();
					var url = a.url;
					card.querySelector('.wf-mm-photo-input').value = url;
					var pidEl = card.querySelector('.wf-mm-photo-id'); if (pidEl) { pidEl.value = a.id || ''; }
					var media = card.querySelector('.wf-mm-card__media');
					media.classList.remove('is-empty');
					var img = media.querySelector('img'); if (img) { img.src = url; }
				});
				frame.open();
			});

			// Disponible toggle → classe is-off + libellé.
			document.addEventListener('change', function(e){
				var t = e.target.closest ? e.target.closest('.wf-mm-avail') : null;
				if (!t) return;
				var card = t.closest('.wf-mm-card');
				var lbl = card.querySelector('.wf-mm-toggle__lbl');
				if (t.checked) { card.classList.remove('is-off'); if (lbl) { lbl.textContent = 'Disponible'; } }
				else { card.classList.add('is-off'); if (lbl) { lbl.textContent = 'Épuisé'; } }
			});

			// Ingrédients : Entrée dans le champ = crée une puce.
			document.addEventListener('keydown', function(e){
				var inp = e.target.closest ? e.target.closest('.wf-mm-ing__add') : null;
				if (!inp) return;
				if (e.key !== 'Enter') return;
				e.preventDefault();
				var val = inp.value.trim(); if (!val) return;
				var card = inp.closest('.wf-mm-card');
				var idxName = card.querySelector('[name^="items["]').name.match(/items\[([^\]]*)\]/);
				var idx = idxName ? idxName[1] : '0';
				var chip = document.createElement('span');
				chip.className = 'wf-mm-ing';
				chip.appendChild(document.createTextNode(val));
				var h = document.createElement('input');
				h.type = 'hidden'; h.name = 'items[' + idx + '][ingredients][]'; h.value = val;
				chip.appendChild(h);
				var x = document.createElement('button');
				x.type = 'button'; x.className = 'wf-mm-ing__x'; x.textContent = '×';
				chip.appendChild(x);
				inp.parentNode.insertBefore(chip, inp);
				inp.value = '';
			});
			document.addEventListener('click', function(e){
				var x = e.target.closest ? e.target.closest('.wf-mm-ing__x') : null;
				if (x) { e.preventDefault(); var c = x.closest('.wf-mm-ing'); if (c) { c.remove(); } return; }
				var dt = e.target.closest ? e.target.closest('.wf-mm-card__desc-toggle') : null;
				if (dt) { e.preventDefault(); var ta = dt.closest('.wf-mm-card__body').querySelector('.wf-mm-card__desc'); if (ta) { ta.style.display = (ta.style.display === 'none') ? '' : 'none'; } return; }
				var del = e.target.closest ? e.target.closest('.wf-mm-card__del') : null;
				if (del) { e.preventDefault(); var cd = del.closest('.wf-mm-card'); if (cd) { cd.remove(); } return; }
				var dup = e.target.closest ? e.target.closest('.wf-mm-card__dup') : null;
				if (dup) {
					e.preventDefault();
					var src = dup.closest('.wf-mm-card');
					var clone = src.cloneNode(true);
					var idIn = clone.querySelector('input[name$="[id]"]'); if (idIn) { idIn.value = ''; }
					src.parentNode.insertBefore(clone, src.nextSibling);
					return;
				}
			});

			// Réordonner : drag depuis la poignée ⠿.
			var dragEl = null;
			rows.addEventListener('dragstart', function(e){
				var g = e.target.closest('.wf-mm-grip');
				if (!g) { return; }
				dragEl = g.closest('.wf-mm-card');
				if (dragEl) { dragEl.classList.add('drag'); if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; } }
			});
			rows.addEventListener('dragend', function(){ if (dragEl) { dragEl.classList.remove('drag'); dragEl = null; } });
			rows.addEventListener('dragover', function(e){
				if (!dragEl) return;
				e.preventDefault();
				var ref = dragAfter(e.clientX, e.clientY);
				if (ref == null) { rows.appendChild(dragEl); }
				else if (ref !== dragEl) { rows.insertBefore(dragEl, ref); }
			});
			function dragAfter(x, y){
				var els = Array.prototype.slice.call(rows.querySelectorAll('.wf-mm-card:not(.drag)'));
				var best = null, bestDist = Infinity, insertAfter = false;
				els.forEach(function(el){
					var b = el.getBoundingClientRect();
					var cx = b.left + b.width/2, cy = b.top + b.height/2;
					var d = Math.hypot(cx - x, cy - y);
					if (d < bestDist) { bestDist = d; best = el; insertAfter = (x > cx) || (y > cy + b.height*0.25); }
				});
				if (!best) return null;
				return insertAfter ? best.nextElementSibling : best;
			}

			// Renumérote les index selon l'ordre affiché avant l'envoi.
			form.addEventListener('submit', function(){
				var cards = rows.querySelectorAll('.wf-mm-card');
				for (var idx = 0; idx < cards.length; idx++) {
					var inputs = cards[idx].querySelectorAll('[name^="items["]');
					for (var j = 0; j < inputs.length; j++) {
						inputs[j].name = inputs[j].name.replace(/items\[[^\]]*\]/, 'items[' + idx + ']');
					}
				}
			});
		})();
		</script>
	</div>
	<?php
}

/* ============================================================
 *  8. RÉSERVATIONS DE TABLE (CPT wf_resa, reçues sur le site)
 * ========================================================== */

add_action( 'init', 'wf_resa_register_cpt' );
function wf_resa_register_cpt() {
	register_post_type( 'wf_resa', array(
		'label'           => 'Réservations',
		'public'          => false,
		'show_ui'         => false,
		'show_in_menu'    => false,
		'show_in_rest'    => false,
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'map_meta_cap'    => true,
	) );
}

function wf_resa_count_new() {
	$q = new WP_Query( array(
		// -1 : le badge plafonnait à 50 au-delà de 50 demandes en attente.
		'post_type' => 'wf_resa', 'post_status' => 'publish', 'posts_per_page' => -1,
		'fields' => 'ids', 'no_found_rows' => true,
		'meta_query' => array( array( 'key' => '_wf_resa_statut', 'value' => 'new' ) ),
	) );
	return count( $q->posts );
}

/* ── Créneaux & capacité ─────────────────────────────────────────── */

/** Clé jour (mon..sun) d'une date Y-m-d dans le fuseau du resto. */
function wf_resa_daykey( $date ) {
	$d = DateTime::createFromFormat( '!Y-m-d', $date, wf_resto_tz() );
	if ( ! $d ) { return ''; }
	return strtolower( $d->format( 'D' ) );
}

/** Créneaux d'une date depuis les horaires : [ ['time'=>'HH:MM','service'=>'midi'|'soir'], … ]. */
function wf_resa_gen_slots( $date ) {
	$s   = wf_resto_get();
	$rz  = $s['resa'];
	$key = wf_resa_daykey( $date );
	if ( '' === $key ) { return array(); }
	$row = isset( $s['days'][ $key ] ) ? $s['days'][ $key ] : array();
	if ( empty( $row['enabled'] ) ) { return array(); }
	$iv  = max( 5, (int) $rz['interval'] );
	$lb  = max( 0, (int) $rz['last_before'] );
	$out = array();
	// « min » est la minute absolue depuis minuit du jour choisi : elle peut
	// dépasser 1440 pour un service qui finit après minuit. C'est elle qui
	// sert à comparer avec l'heure courante, jamais la chaîne « 00:30 ».
	foreach ( wf_resto_day_windows( $row ) as $w ) {
		$last = $w['to'] - $lb;
		for ( $t = $w['from']; $t <= $last; $t += $iv ) {
			$out[] = array(
				'time'    => sprintf( '%02d:%02d', intdiv( $t, 60 ) % 24, $t % 60 ),
				'service' => $w['service'],
				'min'     => $t,
			);
		}
	}
	return $out;
}

/** Service (midi|soir|'') d'une heure donnée sur une date. */
function wf_resa_service_for( $date, $heure ) {
	foreach ( wf_resa_gen_slots( $date ) as $sl ) {
		if ( $sl['time'] === $heure ) { return $sl['service']; }
	}
	return '';
}

/** Couverts réservés (new+confirmed) pour une date : ['midi'=>n,'soir'=>n,'slots'=>[time=>n]]. */
function wf_resa_booked( $date ) {
	$q = new WP_Query( array(
		'post_type' => 'wf_resa', 'post_status' => 'publish', 'posts_per_page' => 500,
		'no_found_rows' => true, 'fields' => 'ids',
		'meta_query' => array( array( 'key' => '_wf_resa_date', 'value' => $date ) ),
	) );
	$out = array( 'midi' => 0, 'soir' => 0, 'slots' => array() );
	foreach ( $q->posts as $id ) {
		if ( 'declined' === get_post_meta( $id, '_wf_resa_statut', true ) ) { continue; }
		$cv   = (int) get_post_meta( $id, '_wf_resa_couverts', true );
		$svc  = get_post_meta( $id, '_wf_resa_service', true );
		$time = get_post_meta( $id, '_wf_resa_heure', true );
		if ( 'midi' === $svc || 'soir' === $svc ) { $out[ $svc ] += $cv; }
		if ( $time ) { $out['slots'][ $time ] = ( isset( $out['slots'][ $time ] ) ? $out['slots'][ $time ] : 0 ) + $cv; }
	}
	return $out;
}

/** Créneaux réellement disponibles pour un nombre de couverts. */
function wf_resa_availability( $date, $party ) {
	$s       = wf_resto_get();
	$rz      = $s['resa'];
	$slots   = wf_resa_gen_slots( $date );
	$booked  = wf_resa_booked( $date );
	$maxsvc  = array( 'midi' => (int) $rz['max_lunch'], 'soir' => (int) $rz['max_dinner'] );
	$maxslot = (int) $rz['max_per_slot'];
	$party   = max( 1, (int) $party );
	$out     = array();
	foreach ( $slots as $sl ) {
		$svc      = $sl['service'];
		$rem_svc  = $maxsvc[ $svc ] > 0 ? $maxsvc[ $svc ] - (int) $booked[ $svc ] : PHP_INT_MAX;
		$rem_slot = $maxslot > 0 ? $maxslot - (int) ( isset( $booked['slots'][ $sl['time'] ] ) ? $booked['slots'][ $sl['time'] ] : 0 ) : PHP_INT_MAX;
		$rem      = min( $rem_svc, $rem_slot );
		if ( $rem >= $party ) {
			$out[] = array(
				'time'      => $sl['time'],
				'service'   => $svc,
				'min'       => isset( $sl['min'] ) ? (int) $sl['min'] : (int) wf_resto_hm( $sl['time'] ),
				'remaining' => ( PHP_INT_MAX === $rem ? 0 : $rem ),
			);
		}
	}
	return $out;
}

/* Créneaux disponibles pour le front (AJAX). */
add_action( 'wp_ajax_wf_resa_slots', 'wf_resa_slots_ajax' );
add_action( 'wp_ajax_nopriv_wf_resa_slots', 'wf_resa_slots_ajax' );
function wf_resa_slots_ajax() {
	if ( ! isset( $_POST['_wfnonce'] ) || ! wp_verify_nonce( $_POST['_wfnonce'], 'wf_resa' ) ) { wp_send_json_error( 'Session expirée.' ); }
	$date  = preg_replace( '/[^0-9\-]/', '', wp_unslash( $_POST['date'] ?? '' ) );
	$party = (int) ( $_POST['couverts'] ?? 2 );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) { wp_send_json_error( 'Date invalide.' ); }
	$s = wf_resto_get();
	if ( empty( $s['resa']['enabled'] ) ) { wp_send_json_success( array( 'slots' => array( 'midi' => array(), 'soir' => array() ), 'closed' => false, 'off' => true ) ); }

	$today = wf_resto_today();
	$max   = date( 'Y-m-d', strtotime( $today . ' +' . (int) $s['resa']['days_ahead'] . ' days' ) );
	if ( $date < $today || $date > $max ) { wp_send_json_success( array( 'slots' => array( 'midi' => array(), 'soir' => array() ), 'closed' => true, 'reason' => 'Date non réservable.' ) ); }

	$exc = $s['exception'];
	if ( ! empty( $exc['active'] ) ) {
		$ge = ( '' === $exc['from'] || $date >= $exc['from'] );
		$le = ( '' === $exc['to'] || $date <= $exc['to'] );
		if ( $ge && $le ) { wp_send_json_success( array( 'slots' => array( 'midi' => array(), 'soir' => array() ), 'closed' => true, 'reason' => $exc['message'] ? $exc['message'] : 'Fermeture exceptionnelle' ) ); }
	}
	if ( in_array( $date, (array) $s['resa']['blocked'], true ) ) {
		wp_send_json_success( array( 'slots' => array( 'midi' => array(), 'soir' => array() ), 'closed' => true, 'reason' => 'Fermé ce jour-là.' ) );
	}

	$av = wf_resa_availability( $date, $party );
	// Délai minimum avant réservation : masque les créneaux trop proches (et déjà passés) aujourd'hui.
	if ( $date === $today ) {
		$buf     = (int) ( $s['resa']['buffer_min'] ?? 0 );
		$now_min = wf_resto_now_min() + $buf;
		$av = array_values( array_filter( $av, function ( $a ) use ( $now_min ) {
			return (int) $a['min'] >= $now_min;
		} ) );
	}
	$g  = array( 'midi' => array(), 'soir' => array() );
	foreach ( $av as $a ) { $g[ $a['service'] ][] = $a; }
	wp_send_json_success( array( 'slots' => $g, 'closed' => empty( $av ) ) );
}

/* E-mail au client (accusé de réception / confirmation / refus). */
function wf_resa_notify_client( $id, $type ) {
	$email = get_post_meta( $id, '_wf_resa_email', true );
	if ( ! $email || ! is_email( $email ) ) { return; }
	$prenom   = get_post_meta( $id, '_wf_resa_prenom', true );
	$date     = get_post_meta( $id, '_wf_resa_date', true );
	$heure    = get_post_meta( $id, '_wf_resa_heure', true );
	$couverts = (int) get_post_meta( $id, '_wf_resa_couverts', true );
	$site     = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$when     = date_i18n( 'l j F', strtotime( $date ) ) . ' à ' . $heure;
	if ( 'received' === $type ) {
		$subj = '[' . $site . '] Demande de réservation reçue';
		$body = "Bonjour $prenom,\n\nNous avons bien reçu votre demande de réservation pour $couverts personne(s), le $when.\nNous vous recontactons pour la confirmer.\n\n$site";
	} elseif ( 'confirmed' === $type ) {
		$subj = '[' . $site . '] Réservation confirmée';
		$body = "Bonjour $prenom,\n\nVotre réservation pour $couverts personne(s) le $when est CONFIRMÉE.\nÀ très bientôt !\n\n$site";
	} elseif ( 'declined' === $type ) {
		$subj = '[' . $site . '] Réservation non disponible';
		$body = "Bonjour $prenom,\n\nNous sommes désolés, nous ne pouvons pas honorer votre réservation pour le $when.\nN'hésitez pas à nous appeler pour trouver un autre créneau.\n\n$site";
	} else {
		return;
	}
	wp_mail( $email, $subj, $body );
}

/* Réception du formulaire (front, AJAX). */
add_action( 'wp_ajax_wf_resa_submit', 'wf_resa_submit' );
add_action( 'wp_ajax_nopriv_wf_resa_submit', 'wf_resa_submit' );
function wf_resa_submit() {
	if ( ! isset( $_POST['_wfnonce'] ) || ! wp_verify_nonce( $_POST['_wfnonce'], 'wf_resa' ) ) {
		wp_send_json_error( 'Session expirée, rechargez la page.' );
	}
	if ( ! empty( $_POST['company'] ) ) { wp_send_json_error( 'Erreur.' ); } // honeypot

	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
	$key = 'wf_resa_' . md5( $ip );
	if ( get_transient( $key ) ) { wp_send_json_error( 'Merci de patienter avant un nouvel envoi.' ); }

	$prenom   = sanitize_text_field( wp_unslash( $_POST['prenom'] ?? '' ) );
	$nom      = sanitize_text_field( wp_unslash( $_POST['nom'] ?? '' ) );
	$tel      = sanitize_text_field( wp_unslash( $_POST['tel'] ?? '' ) );
	$couverts = (int) ( $_POST['couverts'] ?? 0 );
	$date     = preg_replace( '/[^0-9\-]/', '', wp_unslash( $_POST['date'] ?? '' ) );
	$heure    = sanitize_text_field( wp_unslash( $_POST['heure'] ?? '' ) );
	$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$note     = sanitize_text_field( wp_unslash( $_POST['note'] ?? '' ) );

	if ( '' === $nom || '' === $prenom || '' === $tel ) { wp_send_json_error( 'Merci d’indiquer nom, prénom et téléphone.' ); }
	$rz_sub  = wf_resto_get()['resa'];
	$min_p   = max( 1, (int) ( $rz_sub['min_party'] ?? 1 ) );
	$max_p   = (int) ( $rz_sub['max_party'] ?? 0 );
	$cap_p   = ( $max_p > 0 ) ? $max_p : 50;
	if ( $couverts < $min_p || $couverts > $cap_p ) {
		wp_send_json_error( 'Nombre de personnes : entre ' . $min_p . ' et ' . $cap_p . '. Au-delà, appelez-nous.' );
	}
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) { wp_send_json_error( 'Date invalide.' ); }
	if ( ! preg_match( '/^([01]?\d|2[0-3]):[0-5]\d$/', $heure ) ) { wp_send_json_error( 'Heure invalide.' ); }

	// Créneaux + capacité (autorité serveur)
	$store   = wf_resto_get();
	$service = '';
	if ( ! empty( $store['resa']['enabled'] ) ) {
		$today = wf_resto_today();
		$maxd  = date( 'Y-m-d', strtotime( $today . ' +' . (int) $store['resa']['days_ahead'] . ' days' ) );
		if ( $date < $today || $date > $maxd ) { wp_send_json_error( 'Cette date n’est pas réservable en ligne.' ); }
		$exc = $store['exception'];
		if ( ! empty( $exc['active'] ) ) {
			$ge = ( '' === $exc['from'] || $date >= $exc['from'] );
			$le = ( '' === $exc['to'] || $date <= $exc['to'] );
			if ( $ge && $le ) { wp_send_json_error( 'Le restaurant est fermé à cette date.' ); }
		}
		if ( in_array( $date, (array) $store['resa']['blocked'], true ) ) { wp_send_json_error( 'Le restaurant est fermé à cette date.' ); }
		$service = wf_resa_service_for( $date, $heure );
		if ( '' === $service ) { wp_send_json_error( 'Ce créneau n’est plus proposé, choisissez-en un autre.' ); }
		$avail = wf_resa_availability( $date, $couverts );
		$ok     = false;
		$sel_min = null;
		foreach ( $avail as $a ) {
			if ( $a['time'] === $heure ) { $ok = true; $sel_min = (int) $a['min']; break; }
		}
		if ( ! $ok ) { wp_send_json_error( 'Désolé, ce service est complet pour ' . $couverts . ' personne(s).' ); }
		// Le délai minimum n'était filtré que par le JS des créneaux : un POST
		// rejoué pouvait réserver une heure déjà passée. Le serveur tranche, sur
		// la minute absolue pour ne pas rejeter un « 00:30 » qui est en fait la
		// fin du service du soir.
		if ( $date === $today ) {
			$limite = wf_resto_now_min() + max( 0, (int) ( $store['resa']['buffer_min'] ?? 0 ) );
			if ( $sel_min < $limite ) { wp_send_json_error( 'Ce créneau vient de passer, choisissez-en un autre.' ); }
		}
	}

	$title = $nom . ' ' . $prenom . ' — ' . $date . ' ' . $heure . ' (' . $couverts . 'p)';
	$id    = wp_insert_post( array(
		'post_type'   => 'wf_resa',
		'post_status' => 'publish',
		'post_title'  => $title,
	), true );
	if ( is_wp_error( $id ) || ! $id ) { wp_send_json_error( 'Erreur d’enregistrement, réessayez.' ); }

	update_post_meta( $id, '_wf_resa_prenom', $prenom );
	update_post_meta( $id, '_wf_resa_nom', $nom );
	update_post_meta( $id, '_wf_resa_tel', $tel );
	update_post_meta( $id, '_wf_resa_couverts', $couverts );
	update_post_meta( $id, '_wf_resa_date', $date );
	update_post_meta( $id, '_wf_resa_heure', $heure );
	if ( '' === $service ) { $service = wf_resa_service_for( $date, $heure ); }
	update_post_meta( $id, '_wf_resa_service', $service );
	update_post_meta( $id, '_wf_resa_email', $email );
	update_post_meta( $id, '_wf_resa_note', $note );
	update_post_meta( $id, '_wf_resa_statut', 'new' );

	set_transient( $key, 1, 30 );

	// Notification au restaurant
	$store = wf_resto_get();
	$to    = ! empty( $store['resa_email'] ) ? $store['resa_email'] : get_option( 'admin_email' );
	$site  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subj  = '[' . $site . '] Nouvelle réservation — ' . $date . ' ' . $heure;
	$body  = "Nouvelle réservation :\n\n"
		. "Nom : $nom\nPrénom : $prenom\nTéléphone : $tel\nPersonnes : $couverts\n"
		. "Date : $date\nHeure : $heure\n"
		. ( $email ? "Email : $email\n" : '' )
		. ( $note ? "Note : $note\n" : '' )
		. "\nGérer : " . admin_url( 'admin.php?page=wf-restaurant-resa' );
	wp_mail( $to, $subj, $body );
	wf_resa_notify_client( $id, 'received' );

	wp_send_json_success( 'Merci ! Votre demande de réservation a bien été envoyée. Nous vous recontactons pour confirmer.' );
}

/* Changement de statut / suppression (back-office). */
add_action( 'admin_post_wf_resa_status', 'wf_resa_status' );
function wf_resa_status() {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { wp_die( 'Refusé.' ); }
	check_admin_referer( 'wf_resa_admin' );
	$id  = (int) ( $_POST['id'] ?? 0 );
	$st  = isset( $_POST['statut'] ) ? sanitize_key( $_POST['statut'] ) : '';
	if ( $id && get_post_type( $id ) === 'wf_resa' && in_array( $st, array( 'new', 'confirmed', 'declined' ), true ) ) {
		update_post_meta( $id, '_wf_resa_statut', $st );
		if ( 'confirmed' === $st || 'declined' === $st ) { wf_resa_notify_client( $id, $st ); }
	}
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wf-restaurant-resa' ) );
	exit;
}
add_action( 'admin_post_wf_resa_delete', 'wf_resa_delete' );
function wf_resa_delete() {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { wp_die( 'Refusé.' ); }
	check_admin_referer( 'wf_resa_admin' );
	$id = (int) ( $_POST['id'] ?? 0 );
	if ( $id && get_post_type( $id ) === 'wf_resa' ) { wp_trash_post( $id ); }
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wf-restaurant-resa' ) );
	exit;
}

function wf_resa_render() {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { return; }
	$filter = isset( $_GET['f'] ) ? sanitize_key( $_GET['f'] ) : 'upcoming';
	if ( ! in_array( $filter, array( 'upcoming', 'past', 'all' ), true ) ) { $filter = 'upcoming'; }

	$q = new WP_Query( array( 'post_type' => 'wf_resa', 'post_status' => 'publish', 'posts_per_page' => 300, 'no_found_rows' => true, 'fields' => 'ids' ) );
	$rows = array();
	foreach ( $q->posts as $id ) {
		$rows[] = array(
			'id'       => $id,
			'prenom'   => get_post_meta( $id, '_wf_resa_prenom', true ),
			'nom'      => get_post_meta( $id, '_wf_resa_nom', true ),
			'tel'      => get_post_meta( $id, '_wf_resa_tel', true ),
			'couverts' => (int) get_post_meta( $id, '_wf_resa_couverts', true ),
			'date'     => get_post_meta( $id, '_wf_resa_date', true ),
			'heure'    => get_post_meta( $id, '_wf_resa_heure', true ),
			'service'  => get_post_meta( $id, '_wf_resa_service', true ),
			'email'    => get_post_meta( $id, '_wf_resa_email', true ),
			'note'     => get_post_meta( $id, '_wf_resa_note', true ),
			'statut'   => get_post_meta( $id, '_wf_resa_statut', true ),
		);
	}
	usort( $rows, function ( $a, $b ) {
		return strcmp( $a['date'] . $a['heure'], $b['date'] . $b['heure'] );
	} );
	$today = current_time( 'Y-m-d' );
	$post  = esc_url( admin_url( 'admin-post.php' ) );
	?>
	<style>
		.wf-resa-tabs a{display:inline-block;padding:6px 14px;border-radius:100px;text-decoration:none;font-weight:600;font-size:13px;color:#50575e;background:#f0f0f1;margin-right:6px}
		.wf-resa-tabs a.on{background:#2271b1;color:#fff}
		.wf-resa-card{background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:14px 16px;margin:10px 0;display:flex;flex-wrap:wrap;gap:10px 18px;align-items:center}
		.wf-resa-when{font-weight:800;font-size:16px;min-width:120px}
		.wf-resa-who{flex:1;min-width:160px}
		.wf-resa-who b{font-size:15px}
		.wf-resa-badge{font-size:12px;font-weight:700;padding:3px 10px;border-radius:100px}
		.wf-resa-badge.new{background:#fff4e5;color:#bf6a02}
		.wf-resa-badge.confirmed{background:#e6f4ea;color:#0a7d32}
		.wf-resa-badge.declined{background:#fde8e8;color:#b32d2e}
		.wf-resa-act button{border:0;border-radius:8px;padding:7px 11px;cursor:pointer;font-weight:700;font-size:13px}
		.wf-resa-act .ok{background:#e6f4ea;color:#0a7d32}
		.wf-resa-act .no{background:#fde8e8;color:#b32d2e}
		.wf-resa-act .del{background:#f0f0f1;color:#50575e}
	</style>
	<div class="wrap" style="max-width:900px;">
		<h1 style="font-size:22px;">📅 Réservations</h1>
		<p class="description">Demandes reçues depuis l'élément « Réservation » du site. Le client laisse nom, prénom, téléphone, nombre de personnes, jour et heure.</p>

		<p class="wf-resa-tabs">
			<?php foreach ( array( 'upcoming' => 'À venir', 'past' => 'Passées', 'all' => 'Toutes' ) as $fk => $flabel ) : ?>
				<a class="<?php echo $filter === $fk ? 'on' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wf-restaurant-resa&f=' . $fk ) ); ?>"><?php echo esc_html( $flabel ); ?></a>
			<?php endforeach; ?>
		</p>

		<?php
		$shown = 0;
		foreach ( $rows as $r ) :
			$is_past = ( $r['date'] < $today );
			if ( 'upcoming' === $filter && $is_past ) { continue; }
			if ( 'past' === $filter && ! $is_past ) { continue; }
			$shown++;
			$stt = $r['statut'] ? $r['statut'] : 'new';
			$stlabel = array( 'new' => 'Nouvelle', 'confirmed' => 'Confirmée', 'declined' => 'Refusée' );
			?>
			<div class="wf-resa-card" style="<?php echo $is_past ? 'opacity:.6;' : ''; ?>">
				<div class="wf-resa-when"><?php echo esc_html( date_i18n( 'D j M', strtotime( $r['date'] ) ) ); ?><br><span style="font-size:19px;"><?php echo esc_html( $r['heure'] ); ?></span></div>
				<div class="wf-resa-who">
					<b><?php echo esc_html( $r['prenom'] . ' ' . $r['nom'] ); ?></b> · <?php echo (int) $r['couverts']; ?> pers.<?php if ( 'midi' === $r['service'] || 'soir' === $r['service'] ) : ?> · <span style="text-transform:capitalize;color:#646970;"><?php echo esc_html( $r['service'] ); ?></span><?php endif; ?><br>
					<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $r['tel'] ) ); ?>"><?php echo esc_html( $r['tel'] ); ?></a>
					<?php if ( $r['note'] ) : ?><br><span style="color:#646970;font-size:13px;">« <?php echo esc_html( $r['note'] ); ?> »</span><?php endif; ?>
				</div>
				<span class="wf-resa-badge <?php echo esc_attr( $stt ); ?>"><?php echo esc_html( $stlabel[ $stt ] ?? 'Nouvelle' ); ?></span>
				<div class="wf-resa-act" style="display:flex;gap:6px;">
					<form method="post" action="<?php echo $post; ?>"><?php wp_nonce_field( 'wf_resa_admin' ); ?><input type="hidden" name="action" value="wf_resa_status"><input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>"><input type="hidden" name="statut" value="confirmed"><button class="ok" title="Confirmer">✓</button></form>
					<form method="post" action="<?php echo $post; ?>"><?php wp_nonce_field( 'wf_resa_admin' ); ?><input type="hidden" name="action" value="wf_resa_status"><input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>"><input type="hidden" name="statut" value="declined"><button class="no" title="Refuser">✕</button></form>
					<form method="post" action="<?php echo $post; ?>" onsubmit="return confirm('Supprimer cette réservation ?');"><?php wp_nonce_field( 'wf_resa_admin' ); ?><input type="hidden" name="action" value="wf_resa_delete"><input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>"><button class="del" title="Supprimer">🗑</button></form>
				</div>
			</div>
		<?php endforeach; ?>

		<?php if ( 0 === $shown ) : ?>
			<p style="color:#646970;">Aucune réservation <?php echo 'upcoming' === $filter ? 'à venir' : ( 'past' === $filter ? 'passée' : '' ); ?> pour le moment.</p>
		<?php endif; ?>
	</div>
	<?php
}

/* ============================================================
 *  APP « Mon restaurant » — PWA (Phase 1 : installable + nav)
 * ========================================================== */

/** Pages qui composent l'app restaurateur. */
function wf_resto_app_pages() {
	return array( 'wf-restaurant-today', 'wf-restaurant', 'wf-restaurant-menu', 'wf-restaurant-resa' );
}
function wf_resto_is_app_page() {
	if ( ! is_admin() ) { return false; }
	$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
	return in_array( $page, wf_resto_app_pages(), true );
}
function wf_resto_app() {
	$o = get_option( 'wf_resto_app', array() );
	if ( ! is_array( $o ) ) { $o = array(); }
	return array_merge( array(
		'name'           => 'Mon restaurant',
		'theme_color'    => '#1f2937',
		'icon_id'        => 0,
		'notif_title'    => 'Nouvelle réservation 🍽',
		'notif_body'     => "Une demande vient d'arriver. Ouvrez l'app.",
		'notif_resa_tpl' => 'Résa de {prenom} {nom} · {couverts} couv. le {date} à {heure}',
	), $o );
}
function wf_resto_theme_color() {
	$c = wf_resto_app()['theme_color'];
	return $c ? $c : '#1f2937';
}
function wf_resto_app_icon_url( $size = 192 ) {
	$a = wf_resto_app();
	if ( ! empty( $a['icon_id'] ) ) {
		$u = wp_get_attachment_image_url( (int) $a['icon_id'], $size >= 400 ? 'large' : 'medium' );
		if ( $u ) { return $u; }
		$u = wp_get_attachment_url( (int) $a['icon_id'] );
		if ( $u ) { return $u; }
	}
	return home_url( '/?wf_resto_icon=' . (int) $size );
}

/* Icône de l'app : /?wf_resto_icon=192  (PNG via GD, sinon SVG). */
add_action( 'init', function () {
	if ( empty( $_GET['wf_resto_icon'] ) ) { return; }
	$size = max( 48, min( 512, (int) $_GET['wf_resto_icon'] ) );
	nocache_headers();
	header( 'Cache-Control: public, max-age=86400' );
	$accent = wf_resto_theme_color();
	if ( function_exists( 'imagecreatetruecolor' ) ) {
		$im = imagecreatetruecolor( $size, $size );
		$r = hexdec( substr( $accent, 1, 2 ) ); $g = hexdec( substr( $accent, 3, 2 ) ); $b = hexdec( substr( $accent, 5, 2 ) );
		$bg = imagecolorallocate( $im, $r, $g, $b );
		imagefilledrectangle( $im, 0, 0, $size, $size, $bg );
		$white = imagecolorallocate( $im, 255, 255, 255 );
		imagesetthickness( $im, max( 2, (int) round( $size * 0.035 ) ) );
		// assiette
		$cx = (int) ( $size / 2 ); $cy = (int) ( $size * 0.54 ); $d = (int) ( $size * 0.44 );
		imageellipse( $im, $cx, $cy, $d, $d, $white );
		imageellipse( $im, $cx, $cy, (int) ( $d * 0.6 ), (int) ( $d * 0.6 ), $white );
		// fourchette + couteau
		$y1 = (int) ( $size * 0.20 ); $y2 = (int) ( $size * 0.44 );
		imageline( $im, (int) ( $size * 0.30 ), $y1, (int) ( $size * 0.30 ), $y2, $white );
		imageline( $im, (int) ( $size * 0.70 ), $y1, (int) ( $size * 0.70 ), $y2, $white );
		header( 'Content-Type: image/png' );
		imagepng( $im ); imagedestroy( $im ); exit;
	}
	header( 'Content-Type: image/svg+xml; charset=utf-8' );
	echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 512 512"><rect width="512" height="512" fill="' . esc_attr( $accent ) . '"/><g fill="none" stroke="#fff" stroke-width="18"><circle cx="256" cy="276" r="100"/><circle cx="256" cy="276" r="60"/><line x1="150" y1="110" x2="150" y2="230"/><line x1="362" y1="110" x2="362" y2="230"/></g></svg>';
	exit;
} );

/* Manifest : /?wf_resto_manifest=1 */
add_action( 'init', function () {
	if ( empty( $_GET['wf_resto_manifest'] ) ) { return; }
	nocache_headers();
	header( 'Content-Type: application/manifest+json; charset=utf-8' );
	$app     = wf_resto_app();
	$appname = $app['name'] ? $app['name'] : 'Mon restaurant';
	echo wp_json_encode( array(
		'name'             => $appname,
		'short_name'       => mb_substr( $appname, 0, 12 ),
		'start_url'        => admin_url( 'admin.php?page=wf-restaurant-today' ),
		'scope'            => wp_parse_url( admin_url(), PHP_URL_PATH ),
		'display'          => 'standalone',
		'orientation'      => 'portrait',
		'background_color' => '#f0f0f1',
		'theme_color'      => wf_resto_theme_color(),
		'lang'             => 'fr',
		'icons'            => array(
			array( 'src' => wf_resto_app_icon_url( 192 ), 'sizes' => '192x192', 'purpose' => 'any' ),
			array( 'src' => wf_resto_app_icon_url( 512 ), 'sizes' => '512x512', 'purpose' => 'any maskable' ),
		),
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
} );

/* Service worker : /?wf_resto_sw=1 */
add_action( 'init', function () {
	if ( empty( $_GET['wf_resto_sw'] ) ) { return; }
	nocache_headers();
	header( 'Content-Type: application/javascript; charset=utf-8' );
	header( 'Service-Worker-Allowed: /' );
	$sw_icon = wf_resto_app_icon_url( 192 );
	$sw_resa = admin_url( 'admin.php?page=wf-restaurant-resa' );
	$sw_app  = wf_resto_app();
	?>
const WF_CACHE = 'wf-resto-shell-v1';
self.addEventListener('install', function(e){ self.skipWaiting(); });
self.addEventListener('activate', function(e){ e.waitUntil(self.clients.claim()); });
self.addEventListener('push', function(e){
	var title = <?php echo wp_json_encode( $sw_app['notif_title'] ); ?>;
	var body  = <?php echo wp_json_encode( $sw_app['notif_body'] ); ?>;
	var data  = {};
	if (e.data) { try { var d = e.data.json(); if (d.title) { title = d.title; } if (d.body) { body = d.body; } data = d; } catch (err) { try { body = e.data.text(); } catch (er) {} } }
	var opts = { body: body, icon: <?php echo wp_json_encode( $sw_icon ); ?>, badge: <?php echo wp_json_encode( $sw_icon ); ?>, tag: 'wf-resa', renotify: true, vibrate: [80, 40, 80], data: { url: <?php echo wp_json_encode( $sw_resa ); ?> } };
	if (data.ok) { opts.actions = [{ action: 'confirm', title: '✅ Confirmer' }, { action: 'decline', title: '✕ Refuser' }]; opts.data.ok = data.ok; opts.data.no = data.no; }
	e.waitUntil(self.registration.showNotification(title, opts));
});
self.addEventListener('notificationclick', function(e){
	e.notification.close();
	var d = e.notification.data || {};
	var ic = <?php echo wp_json_encode( $sw_icon ); ?>;
	if (e.action === 'confirm') { if (d.ok) { e.waitUntil(fetch(d.ok, { credentials: 'same-origin' }).then(function(){ return self.registration.showNotification('Réservation confirmée ✔', { icon: ic, tag: 'wf-resa-done' }); })); return; } }
	if (e.action === 'decline') { if (d.no) { e.waitUntil(fetch(d.no, { credentials: 'same-origin' }).then(function(){ return self.registration.showNotification('Réservation refusée', { icon: ic, tag: 'wf-resa-done' }); })); return; } }
	var target = d.url || <?php echo wp_json_encode( $sw_resa ); ?>;
	e.waitUntil(self.clients.matchAll({ type: 'window' }).then(function(cl){ for (var i = 0; i < cl.length; i++){ if (cl[i].url.indexOf('wf-restaurant') > -1){ return cl[i].focus(); } } if (self.clients.openWindow) { return self.clients.openWindow(target); } }));
});
self.addEventListener('fetch', function(e){
	var req = e.request;
	if (req.method !== 'GET') { return; }
	e.respondWith(
		fetch(req).then(function(res){
			try { var copy = res.clone(); caches.open(WF_CACHE).then(function(c){ c.put(req, copy); }); } catch (err) {}
			return res;
		}).catch(function(){ return caches.match(req); })
	);
});
	<?php
	exit;
} );

/* Injection <head> sur les pages de l'app. */
add_action( 'admin_head', function () {
	if ( ! wf_resto_is_app_page() ) { return; }
	$manifest = home_url( '/?wf_resto_manifest=1' );
	$icon     = wf_resto_app_icon_url( 192 );
	$sw       = home_url( '/?wf_resto_sw=1' );
	$scope    = wp_parse_url( admin_url(), PHP_URL_PATH );
	echo '<link rel="manifest" href="' . esc_url( $manifest ) . '">' . "\n";
	echo '<meta name="theme-color" content="' . esc_attr( wf_resto_theme_color() ) . '">' . "\n";
	echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
	echo '<meta name="apple-mobile-web-app-title" content="' . esc_attr( wf_resto_app()['name'] ) . '">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $icon ) . '">' . "\n";
	?>
	<script>
	if ('serviceWorker' in navigator) {
		window.addEventListener('load', function(){
			navigator.serviceWorker.register(<?php echo wp_json_encode( $sw ); ?>, { scope: <?php echo wp_json_encode( $scope ); ?> }).catch(function(){});
		});
	}
	</script>
	<?php
} );

/* Barre de navigation basse (app) + bouton installer. */
add_action( 'admin_footer', function () {
	if ( ! wf_resto_is_app_page() ) { return; }
	$page  = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
	$new   = function_exists( 'wf_resa_count_new' ) ? (int) wf_resa_count_new() : 0;
	$items = array(
		array( 'wf-restaurant-today', 'admin.php?page=wf-restaurant-today', 'calendar-alt', "Aujourd'hui", $new ),
		array( 'wf-restaurant',       'admin.php?page=wf-restaurant',       'store',        'Réglages',    0 ),
		array( 'wf-restaurant-menu',  'admin.php?page=wf-restaurant-menu',  'list-view',    'Menu',        0 ),
		array( 'wf-restaurant-resa',  'admin.php?page=wf-restaurant-resa',  'backup',       'Historique',  0 ),
	);
	$accent = wf_resto_theme_color();
	echo '<nav class="wf-appnav" aria-label="Navigation restaurant">';
	foreach ( $items as $it ) {
		$active = ( $page === $it[0] ) ? ' is-active' : '';
		echo '<a class="wf-appnav-i' . $active . '" href="' . esc_url( admin_url( $it[1] ) ) . '">';
		echo '<span class="dashicons dashicons-' . esc_attr( $it[2] ) . '"></span>';
		echo '<span class="wf-appnav-l">' . esc_html( $it[3] );
		if ( $it[4] > 0 ) { echo '<b class="wf-appnav-badge">' . (int) $it[4] . '</b>'; }
		echo '</span></a>';
	}
	echo '<button type="button" class="wf-appnav-i wf-appnav-install" id="wf-app-install" style="display:none;"><span class="dashicons dashicons-download"></span><span class="wf-appnav-l">Installer</span></button>';
	echo '</nav>';
	?>
	<style>
	#wpbody-content{padding-bottom:100px !important}
	.wf-appnav{position:fixed;left:0;right:0;bottom:0;z-index:99999;display:flex;gap:5px;background:#fff;border-top:1px solid #dcdcde;box-shadow:0 -4px 18px rgba(0,0,0,.12);padding:8px calc(8px + env(safe-area-inset-right)) calc(8px + env(safe-area-inset-bottom)) calc(8px + env(safe-area-inset-left))}
	.wf-appnav-i{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;padding:9px 4px;color:#374151;text-decoration:none;border:0;background:#f1f3f5;border-radius:14px;cursor:pointer;font-size:12px;font-weight:700;position:relative;min-height:58px}
	.wf-appnav-i:hover{background:#e7eaee}
	.wf-appnav-i .dashicons{font-size:26px;width:26px;height:26px}
	.wf-appnav-i.is-active{background:<?php echo esc_attr( $accent ); ?>;color:#fff}
	.wf-appnav-l{position:relative;line-height:1.1}
	.wf-appnav-badge{position:absolute;top:-9px;right:-18px;background:#e11d48;color:#fff;font-size:11px;line-height:18px;min-width:18px;height:18px;border-radius:9px;padding:0 5px;text-align:center;font-weight:800;box-shadow:0 0 0 2px #fff}
	.wf-appnav-install{background:#dcfce7;color:#15803d}
	@media(min-width:961px){.wf-appnav{max-width:820px;margin:0 auto;border-radius:16px 16px 0 0;left:50%;transform:translateX(-50%)}}
	</style>
	<script>
	(function(){
		var deferred = null;
		var btn = document.getElementById('wf-app-install');
		window.addEventListener('beforeinstallprompt', function(e){
			e.preventDefault(); deferred = e;
			if (btn) { btn.style.display = ''; }
		});
		if (btn) {
			btn.addEventListener('click', function(){
				if (deferred) { deferred.prompt(); deferred = null; btn.style.display = 'none'; return; }
				var ua = navigator.userAgent || '';
				if (/iphone|ipad|ipod/i.test(ua)) { alert("Sur iPhone : bouton Partager ⬆️ puis « Sur l'écran d'accueil »."); }
				else { alert("Menu du navigateur ⋮ puis « Installer l'application » / « Ajouter à l'écran d'accueil »."); }
			});
		}
		// iOS : pas de beforeinstallprompt → montrer le bouton si pas déjà installé.
		var iOS = /iphone|ipad|ipod/i.test(navigator.userAgent || '');
		var standalone = window.navigator.standalone === true || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches);
		if (btn) { if (iOS) { if (!standalone) { btn.style.display = ''; } } }
	})();
	</script>
	<?php
} );

/* ============================================================
 *  APP — Phase 2 : notifications push (Web Push / VAPID, sans payload)
 * ========================================================== */

function wf_b64url( $bin ) { return rtrim( strtr( base64_encode( $bin ), '+/', '-_' ), '=' ); }

/** Clés VAPID (générées une fois, stockées en option). */
function wf_resto_vapid() {
	$v = get_option( 'wf_resto_vapid' );
	if ( is_array( $v ) && ! empty( $v['public'] ) && ! empty( $v['pem'] ) ) { return $v; }
	if ( ! function_exists( 'openssl_pkey_new' ) ) { return null; }
	$res = openssl_pkey_new( array( 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ) );
	if ( ! $res ) { return null; }
	$pem = '';
	if ( ! openssl_pkey_export( $res, $pem ) ) { return null; }
	$d = openssl_pkey_get_details( $res );
	if ( empty( $d['ec']['x'] ) || empty( $d['ec']['y'] ) ) { return null; }
	$pub = "\x04" . str_pad( $d['ec']['x'], 32, "\x00", STR_PAD_LEFT ) . str_pad( $d['ec']['y'], 32, "\x00", STR_PAD_LEFT );
	$v = array( 'public' => wf_b64url( $pub ), 'pem' => $pem );
	update_option( 'wf_resto_vapid', $v, false );
	return $v;
}

/** Signature DER ECDSA -> concat R||S (64 octets) pour JOSE ES256. */
function wf_der_to_raw( $der ) {
	if ( strlen( $der ) < 8 || ord( $der[0] ) !== 0x30 ) { return ''; }
	$off = 2;
	if ( ord( $der[1] ) & 0x80 ) { $off = 2 + ( ord( $der[1] ) & 0x7f ); }
	if ( ord( $der[ $off ] ) !== 0x02 ) { return ''; }
	$rlen = ord( $der[ $off + 1 ] ); $r = substr( $der, $off + 2, $rlen ); $off = $off + 2 + $rlen;
	if ( ord( $der[ $off ] ) !== 0x02 ) { return ''; }
	$slen = ord( $der[ $off + 1 ] ); $s = substr( $der, $off + 2, $slen );
	$r = ltrim( $r, "\x00" ); $s = ltrim( $s, "\x00" );
	if ( strlen( $r ) > 32 || strlen( $s ) > 32 ) { return ''; }
	$r = str_pad( $r, 32, "\x00", STR_PAD_LEFT );
	$s = str_pad( $s, 32, "\x00", STR_PAD_LEFT );
	return $r . $s;
}

/** JWT VAPID (ES256) signé pour un endpoint (audience = origine). */
function wf_resto_vapid_jwt( $aud ) {
	$v = wf_resto_vapid(); if ( ! $v ) { return ''; }
	$sub = get_option( 'admin_email' );
	$sub = $sub ? 'mailto:' . $sub : 'mailto:contact@example.com';
	$header  = wf_b64url( wp_json_encode( array( 'typ' => 'JWT', 'alg' => 'ES256' ) ) );
	$payload = wf_b64url( wp_json_encode( array( 'aud' => $aud, 'exp' => time() + 43200, 'sub' => $sub ) ) );
	$signing = $header . '.' . $payload;
	$pkey = openssl_pkey_get_private( $v['pem'] ); if ( ! $pkey ) { return ''; }
	$der = '';
	if ( ! openssl_sign( $signing, $der, $pkey, OPENSSL_ALGO_SHA256 ) ) { return ''; }
	$raw = wf_der_to_raw( $der ); if ( '' === $raw ) { return ''; }
	return $signing . '.' . wf_b64url( $raw );
}

function wf_b64url_decode( $s ) {
	$s = strtr( (string) $s, '-_', '+/' );
	$pad = strlen( $s ) % 4;
	if ( $pad ) { $s .= str_repeat( '=', 4 - $pad ); }
	return base64_decode( $s );
}

/** Reconstruit une clé publique EC (P-256) à partir d'un point brut 0x04|x|y (65 o). */
function wf_resto_ec_pub_from_point( $point ) {
	$der = hex2bin( '3059301306072a8648ce3d020106082a8648ce3d030107034200' ) . $point;
	$pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split( base64_encode( $der ), 64, "\n" ) . "-----END PUBLIC KEY-----\n";
	return openssl_pkey_get_public( $pem );
}

/** Chiffre un payload pour Web Push (RFC 8291, aes128gcm). Renvoie le corps binaire ou null. */
function wf_resto_push_encrypt( $plaintext, $p256dh_b64, $auth_b64 ) {
	if ( ! function_exists( 'openssl_pkey_derive' ) || ! function_exists( 'hash_hkdf' ) ) { return null; }
	$ua_public = wf_b64url_decode( $p256dh_b64 );
	$auth      = wf_b64url_decode( $auth_b64 );
	if ( strlen( $ua_public ) !== 65 || strlen( $auth ) < 16 ) { return null; }
	$eph = openssl_pkey_new( array( 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ) );
	if ( ! $eph ) { return null; }
	$ed = openssl_pkey_get_details( $eph );
	if ( empty( $ed['ec']['x'] ) || empty( $ed['ec']['y'] ) ) { return null; }
	$as_public = "\x04" . str_pad( $ed['ec']['x'], 32, "\x00", STR_PAD_LEFT ) . str_pad( $ed['ec']['y'], 32, "\x00", STR_PAD_LEFT );
	$ua_key = wf_resto_ec_pub_from_point( $ua_public );
	if ( ! $ua_key ) { return null; }
	$shared = openssl_pkey_derive( $ua_key, $eph, 32 );
	if ( ! $shared ) { return null; }
	$key_info = "WebPush: info\x00" . $ua_public . $as_public;
	$ikm   = hash_hkdf( 'sha256', $shared, 32, $key_info, $auth );
	$salt  = random_bytes( 16 );
	$cek   = hash_hkdf( 'sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $salt );
	$nonce = hash_hkdf( 'sha256', $ikm, 12, "Content-Encoding: nonce\x00", $salt );
	$tag   = '';
	$cipher = openssl_encrypt( $plaintext . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16 );
	if ( false === $cipher ) { return null; }
	$cipher .= $tag;
	return $salt . pack( 'N', 4096 ) . chr( 65 ) . $as_public . $cipher;
}

/** Envoie une notif push (détaillée si possible, sinon générique). Purge les abonnements expirés. */
function wf_resto_push_send( $title = '', $body = '', $data = array() ) {
	$v = wf_resto_vapid(); if ( ! $v ) { return 0; }
	$a = wf_resto_app();
	if ( '' === $title ) { $title = $a['notif_title']; }
	if ( '' === $body )  { $body  = $a['notif_body']; }
	$subs = get_option( 'wf_resto_push_subs', array() );
	if ( ! is_array( $subs ) || empty( $subs ) ) { return 0; }
	$payload = wp_json_encode( array_merge( array( 'title' => $title, 'body' => $body ), is_array( $data ) ? $data : array() ) );
	$sent = 0; $changed = false;
	foreach ( $subs as $hash => $sub ) {
		$endpoint = isset( $sub['endpoint'] ) ? $sub['endpoint'] : '';
		if ( ! $endpoint ) { continue; }
		$scheme = wp_parse_url( $endpoint, PHP_URL_SCHEME );
		$host   = wp_parse_url( $endpoint, PHP_URL_HOST );
		$jwt = wf_resto_vapid_jwt( $scheme . '://' . $host ); if ( ! $jwt ) { continue; }
		$headers = array( 'Authorization' => 'vapid t=' . $jwt . ', k=' . $v['public'], 'TTL' => '3600' );
		$bodybin = '';
		if ( ! empty( $sub['p256dh'] ) && ! empty( $sub['auth'] ) ) {
			$enc = wf_resto_push_encrypt( $payload, $sub['p256dh'], $sub['auth'] );
			if ( null !== $enc ) {
				$bodybin = $enc;
				$headers['Content-Encoding'] = 'aes128gcm';
				$headers['Content-Type']     = 'application/octet-stream';
			}
		}
		if ( '' === $bodybin ) { $headers['Content-Length'] = '0'; }
		$res = wp_remote_post( $endpoint, array( 'timeout' => 8, 'headers' => $headers, 'body' => $bodybin ) );
		if ( ! is_wp_error( $res ) ) {
			$code = (int) wp_remote_retrieve_response_code( $res );
			if ( 404 === $code || 410 === $code ) { unset( $subs[ $hash ] ); $changed = true; }
			elseif ( $code >= 200 && $code < 300 ) { $sent++; }
		}
	}
	if ( $changed ) { update_option( 'wf_resto_push_subs', $subs, false ); }
	return $sent;
}

/* Nouvelle réservation -> push. */
add_action( 'save_post_wf_resa', function ( $post_id, $post, $update ) {
	if ( $update ) { return; }
	if ( wp_is_post_revision( $post_id ) ) { return; }
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
	$prenom  = get_post_meta( $post_id, '_wf_resa_prenom', true );
	$nom     = get_post_meta( $post_id, '_wf_resa_nom', true );
	$date    = get_post_meta( $post_id, '_wf_resa_date', true );
	$heure   = get_post_meta( $post_id, '_wf_resa_heure', true );
	$service = get_post_meta( $post_id, '_wf_resa_service', true );
	$cv      = (int) get_post_meta( $post_id, '_wf_resa_couverts', true );
	$who     = trim( $prenom . ' ' . $nom );
	$dtxt    = $date ? date_i18n( 'd/m', strtotime( $date ) ) : '';
	$svc     = 'midi' === $service ? 'midi' : ( 'soir' === $service ? 'soir' : '' );
	$tpl     = wf_resto_app()['notif_resa_tpl'];
	$body    = trim( strtr( (string) $tpl, array(
		'{prenom}'      => (string) $prenom,
		'{nom}'         => (string) $nom,
		'{nom_complet}' => $who,
		'{date}'        => $dtxt,
		'{heure}'       => (string) $heure,
		'{couverts}'    => $cv ? (string) $cv : '',
		'{service}'     => $svc,
	) ) );
	if ( '' === $body ) { $body = 'Nouvelle réservation'; }
	$ajax = admin_url( 'admin-ajax.php' );
	$data = array(
		'id' => (int) $post_id,
		'ok' => add_query_arg( array( 'action' => 'wf_resa_action', 'id' => (int) $post_id, 'a' => 'confirm', 't' => wf_resto_resa_token( $post_id, 'confirm' ) ), $ajax ),
		'no' => add_query_arg( array( 'action' => 'wf_resa_action', 'id' => (int) $post_id, 'a' => 'decline', 't' => wf_resto_resa_token( $post_id, 'decline' ) ), $ajax ),
	);
	wf_resto_push_send( '', $body, $data );
}, 20, 3 );

/** Jeton HMAC pour confirmer/refuser une résa depuis la notification (sans login). */
function wf_resto_resa_token( $id, $action ) {
	return substr( hash_hmac( 'sha256', 'wfresa|' . (int) $id . '|' . $action, wp_salt( 'auth' ) ), 0, 24 );
}

/** Change le statut d'une résa + e-mail client. */
function wf_resa_set_status( $id, $status ) {
	$id = (int) $id;
	if ( ! $id || 'wf_resa' !== get_post_type( $id ) ) { return false; }
	update_post_meta( $id, '_wf_resa_statut', $status );
	if ( in_array( $status, array( 'confirmed', 'declined' ), true ) && function_exists( 'wf_resa_notify_client' ) ) {
		wf_resa_notify_client( $id, $status );
	}
	return true;
}

/* Confirmer / Refuser depuis la notification (auth = jeton). */
add_action( 'wp_ajax_wf_resa_action', 'wf_resa_action_handler' );
add_action( 'wp_ajax_nopriv_wf_resa_action', 'wf_resa_action_handler' );
function wf_resa_action_handler() {
	$id = (int) ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : 0 );
	$a  = isset( $_REQUEST['a'] ) ? sanitize_key( $_REQUEST['a'] ) : '';
	$t  = isset( $_REQUEST['t'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['t'] ) ) : '';
	if ( ! $id || ! in_array( $a, array( 'confirm', 'decline' ), true ) ) { wp_send_json_error( 'params' ); }
	if ( ! hash_equals( wf_resto_resa_token( $id, $a ), $t ) ) { wp_send_json_error( 'token' ); }
	wf_resa_set_status( $id, 'confirm' === $a ? 'confirmed' : 'declined' );
	wp_send_json_success( $a );
}

/* ============================================================
 *  APP — écran « Aujourd'hui »
 * ========================================================== */

function wf_resa_today_list( $date = '' ) {
	if ( '' === $date ) { $date = wf_resto_today(); }
	$q = new WP_Query( array( 'post_type' => 'wf_resa', 'post_status' => 'publish', 'posts_per_page' => 200, 'no_found_rows' => true, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_wf_resa_date', 'value' => $date ) ) ) );
	$out = array();
	foreach ( $q->posts as $id ) {
		$out[] = array(
			'id' => (int) $id,
			'prenom' => get_post_meta( $id, '_wf_resa_prenom', true ),
			'nom' => get_post_meta( $id, '_wf_resa_nom', true ),
			'tel' => get_post_meta( $id, '_wf_resa_tel', true ),
			'heure' => get_post_meta( $id, '_wf_resa_heure', true ),
			'service' => get_post_meta( $id, '_wf_resa_service', true ),
			'couverts' => (int) get_post_meta( $id, '_wf_resa_couverts', true ),
			'statut' => get_post_meta( $id, '_wf_resa_statut', true ),
			'note' => get_post_meta( $id, '_wf_resa_note', true ),
		);
	}
	usort( $out, function ( $a, $b ) { return strcmp( (string) $a['heure'], (string) $b['heure'] ); } );
	return $out;
}

/*
 * Priorité 11, et surtout pas 1 : c'est add_menu_page(), exécuté en priorité
 * 10, qui remplit $admin_page_hooks['wf-restaurant']. Enregistré avant lui, ce
 * sous-menu recevait le nom de hook « admin_page_wf-restaurant-today » au lieu
 * de « mon-restaurant_page_wf-restaurant-today ». Au chargement de la page,
 * WordPress recalcule le bon nom, ne le trouve pas dans $_registered_pages et
 * refuse l'accès : la page répondait 403 « vous n'avez pas l'autorisation ».
 * $position = 0 garde l'onglet en tête du sous-menu, ce que la priorité 1
 * cherchait à obtenir.
 */
add_action( 'admin_menu', function () {
	add_submenu_page( 'wf-restaurant', "Aujourd'hui", '<span class="dashicons dashicons-calendar-alt wf-mi"></span> Aujourd&rsquo;hui', WF_RESTO_CAP, 'wf-restaurant-today', 'wf_resto_today_render', 0 );
}, 11 );

function wf_resto_today_render() {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { return; }
	$date   = wf_resto_today();
	$state  = function_exists( 'wf_resto_is_open_now' ) ? wf_resto_is_open_now() : array( 'open' => false, 'message' => '' );
	$list   = wf_resa_today_list( $date );
	$cmap   = wf_resa_client_stats_map();
	$notice = get_transient( 'wf_resto_notice_' . get_current_user_id() );
	if ( false !== $notice ) { delete_transient( 'wf_resto_notice_' . get_current_user_id() ); }
	$midi = 0; $soir = 0; $nnew = 0;
	foreach ( $list as $r ) {
		if ( in_array( $r['statut'], array( 'declined', 'noshow' ), true ) ) { continue; }
		if ( 'midi' === $r['service'] ) { $midi += $r['couverts']; } elseif ( 'soir' === $r['service'] ) { $soir += $r['couverts']; }
		if ( '' === $r['statut'] || 'new' === $r['statut'] ) { $nnew++; }
	}
	$store    = function_exists( 'wf_resto_get' ) ? wf_resto_get() : array();
	$blocked  = isset( $store['resa']['blocked'] ) ? (array) $store['resa']['blocked'] : array();
	$isBlock  = in_array( $date, $blocked, true );
	$post     = esc_url( admin_url( 'admin-post.php' ) );
	$acc      = wf_resto_theme_color();
	$badges   = array( 'new' => array( 'Nouveau', '#b45309', '#fef3c7' ), 'confirmed' => array( 'Confirmé', '#15803d', '#dcfce7' ), 'declined' => array( 'Refusé', '#b91c1c', '#fee2e2' ), 'noshow' => array( 'No-show', '#6b7280', '#e5e7eb' ) );
	?>
	<style>
		.wf-td{max-width:720px;margin:8px 0}
		.wf-td *{box-sizing:border-box}
		.wf-td-live{display:flex;align-items:center;gap:10px;padding:14px 16px;border-radius:14px;font-weight:800;font-size:17px;margin:12px 0;background:<?php echo $state['open'] ? '#e6f4ea' : '#fde8e8'; ?>;color:<?php echo $state['open'] ? '#0a7d32' : '#b32d2e'; ?>}
		.wf-td-live .dot{width:12px;height:12px;border-radius:50%;background:currentColor}
		.wf-td-push{width:100%;background:<?php echo esc_attr( $acc ); ?>;color:#fff;border:0;border-radius:14px;padding:16px;font-size:16px;font-weight:800;cursor:pointer;margin:2px 0 12px;box-shadow:0 4px 14px rgba(0,0,0,.18)}
		.wf-td-push:active{transform:scale(.99)}
		.wf-td-stats{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:12px 0}
		.wf-td-stat{background:#fff;border:1px solid #e6e8ec;border-radius:16px;padding:16px;text-align:center}
		.wf-td-stat b{display:block;font-size:34px;line-height:1;color:<?php echo esc_attr( $acc ); ?>}
		.wf-td-stat span{font-size:13px;color:#6b7177;font-weight:700;text-transform:uppercase;letter-spacing:.03em}
		.wf-td-block{display:flex;gap:10px;flex-wrap:wrap;margin:10px 0}
		.wf-td-block button{border:0;border-radius:12px;padding:12px 18px;font-weight:800;cursor:pointer;font-size:15px}
		.wf-td-r{background:#fff;border:1px solid #e6e8ec;border-left:5px solid <?php echo esc_attr( $acc ); ?>;border-radius:14px;padding:14px 16px;margin:10px 0}
		.wf-td-r.off{opacity:.55}
		.wf-td-r-top{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
		.wf-td-r-h{font-weight:900;font-size:20px}
		.wf-td-r-name{font-weight:700;flex:1;min-width:0}
		.wf-td-r-cv{background:<?php echo esc_attr( $acc ); ?>;color:#fff;border-radius:100px;padding:3px 10px;font-weight:800;font-size:13px}
		.wf-td-bdg{font-size:11px;font-weight:800;padding:3px 9px;border-radius:100px}
		.wf-td-r-meta{color:#6b7177;font-size:13px;margin-top:4px}
		.wf-td-r-meta a{color:<?php echo esc_attr( $acc ); ?>;font-weight:700;text-decoration:none}
		.wf-td-acts{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
		.wf-td-acts button{flex:1;min-width:90px;border:0;border-radius:10px;padding:11px;font-weight:800;cursor:pointer;font-size:14px}
		.wf-td-ok{background:#dcfce7;color:#15803d}.wf-td-ok:hover{background:#15803d;color:#fff}
		.wf-td-no{background:#fee2e2;color:#b91c1c}.wf-td-no:hover{background:#b91c1c;color:#fff}
		.wf-td-ns{background:#e5e7eb;color:#374151}.wf-td-ns:hover{background:#374151;color:#fff}
	</style>
	<div class="wrap wf-td">
		<h1 style="font-size:22px;">📅 Aujourd'hui <span style="font-weight:400;color:#8a8f98;font-size:16px;"><?php echo esc_html( date_i18n( 'l j F', strtotime( $date ) ) ); ?></span></h1>
		<?php if ( false !== $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>

		<div class="wf-td-live"><span class="dot"></span><?php echo esc_html( $state['open'] ? 'OUVERT' : ( ! empty( $state['message'] ) ? $state['message'] : 'FERMÉ' ) ); ?><?php if ( $nnew ) : ?><span style="margin-left:auto;background:#c0392b;color:#fff;border-radius:100px;padding:3px 12px;font-size:14px;"><?php echo (int) $nnew; ?> à traiter</span><?php endif; ?></div>

		<button type="button" id="wf-push-btn" class="wf-td-push" style="display:none;">🔔 Activer les notifications de réservation</button>

		<div class="wf-td-stats">
			<div class="wf-td-stat"><b><?php echo (int) $midi; ?></b><span>Couverts midi</span></div>
			<div class="wf-td-stat"><b><?php echo (int) $soir; ?></b><span>Couverts soir</span></div>
		</div>

		<div class="wf-td-block">
			<form method="post" action="<?php echo $post; ?>">
				<?php wp_nonce_field( 'wf_resto_today' ); ?>
				<input type="hidden" name="action" value="wf_resto_block_day">
				<input type="hidden" name="date" value="<?php echo esc_attr( $date ); ?>">
				<input type="hidden" name="op" value="<?php echo $isBlock ? 'unblock' : 'block'; ?>">
				<button type="submit" style="background:<?php echo $isBlock ? '#e5e7eb;color:#374151' : '#fee2e2;color:#b91c1c'; ?>;"><?php echo $isBlock ? '↩ Rouvrir les réservations du jour' : '🚫 Bloquer les réservations du jour'; ?></button>
			</form>
		</div>

		<h2 style="font-size:17px;margin-top:18px;">Réservations du jour (<?php echo count( $list ); ?>)</h2>
		<?php if ( empty( $list ) ) : ?>
			<p style="color:#6b7177;">Aucune réservation aujourd'hui.</p>
		<?php else : foreach ( $list as $r ) :
			$off = in_array( $r['statut'], array( 'declined', 'noshow' ), true );
			$who = trim( $r['prenom'] . ' ' . $r['nom'] );
			$st  = $r['statut'] ? $r['statut'] : 'new';
			$bd  = isset( $badges[ $st ] ) ? $badges[ $st ] : $badges['new'];
			?>
			<div class="wf-td-r<?php echo $off ? ' off' : ''; ?>">
				<div class="wf-td-r-top">
					<span class="wf-td-r-h"><?php echo esc_html( $r['heure'] ); ?></span>
					<span class="wf-td-r-name"><?php echo esc_html( $who ); ?></span>
					<span class="wf-td-r-cv"><?php echo (int) $r['couverts']; ?> couv.</span>
					<span class="wf-td-bdg" style="color:<?php echo esc_attr( $bd[1] ); ?>;background:<?php echo esc_attr( $bd[2] ); ?>;"><?php echo esc_html( $bd[0] ); ?></span>
				</div>
				<div class="wf-td-r-meta">
					<?php echo esc_html( 'midi' === $r['service'] ? 'Midi' : ( 'soir' === $r['service'] ? 'Soir' : '' ) ); ?>
					<?php if ( $r['tel'] ) : ?> · <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $r['tel'] ) ); ?>">📞 <?php echo esc_html( $r['tel'] ); ?></a><?php endif; ?>
					<?php if ( $r['note'] ) : ?> · <em><?php echo esc_html( $r['note'] ); ?></em><?php endif; ?>
					<?php $teln = preg_replace( '/[^0-9]/', '', (string) $r['tel'] ); $cs = isset( $cmap[ $teln ] ) ? $cmap[ $teln ] : null; ?>
					<?php if ( $cs && $cs['total'] > 1 ) : ?> · <span title="Réservations de ce client">🔁 <?php echo (int) $cs['total']; ?> résa</span><?php endif; ?>
					<?php if ( $cs && $cs['noshow'] > 0 ) : ?> · <span style="color:#b91c1c;font-weight:700;">⚠ <?php echo (int) $cs['noshow']; ?> no-show</span><?php endif; ?>
					<?php $pn = get_post_meta( $r['id'], '_wf_resa_privnote', true ); if ( $pn ) : ?><br><span style="color:#7c3aed;">📝 <?php echo esc_html( $pn ); ?></span><?php endif; ?>
				</div>
				<div class="wf-td-acts">
					<?php $mk = function ( $op, $lbl, $cls ) use ( $post, $r, $date ) { ?>
						<form method="post" action="<?php echo $post; ?>" style="flex:1;display:flex;">
							<?php wp_nonce_field( 'wf_resto_today' ); ?>
							<input type="hidden" name="action" value="wf_resto_resa_quick">
							<input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
							<input type="hidden" name="op" value="<?php echo esc_attr( $op ); ?>">
							<input type="hidden" name="back" value="<?php echo esc_attr( $date ); ?>">
							<button type="submit" class="<?php echo esc_attr( $cls ); ?>" style="flex:1;"><?php echo esc_html( $lbl ); ?></button>
						</form>
					<?php }; ?>
					<?php if ( 'confirmed' !== $st ) { $mk( 'confirmed', '✅ Confirmer', 'wf-td-ok' ); } ?>
					<?php if ( 'declined' !== $st ) { $mk( 'declined', '✕ Refuser', 'wf-td-no' ); } ?>
					<?php if ( 'noshow' !== $st ) { $mk( 'noshow', '👻 No-show', 'wf-td-ns' ); } ?>
				</div>
				<details style="margin-top:8px;"><summary style="cursor:pointer;font-size:13px;color:#6b7177;">📝 Note privée</summary>
					<form method="post" action="<?php echo $post; ?>" style="display:flex;gap:6px;margin-top:6px;">
						<?php wp_nonce_field( 'wf_resto_today' ); ?>
						<input type="hidden" name="action" value="wf_resto_resa_note">
						<input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
						<input type="text" name="privnote" value="<?php echo esc_attr( get_post_meta( $r['id'], '_wf_resa_privnote', true ) ); ?>" placeholder="Habitué, allergie, table préférée…" style="flex:1;padding:8px;border:1px solid #d5d5d5;border-radius:8px;">
						<button type="submit" class="button">OK</button>
					</form>
				</details>
			</div>
		<?php endforeach; endif; ?>
	</div>
	<?php
}

/* Actions rapides depuis « Aujourd'hui ». */
add_action( 'admin_post_wf_resto_resa_quick', function () {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { wp_die( 'Refusé.' ); }
	check_admin_referer( 'wf_resto_today' );
	$id = (int) ( $_POST['id'] ?? 0 );
	$op = sanitize_key( $_POST['op'] ?? '' );
	if ( $id && in_array( $op, array( 'confirmed', 'declined', 'noshow' ), true ) ) { wf_resa_set_status( $id, $op ); }
	wp_safe_redirect( admin_url( 'admin.php?page=wf-restaurant-today' ) );
	exit;
} );

/* Bloquer / rouvrir les réservations d'un jour. */
add_action( 'admin_post_wf_resto_block_day', function () {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { wp_die( 'Refusé.' ); }
	check_admin_referer( 'wf_resto_today' );
	$date = isset( $_POST['date'] ) ? preg_replace( '/[^0-9\-]/', '', $_POST['date'] ) : '';
	$op   = sanitize_key( $_POST['op'] ?? '' );
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && function_exists( 'wf_resto_get' ) ) {
		$s = wf_resto_get();
		$blocked = isset( $s['resa']['blocked'] ) ? (array) $s['resa']['blocked'] : array();
		if ( 'block' === $op ) { if ( ! in_array( $date, $blocked, true ) ) { $blocked[] = $date; } }
		else { $blocked = array_values( array_diff( $blocked, array( $date ) ) ); }
		$opt = get_option( WF_RESTO_OPT, array() ); if ( ! is_array( $opt ) ) { $opt = array(); }
		if ( ! isset( $opt['resa'] ) || ! is_array( $opt['resa'] ) ) { $opt['resa'] = $s['resa']; }
		$opt['resa']['blocked'] = $blocked;
		update_option( WF_RESTO_OPT, $opt );
		if ( function_exists( 'wf_resto_purge' ) ) { wf_resto_purge(); }
		set_transient( 'wf_resto_notice_' . get_current_user_id(), 'block' === $op ? 'Réservations bloquées pour ce jour.' : 'Réservations rouvertes.', 30 );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=wf-restaurant-today' ) );
	exit;
} );

/* ============================================================
 *  Rappel J-1 (cron) + historique client + note privée
 * ========================================================== */

add_action( 'wf_resa_reminder_event', 'wf_resa_send_reminders' );
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'wf_resa_reminder_event' ) ) {
		wp_schedule_event( time() + 600, 'daily', 'wf_resa_reminder_event' );
	}
} );
register_deactivation_hook( __FILE__, function () {
	$ts = wp_next_scheduled( 'wf_resa_reminder_event' );
	if ( $ts ) { wp_unschedule_event( $ts, 'wf_resa_reminder_event' ); }
} );

function wf_resa_send_reminders() {
	$tomorrow = date( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' +1 day' ) );
	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	foreach ( wf_resa_today_list( $tomorrow ) as $r ) {
		if ( 'confirmed' !== $r['statut'] ) { continue; }
		if ( get_post_meta( $r['id'], '_wf_resa_reminded', true ) ) { continue; }
		update_post_meta( $r['id'], '_wf_resa_reminded', 1 );
		$email = get_post_meta( $r['id'], '_wf_resa_email', true );
		if ( ! $email || ! is_email( $email ) ) { continue; }
		$when = date_i18n( 'l j F', strtotime( $tomorrow ) ) . ' à ' . $r['heure'];
		wp_mail( $email, '[' . $site . '] Rappel de votre réservation', "Bonjour " . $r['prenom'] . ",\n\nPetit rappel : votre réservation pour " . (int) $r['couverts'] . " personne(s) est prévue " . $when . ".\nÀ très vite !\n\n" . $site );
	}
}

/** Stats d'un client par téléphone (normalisé) sur toutes ses réservations. */
function wf_resa_client_stats_map() {
	$q = new WP_Query( array( 'post_type' => 'wf_resa', 'post_status' => 'publish', 'posts_per_page' => 1000, 'no_found_rows' => true, 'fields' => 'ids' ) );
	$map = array();
	foreach ( $q->posts as $id ) {
		$tel = preg_replace( '/[^0-9]/', '', (string) get_post_meta( $id, '_wf_resa_tel', true ) );
		if ( strlen( $tel ) < 6 ) { continue; }
		if ( ! isset( $map[ $tel ] ) ) { $map[ $tel ] = array( 'total' => 0, 'noshow' => 0 ); }
		$map[ $tel ]['total']++;
		if ( 'noshow' === get_post_meta( $id, '_wf_resa_statut', true ) ) { $map[ $tel ]['noshow']++; }
	}
	return $map;
}

/* Note privée sur une réservation. */
add_action( 'admin_post_wf_resto_resa_note', function () {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { wp_die( 'Refusé.' ); }
	check_admin_referer( 'wf_resto_today' );
	$id = (int) ( $_POST['id'] ?? 0 );
	if ( $id && 'wf_resa' === get_post_type( $id ) ) {
		update_post_meta( $id, '_wf_resa_privnote', sanitize_text_field( wp_unslash( $_POST['privnote'] ?? '' ) ) );
		set_transient( 'wf_resto_notice_' . get_current_user_id(), 'Note enregistrée.', 20 );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=wf-restaurant-today' ) );
	exit;
} );

/* AJAX : abonnement + test. */
add_action( 'wp_ajax_wf_resto_push_sub', function () {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { wp_send_json_error(); }
	check_ajax_referer( 'wf_resto_push', 'nonce' );
	$endpoint = isset( $_POST['endpoint'] ) ? esc_url_raw( wp_unslash( $_POST['endpoint'] ) ) : '';
	if ( ! $endpoint ) { wp_send_json_error( 'endpoint' ); }
	$subs = get_option( 'wf_resto_push_subs', array() );
	if ( ! is_array( $subs ) ) { $subs = array(); }
	$subs[ md5( $endpoint ) ] = array(
		'endpoint' => $endpoint,
		'p256dh'   => isset( $_POST['p256dh'] ) ? sanitize_text_field( wp_unslash( $_POST['p256dh'] ) ) : '',
		'auth'     => isset( $_POST['auth'] ) ? sanitize_text_field( wp_unslash( $_POST['auth'] ) ) : '',
		'user'     => get_current_user_id(),
	);
	update_option( 'wf_resto_push_subs', $subs, false );
	wp_send_json_success();
} );
add_action( 'wp_ajax_wf_resto_push_test', function () {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { wp_send_json_error(); }
	check_ajax_referer( 'wf_resto_push', 'nonce' );
	$n = wf_resto_push_send( 'Test 🔔', 'Les notifications fonctionnent.' );
	wp_send_json_success( (int) $n );
} );

/* Bouton « Activer les notifications » sur les pages de l'app. */
add_action( 'admin_footer', function () {
	if ( ! wf_resto_is_app_page() ) { return; }
	if ( ! current_user_can( WF_RESTO_CAP ) ) { return; }
	$v = wf_resto_vapid();
	if ( ! $v ) { return; }
	$nonce = wp_create_nonce( 'wf_resto_push' );
	?>
	<script>
	(function(){
		var KEY = <?php echo wp_json_encode( $v['public'] ); ?>;
		var NONCE = <?php echo wp_json_encode( $nonce ); ?>;
		var btn = document.getElementById('wf-push-btn');
		if (!btn) { return; }
		if (!('serviceWorker' in navigator)) { return; }
		if (!('PushManager' in window)) { return; }
		function b64(b){ var pad = '='.repeat((4 - b.length % 4) % 4); var s = (b + pad).replace(/-/g,'+').replace(/_/g,'/'); var raw = atob(s); var arr = new Uint8Array(raw.length); for (var i = 0; i < raw.length; i++){ arr[i] = raw.charCodeAt(i); } return arr; }
		navigator.serviceWorker.ready.then(function(reg){
			reg.pushManager.getSubscription().then(function(sub){
				btn.style.display = sub ? 'none' : '';
			});
		});
		btn.addEventListener('click', function(){
			if (typeof Notification === 'undefined') { alert('Notifications non supportées.'); return; }
			Notification.requestPermission().then(function(perm){
				if (perm !== 'granted') { alert('Autorisez les notifications dans le navigateur.'); return; }
				navigator.serviceWorker.ready.then(function(reg){
					reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64(KEY) }).then(function(sub){
						var j = sub.toJSON();
						var fd = new FormData();
						fd.append('action', 'wf_resto_push_sub');
						fd.append('nonce', NONCE);
						fd.append('endpoint', j.endpoint);
						fd.append('p256dh', j.keys.p256dh);
						fd.append('auth', j.keys.auth);
						fetch(window.ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function(r){ return r.json(); }).then(function(res){
							btn.style.display = 'none';
							var t = new FormData(); t.append('action', 'wf_resto_push_test'); t.append('nonce', NONCE);
							fetch(window.ajaxurl, { method: 'POST', body: t, credentials: 'same-origin' });
						});
					}).catch(function(){ alert('Abonnement échoué. Réessayez.'); });
				});
			});
		});
	})();
	</script>
	<?php
} );

/* ============================================================
 *  APP — Personnalisation (nom, couleur, icône, notif)
 * ========================================================== */

add_action( 'admin_menu', function () {
	add_submenu_page( 'wf-restaurant', "Personnaliser l'app", '<span class="dashicons dashicons-art wf-mi"></span> Personnaliser l&rsquo;app', WF_RESTO_CAP, 'wf-restaurant-app', 'wf_resto_app_render' );
}, 20 );

add_action( 'admin_enqueue_scripts', function () {
	if ( isset( $_GET['page'] ) && 'wf-restaurant-app' === $_GET['page'] ) {
		if ( function_exists( 'wp_enqueue_media' ) ) { wp_enqueue_media(); }
	}
} );

function wf_resto_app_render() {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { return; }
	if ( function_exists( 'wp_enqueue_media' ) ) { wp_enqueue_media(); }
	$a = wf_resto_app();
	$notice = get_transient( 'wf_resto_notice_' . get_current_user_id() );
	if ( false !== $notice ) { delete_transient( 'wf_resto_notice_' . get_current_user_id() ); }
	$post     = esc_url( admin_url( 'admin-post.php' ) );
	$icon_id  = (int) $a['icon_id'];
	$icon_src = $icon_id ? wp_get_attachment_image_url( $icon_id, 'thumbnail' ) : '';
	?>
	<div class="wrap" style="max-width:640px;">
		<h1>🎨 Personnaliser l'app</h1>
		<p class="description">Nom, couleur, icône (écran d'accueil) et texte des notifications de l'app « Mon restaurant ».</p>
		<?php if ( false !== $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
		<form method="post" action="<?php echo $post; ?>">
			<?php wp_nonce_field( 'wf_resto_app' ); ?>
			<input type="hidden" name="action" value="wf_resto_app_save">
			<table class="form-table" role="presentation">
				<tr><th>Nom de l'app</th><td><input type="text" name="app[name]" class="regular-text" value="<?php echo esc_attr( $a['name'] ); ?>" placeholder="Mon restaurant"></td></tr>
				<tr><th>Couleur du thème</th><td><input type="text" name="app[theme_color]" value="<?php echo esc_attr( $a['theme_color'] ); ?>" placeholder="#1f2937"> <span class="description">Barre, boutons, onglet actif.</span></td></tr>
				<tr><th>Icône de l'app</th><td>
					<div style="display:flex;align-items:center;gap:12px;">
						<img id="wf-app-icon-prev" src="<?php echo esc_url( $icon_src ); ?>" style="width:64px;height:64px;object-fit:cover;border-radius:14px;border:1px solid #dcdcde;<?php echo $icon_src ? '' : 'display:none'; ?>">
						<button type="button" class="button" id="wf-app-icon-pick">Choisir une image</button>
						<button type="button" class="button" id="wf-app-icon-clear"<?php echo $icon_src ? '' : ' style="display:none"'; ?>>Retirer</button>
					</div>
					<input type="hidden" id="wf-app-icon-id" name="app[icon_id]" value="<?php echo (int) $icon_id; ?>">
					<p class="description">Idéal : carré, 512×512. Vide = icône par défaut (assiette).</p>
				</td></tr>
				<tr><th>Notif — titre</th><td><input type="text" name="app[notif_title]" class="regular-text" value="<?php echo esc_attr( $a['notif_title'] ); ?>"> <span class="description">Titre de toutes les notifications.</span></td></tr>
				<tr><th>Notif réservation (détail)</th><td>
					<input type="text" name="app[notif_resa_tpl]" class="large-text" value="<?php echo esc_attr( $a['notif_resa_tpl'] ); ?>">
					<p class="description">Message affiché <strong>sans avoir à cliquer</strong>, à chaque réservation. Variables : <code>{prenom}</code> <code>{nom}</code> <code>{nom_complet}</code> <code>{couverts}</code> <code>{date}</code> <code>{heure}</code> <code>{service}</code></p>
				</td></tr>
				<tr><th>Notif — message par défaut</th><td><input type="text" name="app[notif_body]" class="large-text" value="<?php echo esc_attr( $a['notif_body'] ); ?>"> <span class="description">Utilisé pour les notifications génériques (test).</span></td></tr>
			</table>
			<?php submit_button( 'Enregistrer' ); ?>
		</form>
		<p class="description">Après un changement d'icône/nom : sur le téléphone, il peut falloir <strong>réinstaller</strong> l'app (retirer puis « Ajouter à l'écran d'accueil »). Le texte des notifications s'applique au prochain chargement de l'app.</p>
	</div>
	<script>
	jQuery(function($){
		var frame;
		$('#wf-app-icon-pick').on('click', function(e){
			e.preventDefault();
			if (frame) { frame.open(); return; }
			frame = wp.media({ title:"Icône de l'app", library:{ type:'image' }, multiple:false, button:{ text:'Utiliser' } });
			frame.on('select', function(){
				var a = frame.state().get('selection').first().toJSON();
				var url = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
				$('#wf-app-icon-id').val(a.id);
				$('#wf-app-icon-prev').attr('src', url).show();
				$('#wf-app-icon-clear').show();
			});
			frame.open();
		});
		$('#wf-app-icon-clear').on('click', function(e){ e.preventDefault(); $('#wf-app-icon-id').val(''); $('#wf-app-icon-prev').hide(); $(this).hide(); });
	});
	</script>
	<?php
}

add_action( 'admin_post_wf_resto_app_save', function () {
	if ( ! current_user_can( WF_RESTO_CAP ) ) { wp_die( 'Refusé.' ); }
	check_admin_referer( 'wf_resto_app' );
	$in  = isset( $_POST['app'] ) && is_array( $_POST['app'] ) ? wp_unslash( $_POST['app'] ) : array();
	$col = sanitize_hex_color( $in['theme_color'] ?? '' );
	$out = array(
		'name'           => sanitize_text_field( $in['name'] ?? 'Mon restaurant' ),
		'theme_color'    => $col ? $col : '#1f2937',
		'icon_id'        => (int) ( $in['icon_id'] ?? 0 ),
		'notif_title'    => sanitize_text_field( $in['notif_title'] ?? '' ),
		'notif_body'     => sanitize_text_field( $in['notif_body'] ?? '' ),
		'notif_resa_tpl' => sanitize_text_field( $in['notif_resa_tpl'] ?? '' ),
	);
	if ( '' === trim( $out['name'] ) ) { $out['name'] = 'Mon restaurant'; }
	if ( '' === trim( $out['notif_title'] ) ) { $out['notif_title'] = 'Nouvelle réservation 🍽'; }
	if ( '' === trim( $out['notif_body'] ) ) { $out['notif_body'] = "Une demande vient d'arriver. Ouvrez l'app."; }
	if ( '' === trim( $out['notif_resa_tpl'] ) ) { $out['notif_resa_tpl'] = 'Résa de {prenom} {nom} · {couverts} couv. le {date} à {heure}'; }
	update_option( 'wf_resto_app', $out );
	set_transient( 'wf_resto_notice_' . get_current_user_id(), "Personnalisation de l'app enregistrée.", 30 );
	wp_safe_redirect( admin_url( 'admin.php?page=wf-restaurant-app' ) );
	exit;
} );
