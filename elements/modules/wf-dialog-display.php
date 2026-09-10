<?php
/**
 * WeFrame – Affichage du dialog
 *
 * Donne au dialog de l'en-tête ce que YOOtheme ne propose pas : une taille.
 * Plein écran, trois quarts, moitié, un tiers, un quart, ou une dimension
 * libre — avec une valeur distincte sur mobile.
 *
 * ── Pourquoi ça n'existe pas déjà ────────────────────────────────────────
 *
 * Le dialog natif se règle avec : le mode (slide/reveal/push), « à droite »,
 * « superposer », et pour le modal cinq largeurs de contenu UIkit. Aucune
 * taille du panneau lui-même. La largeur d'un offcanvas est fixée par le CSS
 * du thème (270 px, 350 px au-dessus de 640 px) et n'est exposée nulle part.
 *
 * ── Zéro JavaScript, et l'aperçu suit ───────────────────────────────────
 *
 * Les champs sont des champs de config ordinaires : le customizer les rend
 * lui-même et écrit dans Config.values, que « set_theme_mod('config') »
 * enregistre. Le CSS est émis dans wp_head à partir de get_theme_mod('config').
 *
 * Et ce get_theme_mod renvoie la config EN COURS D'ÉDITION pendant l'aperçu :
 * packages/theme-wordpress/bootstrap.php branche LoadCustomizerSession sur le
 * filtre « theme_mod_config », qui applique les changements du customizer avant
 * de rendre la valeur. Le réglage se voit donc en direct, sans une ligne de
 * script et sans dupliquer la logique côté navigateur.
 *
 * ── Le piège, relevé dans le CSS du thème ────────────────────────────────
 *
 * UIkit place le panneau FERMÉ à un décalage négatif égal à sa largeur :
 *
 *   .uk-offcanvas-bar               { left: -270px; width: 270px }
 *   @media (min-width:640px)        { left: -350px; width: 350px }
 *   .uk-offcanvas-flip …            { left: auto; right: -350px }   (classe sur <body>)
 *   .uk-open > .uk-offcanvas-bar    { left: 0 }
 *   .uk-open > .uk-offcanvas-reveal { width: 350px }
 *   …container-animation            { left: ±350px }   (mode « push »)
 *
 * Changer la largeur sans toucher au reste laisse le panneau dépasser à
 * l'état fermé. Et poser « left » sur un sélecteur d'id l'emporterait sur
 * « .uk-open > .uk-offcanvas-bar » — le panneau ne s'ouvrirait plus du tout.
 * Il faut donc réécrire les quatre familles de règles, pas seulement la
 * largeur.
 *
 * ── Unités ───────────────────────────────────────────────────────────────
 *
 * Les fractions sont exprimées en vw (ou vh pour un dropbar qui descend), pas
 * en %. Le conteneur d'un offcanvas n'a pas de largeur propre — il est en
 * position fixe avec left:0 et aucune right — donc un pourcentage n'aurait
 * rien contre quoi se calculer.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wf_dlg_tailles' ) ) :
/**
 * Les tailles proposées, et la fraction qu'elles valent.
 *
 * @return array<string, string>
 */
function wf_dlg_tailles() {
	return array(
		''       => '',   // par défaut du thème : on n'émet rien
		'100'    => '100',
		'75'     => '75',
		'66'     => '66',
		'50'     => '50',
		'33'     => '33',
		'25'     => '25',
		'custom' => 'custom',
	);
}
endif;

if ( ! function_exists( 'wf_dlg_longueur' ) ) :
/**
 * Traduit un réglage en longueur CSS.
 *
 * Rien n'entre dans la feuille de style sans passer par ici : une valeur libre
 * qui ne ressemble pas à une longueur est refusée, pas échappée.
 *
 * @param string $taille Valeur du select.
 * @param string $libre  Valeur du champ « dimension personnalisée ».
 * @param string $axe    'w' pour une largeur, 'h' pour une hauteur.
 * @return string Longueur CSS, ou '' si rien à émettre.
 */
function wf_dlg_longueur( $taille, $libre, $axe = 'w' ) {
	$taille = (string) $taille;

	if ( 'custom' === $taille ) {
		$libre = trim( (string) $libre );
		// Un nombre nu vaut des pixels : « 420 » est une saisie naturelle.
		if ( '' !== $libre && preg_match( '/^\d+(\.\d+)?$/', $libre ) ) {
			return $libre . 'px';
		}
		return preg_match( '/^\d+(\.\d+)?(px|em|rem|%|vw|vh|vmin|vmax)$/', $libre ) ? $libre : '';
	}

	if ( ! isset( wf_dlg_tailles()[ $taille ] ) || '' === $taille ) { return ''; }

	return $taille . ( 'h' === $axe ? 'vh' : 'vw' );
}
endif;

if ( ! function_exists( 'wf_dlg_css' ) ) :
/**
 * Le CSS du dialog, déduit de la configuration du thème.
 *
 * @param array $config Configuration du thème (theme_mod « config » décodé).
 * @return string Feuille de style, ou '' si rien n'est réglé.
 */
function wf_dlg_css( array $config ) {
	$wf = $config['wf_dialog'] ?? array();
	if ( ! is_array( $wf ) ) { return ''; }

	$layout = (string) ( $config['dialog']['layout'] ?? 'offcanvas-top' );
	$forme  = 'offcanvas';
	if ( 0 === strpos( $layout, 'dropbar' ) ) { $forme = 'dropbar'; }
	if ( 0 === strpos( $layout, 'modal' ) ) { $forme = 'modal'; }

	// Un dropbar qui descend se règle en hauteur ; celui qui vient du côté,
	// en largeur. C'est sa géométrie qui décide, pas un réglage de plus.
	$anim = (string) ( $config['dialog']['dropbar']['animation'] ?? '' );
	$axe  = ( 'dropbar' === $forme && 0 !== strpos( $anim, 'slide-' ) ) ? 'h' : 'w';

	$grand = wf_dlg_longueur( $wf['size'] ?? '', $wf['custom'] ?? '', $axe );
	$petit = wf_dlg_longueur( $wf['mobile'] ?? '', $wf['custom_mobile'] ?? '', $axe );

	if ( '' === $grand && '' === $petit ) { return ''; }

	$seuil = (int) ( $wf['breakpoint'] ?? 640 );
	if ( ! in_array( $seuil, array( 640, 960, 1200 ), true ) ) { $seuil = 640; }

	$max = trim( (string) ( $wf['max'] ?? '' ) );
	if ( '' !== $max && ! preg_match( '/^\d+(\.\d+)?(px|em|rem|%|vw|vh)$/', $max ) ) { $max = ''; }

	$mode = (string) ( $config['dialog']['offcanvas']['mode'] ?? 'slide' );

	$regles = function ( $t ) use ( $forme, $axe, $max, $mode ) {
		if ( '' === $t ) { return ''; }
		$neg = 'calc(-1 * ' . $t . ')';
		$css = '';

		if ( 'offcanvas' === $forme ) {
			// 1. la largeur du panneau, et son décalage à l'état fermé.
			//
			//    Le plafond porte un !important, et c'est le seul du fichier :
			//    à l'ouverture, UIkit pose un max-width EN LIGNE égal à la
			//    largeur de la fenêtre (uikit.min.js : c(this.panel,
			//    "maxWidth", e.clientWidth)). Un style en ligne bat n'importe
			//    quelle règle de feuille — sans !important, « Ne pas dépasser »
			//    serait ignoré à chaque ouverture.
			$css .= '#tm-dialog .uk-offcanvas-bar,#tm-dialog-mobile .uk-offcanvas-bar{'
				. 'width:' . $t . ';left:' . $neg . ';'
				. ( '' !== $max ? 'max-width:' . $max . ' !important;' : '' )
				. '}';
			// 2. la même chose côté droit.
			//
			//    « uk-offcanvas-flip » n'est PAS sur le dialog : UIkit la pose
			//    sur <body> à l'ouverture (I(body, clsContainer, clsFlip)).
			//    Un sélecteur #tm-dialog.uk-offcanvas-flip ne matcherait jamais.
			$css .= '.uk-offcanvas-flip #tm-dialog .uk-offcanvas-bar,.uk-offcanvas-flip #tm-dialog-mobile .uk-offcanvas-bar{'
				. 'left:auto;right:' . $neg . ';}';
			// 3. l'état ouvert, que notre sélecteur d'id écraserait sinon
			$css .= '#tm-dialog.uk-open>.uk-offcanvas-bar,#tm-dialog-mobile.uk-open>.uk-offcanvas-bar{left:0;}';
			$css .= '.uk-offcanvas-flip #tm-dialog.uk-open>.uk-offcanvas-bar,.uk-offcanvas-flip #tm-dialog-mobile.uk-open>.uk-offcanvas-bar{left:auto;right:0;}';
			// 4. le mode « reveal » anime la largeur d'une enveloppe
			$css .= '#tm-dialog.uk-open>.uk-offcanvas-reveal,#tm-dialog-mobile.uk-open>.uk-offcanvas-reveal{width:' . $t . ';}';
			$css .= '#tm-dialog .uk-offcanvas-reveal .uk-offcanvas-bar,#tm-dialog-mobile .uk-offcanvas-reveal .uk-offcanvas-bar{left:0;}';
			$css .= '.uk-offcanvas-flip #tm-dialog .uk-offcanvas-reveal .uk-offcanvas-bar,.uk-offcanvas-flip #tm-dialog-mobile .uk-offcanvas-reveal .uk-offcanvas-bar{left:auto;right:0;}';
			// 5. le mode « push » décale la page entière : cette règle-là ne
			//    peut pas être limitée à notre dialog, UIkit la pose sur le
			//    conteneur du site. On ne l'émet donc qu'en mode push.
			if ( 'push' === $mode ) {
				$css .= ':not(.uk-offcanvas-flip).uk-offcanvas-container-animation{left:' . $t . ';}';
				$css .= '.uk-offcanvas-flip.uk-offcanvas-container-animation{left:' . $neg . ';}';
			}
		} elseif ( 'modal' === $forme ) {
			// Le corps du modal porte les classes uk-width-* : notre sélecteur
			// d'id les remplace, d'où un seul réglage de taille au lieu de deux.
			$css .= '#tm-dialog .uk-modal-body,#tm-dialog-mobile .uk-modal-body{'
				. 'width:' . $t . ';'
				. ( '' !== $max ? 'max-width:' . $max . ';' : 'max-width:100%;' )
				. '}';
		} else {
			$prop = ( 'h' === $axe ) ? 'height' : 'width';
			$css .= '#tm-dialog.uk-dropbar,#tm-dialog-mobile.uk-dropbar{'
				. $prop . ':' . $t . ';'
				. ( '' !== $max ? 'max-' . $prop . ':' . $max . ';' : '' )
				. '}';
		}

		return $css;
	};

	$css = '';

	// Le grand écran d'abord, la valeur mobile ensuite : la seconde doit
	// pouvoir écraser la première, donc elle vient après.
	if ( '' !== $grand ) { $css .= $regles( $grand ); }
	if ( '' !== $petit ) {
		$css .= '@media (max-width:' . ( $seuil - 1 ) . 'px){' . $regles( $petit ) . '}';
	}

	return $css;
}
endif;

if ( ! function_exists( 'wf_dlg_head' ) ) :
/**
 * Émet la feuille de style. Rien n'est imprimé si rien n'est réglé.
 */
function wf_dlg_head() {
	$brut   = get_theme_mod( 'config' );
	$config = is_string( $brut ) ? json_decode( $brut, true ) : array();
	if ( ! is_array( $config ) ) { return; }

	$css = wf_dlg_css( $config );
	if ( '' === $css ) { return; }

	echo "\n<style id=\"wf-dialog-display\">" . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- longueurs validées par wf_dlg_longueur().
}
endif;

add_action( 'wp_head', 'wf_dlg_head', 30 );

if ( ! function_exists( 'wf_dlg_customizer' ) ) :
/**
 * Le panneau, et le bouton qui y mène depuis « En-tête & Dialog ».
 *
 * Tout est en champs de config : le customizer les rend, les évalue (« show »)
 * et les enregistre lui-même. La forme et le mode sont les champs NATIFS —
 * mêmes chemins, mêmes valeurs — pour qu'un réglage fait ici soit exactement
 * celui du panneau d'origine, et réciproquement.
 *
 * @return array<string, mixed>
 */
function wf_dlg_customizer() {
	$offcanvas = "\$match(dialog.layout, '^offcanvas')";
	$modal     = "\$match(dialog.layout, '^modal')";
	$dropbar   = "\$match(dialog.layout, '^dropbar')";

	$tailles = array(
		'Par défaut du thème' => '',
		'Plein écran'         => '100',
		'Trois quarts'        => '75',
		'Deux tiers'          => '66',
		'La moitié'           => '50',
		'Un tiers'            => '33',
		'Un quart'            => '25',
		'Personnalisée'       => 'custom',
	);

	return array(
		'panels' => array(
			'wf-dialog-affichage' => array(
				'title'  => 'Affichage du dialog',
				'width'  => 400,
				'fields' => array(

					// ── La forme : le champ natif, à l'identique ──────────
					'dialog.layout' => array(
						'label'       => 'Forme',
						'description' => 'Le dialog n\'apparaît que si un widget est posé dans la zone « Dialog ».',
						'title'       => 'Select dialog layout',
						'type'        => 'select-img',
						'options'     => array(
							'dropbar-top'     => array( 'label' => 'Dropbar Top', 'src' => '$ASSETS/images/dialog/dropbar-top.svg' ),
							'dropbar-center'  => array( 'label' => 'Dropbar Center', 'src' => '$ASSETS/images/dialog/dropbar-center.svg' ),
							'offcanvas-top'   => array( 'label' => 'Offcanvas Top', 'src' => '$ASSETS/images/dialog/offcanvas-top.svg' ),
							'offcanvas-center' => array( 'label' => 'Offcanvas Center', 'src' => '$ASSETS/images/dialog/offcanvas-center.svg' ),
							'modal-top'       => array( 'label' => 'Modal Top', 'src' => '$ASSETS/images/dialog/modal-top.svg' ),
							'modal-center'    => array( 'label' => 'Modal Center', 'src' => '$ASSETS/images/dialog/modal-center.svg' ),
						),
					),

					// ── La taille : ce qui n'existait pas ────────────────
					'wf_dialog.size' => array(
						'label'       => 'Taille',
						'description' => "Fraction de l'écran. « Par défaut du thème » laisse la largeur d'origine (270 px, 350 px au-delà de 640 px).",
						'type'        => 'select',
						'options'     => $tailles,
					),
					'wf_dialog.custom' => array(
						'label'       => 'Dimension',
						'description' => 'Par exemple <code>420px</code>, <code>60vw</code> ou <code>32rem</code>. Un nombre seul vaut des pixels.',
						'attrs'       => array( 'placeholder' => '420px' ),
						'show'        => "wf_dialog.size == 'custom'",
					),
					'wf_dialog.max' => array(
						'label'       => 'Ne pas dépasser',
						'description' => 'Plafond facultatif, utile avec une fraction : la moitié d\'un très grand écran fait vite trop.',
						'attrs'       => array( 'placeholder' => '520px' ),
						'show'        => 'wf_dialog.size',
					),

					// ── La taille sur petit écran ────────────────────────
					'wf_dialog.mobile' => array(
						'label'       => 'Taille sur petit écran',
						'description' => 'Laissé vide, la taille ci-dessus s\'applique partout.',
						'type'        => 'select',
						'options'     => $tailles,
						'show'        => 'wf_dialog.size',
					),
					'wf_dialog.custom_mobile' => array(
						'label' => 'Dimension sur petit écran',
						'attrs' => array( 'placeholder' => '100vw' ),
						'show'  => "wf_dialog.mobile == 'custom'",
					),
					'wf_dialog.breakpoint' => array(
						'label'   => 'Petit écran, c\'est en dessous de',
						'type'    => 'select',
						'options' => array(
							'640 px (mobile)'   => '640',
							'960 px (tablette)' => '960',
							'1200 px (bureau)'  => '1200',
						),
						'show'    => 'wf_dialog.mobile',
					),

					// ── Le comportement : champs natifs, selon la forme ──
					'_wf_dlg_sep' => array(
						'type'        => 'description',
						'description' => 'Les réglages ci-dessous sont ceux de YOOtheme : les modifier ici revient exactement au même que dans le panneau d\'origine.',
					),
					'dialog.offcanvas.mode' => array(
						'label'   => 'Animation',
						'type'    => 'select',
						'options' => array( 'Slide' => 'slide', 'Reveal' => 'reveal', 'Push' => 'push' ),
						'show'    => $offcanvas,
					),
					'dialog.offcanvas.flip' => array(
						'label' => 'Côté',
						'type'  => 'checkbox',
						'text'  => 'Ouvrir à droite',
						'show'  => $offcanvas,
					),
					'dialog.offcanvas.overlay' => array(
						'type' => 'checkbox',
						'text' => 'Superposer au site',
						'show' => $offcanvas,
					),
					'dialog.dropbar.animation' => array(
						'label'       => 'Animation',
						'description' => 'Un dropbar qui descend se règle en hauteur ; venant du côté, en largeur.',
						'type'        => 'select',
						'options'     => array(
							'Reveal Top'  => 'reveal-top',
							'Slide Left'  => 'slide-left',
							'Slide Right' => 'slide-right',
						),
						'show'        => $dropbar,
					),
					'_wf_dlg_modal' => array(
						'type'        => 'description',
						'description' => 'En modal, la taille ci-dessus remplace la « largeur de contenu » du panneau natif.',
						'show'        => $modal,
					),
				),
			),
		),
	);
}
endif;

if ( ! function_exists( 'wf_dlg_bouton' ) ) :
/**
 * Le bouton qui mène au panneau, à poser dans « En-tête & Dialog ».
 *
 * @return array<string, mixed>
 */
function wf_dlg_bouton() {
	return array(
		'type'  => 'button-panel',
		'text'  => 'Affichage du dialog',
		'panel' => 'wf-dialog-affichage',
	);
}
endif;

if ( ! class_exists( 'WF_Dialog_Display_Listener' ) ) :
/**
 * Écouteur de « customizer.init » : le panneau, et rien d'autre. Aucun script.
 */
class WF_Dialog_Display_Listener {

	public \YOOtheme\Config $config;

	public function __construct( \YOOtheme\Config $config ) {
		$this->config = $config;
	}

	public function handle(): void {
		$this->config->add( 'customizer', wf_dlg_customizer() );
	}
}
endif;
