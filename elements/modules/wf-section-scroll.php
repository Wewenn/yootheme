<?php
/**
 * WeFrame – Défilement latéral dans les réglages de Section
 *
 * Ajoute un groupe de champs à l'élément « Section » natif de YOOtheme pour
 * que la section défile à l'horizontale au lieu de se parcourir vers le bas.
 *
 * ── Pourquoi cette technique et pas une autre ──────────────────────────────
 *
 * La tentation, c'est d'intercepter la molette (wheel + preventDefault) et de
 * piloter scrollLeft à la main. C'est le « scroll hijacking » : ça casse le
 * clavier, l'inertie du trackpad, la barre de défilement, la recherche dans la
 * page et le geste tactile. On ne le fait pas.
 *
 * La bonne façon est l'inverse : on ne touche jamais au défilement de la page.
 * On construit une zone verticale plus haute que l'écran, on y colle un hublot
 * (position:sticky) et on décale les panneaux à l'intérieur en fonction de la
 * progression de cette zone dans l'écran. Le défilement reste celui du
 * navigateur, avec son inertie, son clavier et sa barre.
 *
 * ── Deux couches, parce que Firefox ────────────────────────────────────────
 *
 * Les animations pilotées par le scroll (animation-timeline) restent derrière
 * un drapeau dans Firefox stable — ~84 % de support global. Une section qui
 * n'existerait qu'en mode épinglé serait donc cassée pour un visiteur sur six.
 * D'où deux couches :
 *
 *   1. Socle, partout, sans JavaScript : la section devient une vraie bande
 *      latérale (overflow-x + scroll-snap). Le visiteur la parcourt au doigt,
 *      au trackpad, aux flèches. Utilisable et accessible tel quel.
 *   2. Enrichissement, sous @supports et si le visiteur accepte les
 *      animations : la même bande est épinglée et pilotée par le défilement
 *      vertical de la page.
 *
 * Le mode « épinglé » dégrade donc en bande latérale, jamais en page cassée.
 *
 * ── Zéro balisage, et une profondeur de DOM inconnue ───────────────────────
 *
 * Comme wf-section-fx.php, on n'ajoute aucune balise : tout passe par le champ
 * CSS que la section possède déjà, que YOOtheme préfixe avec l'id du nœud
 * (« .el-element » désigne la section).
 *
 * Or la structure rendue varie : selon que le conteneur est actif ou non, la
 * grille est enfant direct de la section ou petit-enfant. Impossible à
 * deviner de façon fiable. La recette ci-dessous s'en affranchit en ne
 * nommant que des classes publiques UIkit, à n'importe quelle profondeur :
 *
 *   PARENT   .el-element:has(> .uk-grid), .el-element :has(> .uk-grid)
 *            → porte la hauteur de défilement (height) et la timeline
 *   HUBLOT   .el-element .uk-grid
 *            → collant, 100vh, overflow caché, une seule ligne
 *   PANNEAUX .el-element .uk-grid > *
 *            → un écran de large chacun, décalés ensemble
 *
 * Les panneaux se décalent eux-mêmes plutôt qu'un rail intermédiaire : ça
 * économise le niveau de DOM qui manquait.
 *
 * Aucun @keyframes ici — le préfixeur de YOOtheme massacrerait les from/to.
 * wf-hs-x et wf-hs-bar vivent dans assets/css/weframe-shared.css.
 *
 * Tout est enveloppé de gardes : si l'API de YOOtheme change, les champs
 * n'apparaissent pas et le site continue de fonctionner.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wf_shs_fields' ) ) :
/**
 * Champs ajoutés à la section.
 *
 * @return array<string, array<string, mixed>>
 */
function wf_shs_fields() {
	return array(
		'wf_hs' => array(
			'label'       => 'Défilement latéral',
			'type'        => 'select',
			'options'     => array(
				'Non'                                  => '',
				'Bande latérale (le visiteur fait défiler)' => 'strip',
				'Épinglée (le scroll de la page fait défiler)' => 'pin',
			),
			'description' => "Demande une seule ligne dans la section : ses colonnes deviennent les panneaux. « Épinglée » redevient une bande latérale sur les navigateurs qui ne savent pas encore piloter une animation au scroll, Firefox compris.",
		),
		'wf_hs_dir' => array(
			'label'   => 'Sens du défilement',
			'type'    => 'select',
			'options' => array(
				'Vers la gauche (on avance à droite)' => 'left',
				'Vers la droite (on part de la droite)' => 'right',
			),
			'enable'  => 'wf_hs',
		),
		'wf_hs_width' => array(
			'label'   => "Largeur d'un panneau",
			'type'    => 'select',
			'options' => array(
				'Plein écran'      => '100',
				'Trois quarts'     => '75',
				'Deux tiers'       => '66',
				'La moitié'        => '50',
				'Un tiers'         => '33',
				'Selon le contenu' => 'auto',
			),
			'enable'  => 'wf_hs',
			'description' => 'En mode épinglé, seul « Plein écran » garantit un panneau centré à chaque palier.',
		),
		'wf_hs_gap' => array(
			'label'  => 'Écart entre panneaux (px)',
			'type'   => 'number',
			'attrs'  => array( 'min' => 0, 'max' => 160, 'step' => 4 ),
			'source' => true,
			'enable' => 'wf_hs',
			'description' => "Remplace la gouttière de la ligne, neutralisée pour que les panneaux s'alignent au pixel.",
		),
		'wf_hs_len' => array(
			'label'  => 'Défilement par panneau (% de hauteur d\'écran)',
			'type'   => 'number',
			'attrs'  => array( 'min' => 40, 'max' => 300, 'step' => 10 ),
			'source' => true,
			'enable' => "wf_hs === 'pin'",
			'description' => '100 = un écran de défilement vertical pour passer au panneau suivant. Plus haut, la traversée est plus lente.',
		),
		'wf_hs_snap' => array(
			'label'  => 'Accroche',
			'type'   => 'checkbox',
			'text'   => 'Accrocher les panneaux',
			'enable' => 'wf_hs',
		),
		'wf_hs_from' => array(
			'label'   => 'À partir de',
			'type'    => 'select',
			'options' => array(
				'Tous les écrans'         => '0',
				'Tablette (≥ 640 px)'     => '640',
				'Bureau (≥ 960 px)'       => '960',
				'Grand écran (≥ 1200 px)' => '1200',
			),
			'enable'  => 'wf_hs',
			'description' => "En dessous, la section reprend son empilement vertical normal. Une bande latérale sur un téléphone se prend souvent mal.",
		),
		'wf_hs_bar' => array(
			'label'  => 'Progression',
			'type'   => 'checkbox',
			'text'   => 'Afficher une barre de progression',
			'enable' => "wf_hs === 'pin'",
			'description' => "Inutile en bande latérale : la barre de défilement du navigateur dit déjà où l'on en est.",
		),
		'wf_hs_bar_color' => array(
			'label'  => 'Couleur de la barre',
			'type'   => 'color',
			'enable' => "wf_hs === 'pin' && wf_hs_bar",
		),
	);
}
endif;

if ( ! function_exists( 'wf_shs_defaults' ) ) :
function wf_shs_defaults() {
	return array(
		'wf_hs'           => '',
		'wf_hs_dir'       => 'left',
		'wf_hs_width'     => '100',
		'wf_hs_gap'       => 0,
		'wf_hs_len'       => 100,
		'wf_hs_snap'      => true,
		'wf_hs_from'      => '960',
		'wf_hs_bar'       => false,
		'wf_hs_bar_color' => '#EA5C1C',
	);
}
endif;

if ( ! function_exists( 'wf_shs_count_panels' ) ) :
/**
 * Nombre de panneaux = colonnes de la première ligne de la section.
 *
 * On le lit dans l'arbre du builder plutôt que de le demander à l'utilisateur :
 * un chiffre saisi à la main se désynchronise dès qu'on ajoute une colonne, et
 * c'est lui qui fixe la hauteur de défilement.
 *
 * @param object $node Nœud « section ».
 * @return int Au moins 1.
 */
function wf_shs_count_panels( $node ) {
	$rows = $node->children ?? null;
	if ( ! is_array( $rows ) ) { return 1; }
	foreach ( $rows as $row ) {
		if ( ! is_object( $row ) ) { continue; }
		$cols = $row->children ?? null;
		if ( is_array( $cols ) && count( $cols ) > 0 ) { return count( $cols ); }
	}
	return 1;
}
endif;

if ( ! function_exists( 'wf_shs_css' ) ) :
/**
 * Construit le CSS d'une section à partir de ses props.
 * Retourne '' si rien n'est demandé : dans ce cas on ne touche à rien.
 *
 * @param array $p Props du nœud.
 * @param int   $n Nombre de panneaux.
 * @return string
 */
function wf_shs_css( array $p, $n = 1 ) {
	$mode = (string) ( $p['wf_hs'] ?? '' );
	if ( 'strip' !== $mode && 'pin' !== $mode ) { return ''; }

	$n = max( 1, (int) $n );
	// Un seul panneau : rien à faire défiler, et une section épinglée de 0vh
	// serait juste une section cassée.
	if ( $n < 2 ) { return ''; }

	$dir   = ( 'right' === ( $p['wf_hs_dir'] ?? 'left' ) ) ? 'right' : 'left';
	$width = (string) ( $p['wf_hs_width'] ?? '100' );
	if ( ! in_array( $width, array( '100', '75', '66', '50', '33', 'auto' ), true ) ) { $width = '100'; }
	$gap   = max( 0, min( 160, (int) ( $p['wf_hs_gap'] ?? 0 ) ) );
	$len   = max( 40, min( 300, (int) ( $p['wf_hs_len'] ?? 100 ) ) );
	$snap  = ! empty( $p['wf_hs_snap'] );
	$bar   = ! empty( $p['wf_hs_bar'] );
	$barc  = (string) ( $p['wf_hs_bar_color'] ?? '#EA5C1C' );

	$bp = (int) ( $p['wf_hs_from'] ?? 960 );
	if ( ! in_array( $bp, array( 0, 640, 960, 1200 ), true ) ) { $bp = 960; }
	$mq = $bp > 0 ? '@media (min-width:' . $bp . 'px)' : '@media screen';

	// Les trois cibles. « PARENT » couvre les deux structures possibles : la
	// grille enfant direct de la section, ou nichée dans un conteneur.
	$parent = '.el-element:has(> .uk-grid),.el-element :has(> .uk-grid)';
	$grid   = '.el-element .uk-grid';
	$panel  = '.el-element .uk-grid > *';

	// Largeur d'un panneau. Les gouttières UIkit sont neutralisées et
	// remplacées par un gap : sans ça, la marge négative de .uk-grid décale
	// l'accroche d'une gouttière et les panneaux ne tombent jamais juste.
	$flex = 'auto' === $width
		? 'flex:0 0 auto;'
		: 'flex:0 0 calc(' . $width . '% - ' . $gap . 'px * ' . ( 100 === (int) $width ? '0' : '1' ) . ');';
	if ( '100' === $width ) { $flex = 'flex:0 0 100%;'; }

	$css = '';

	/* ── 1. Socle : une vraie bande latérale, partout, sans JavaScript ── */
	$css .= $mq . '{'
		. $grid . '{'
			. 'flex-wrap:nowrap;'
			. 'margin-left:0;'
			. 'overflow-x:auto;'
			. 'overscroll-behavior-x:contain;'
			. 'scrollbar-width:thin;'
			. ( $gap > 0 ? 'gap:' . $gap . 'px;' : '' )
			. ( $snap ? 'scroll-snap-type:x mandatory;' : '' )
			// Le hublot part de la droite sans inverser l'ordre des panneaux :
			// direction:rtl sur le conteneur, ltr sur les enfants.
			. ( 'right' === $dir ? 'direction:rtl;' : '' )
		. '}'
		. $panel . '{'
			. $flex
			. 'max-width:none;'
			. 'padding-left:0;'
			. 'box-sizing:border-box;'
			. ( $snap ? 'scroll-snap-align:start;' : '' )
			. ( 'right' === $dir ? 'direction:ltr;' : '' )
		. '}'
		. '}';

	/* ── 2. Enrichissement : épinglage piloté par le défilement vertical ── */
	if ( 'pin' === $mode ) {
		$travel = -100 * ( $n - 1 );          // en % de la largeur d'un panneau
		$scroll = ( $n - 1 ) * $len;          // en vh, la durée de l'épinglage

		$css .= '@supports (animation-timeline:view()){'
			. $mq . '{'
			. '@media (prefers-reduced-motion:no-preference){'

			// La zone qui porte la hauteur de défilement et la timeline.
			//
			// height et pas padding-bottom : mesuré dans Chromium, le rectangle
			// qui borne un élément collant est la boîte de CONTENU de son
			// parent, pas sa boîte de padding. Avec padding-bottom, la grille
			// ne colle pas du tout — elle défile comme un bloc normal. Avec une
			// hauteur réelle, elle colle. C'est le piège central de cette
			// recette, et il ne se voit qu'à l'exécution.
			. $parent . '{'
				. 'height:calc(100vh + ' . $scroll . 'vh);'
				. 'padding-top:0;'
				. 'padding-bottom:0;'
				. 'view-timeline-name:--wf-hs;'
				. 'view-timeline-axis:block;'
			. '}'

			// Le hublot : collant, plein écran, plus de défilement propre —
			// c'est le scroll de la page qui commande maintenant.
			. $grid . '{'
				. 'position:sticky;'
				. 'top:0;'
				. 'height:100vh;'
				. 'align-items:center;'
				. 'overflow:hidden;'
				. 'scroll-snap-type:none;'
			. '}'

			// Les panneaux se décalent ensemble. « contain » sur un sujet plus
			// haut que l'écran, c'est exactement la durée pendant laquelle il
			// le recouvre : la durée de l'épinglage.
			. $panel . '{'
				. '--wf-hs-end:' . $travel . '%;'
				. 'flex:0 0 100%;'
				. 'scroll-snap-align:none;'
				. 'animation:wf-hs-x linear both;'
				. 'animation-timeline:--wf-hs;'
				. 'animation-range:contain 0% contain 100%;'
				. ( 'right' === $dir ? 'animation-direction:reverse;' : '' )
			. '}'

			. '}}}';
	}

	/* ── 3. Barre de progression (mode épinglé seulement) ──
	 *
	 * En bande latérale, la barre de défilement du navigateur dit déjà où l'on
	 * en est ; on ne la double pas. La barre est posée sur le hublot, qui est
	 * déjà positionné (sticky) : elle reste donc en haut de l'écran pendant
	 * toute la traversée, ce qu'un ::after collant placé après la grille ne
	 * ferait pas — il commencerait un écran plus bas.
	 */
	if ( $bar && 'pin' === $mode ) {
		$css .= '@supports (animation-timeline:view()){'
			. $mq . '{'
			. '@media (prefers-reduced-motion:no-preference){'
			. $grid . '::after{'
				. 'content:"";'
				. 'position:absolute;'
				. 'top:0;left:0;right:0;'
				. 'height:3px;'
				. 'background:' . $barc . ';'
				. 'transform-origin:' . ( 'right' === $dir ? 'right' : 'left' ) . ' center;'
				. 'transform:scaleX(0);'
				. 'z-index:2;'
				. 'pointer-events:none;'
				. 'animation:wf-hs-bar linear both;'
				. 'animation-timeline:--wf-hs;'
				. 'animation-range:contain 0% contain 100%;'
				. ( 'right' === $dir ? 'animation-direction:reverse;' : '' )
			. '}'
			. '}}}';
	}

	return $css;
}
endif;

/**
 * Branchement : champs dans le panneau + CSS au rendu.
 *
 * @param object $builder Instance YOOtheme\Builder.
 */
if ( ! function_exists( 'wf_shs_boot' ) ) :
function wf_shs_boot( $builder ) {
	if ( ! is_object( $builder ) ) { return; }
	if ( ! method_exists( $builder, 'getType' ) ) { return; }
	if ( ! method_exists( $builder, 'addTransform' ) ) { return; }

	try {
		$section = $builder->getType( 'section' );
	} catch ( \Throwable $e ) {
		return;
	}
	if ( ! is_object( $section ) ) { return; }
	if ( ! isset( $section->fields ) ) { return; }
	if ( ! is_array( $section->fields ) ) { return; }

	// 1. Champs
	foreach ( wf_shs_fields() as $key => $def ) {
		if ( ! isset( $section->fields[ $key ] ) ) {
			$section->fields[ $key ] = $def;
		}
	}
	if ( isset( $section->defaults ) && is_array( $section->defaults ) ) {
		$section->defaults += wf_shs_defaults();
	}

	// 2. Un groupe dans l'onglet Paramètres de la section
	if ( isset( $section->fieldset['default']['fields'] ) && is_array( $section->fieldset['default']['fields'] ) ) {
		foreach ( $section->fieldset['default']['fields'] as &$tab ) {
			if ( ! is_array( $tab ) ) { continue; }
			$title = $tab['title'] ?? '';
			if ( 'Settings' !== $title && 'Paramètres' !== $title && 'Parametres' !== $title ) { continue; }
			if ( ! isset( $tab['fields'] ) || ! is_array( $tab['fields'] ) ) { continue; }
			// on évite le doublon si le module est chargé deux fois
			$flat = wp_json_encode( $tab['fields'] );
			if ( is_string( $flat ) && false !== strpos( $flat, 'wf_hs' ) ) { break; }
			$tab['fields'][] = array(
				'label'   => 'Défilement latéral',
				'type'    => 'group',
				'divider' => true,
				'fields'  => array( 'wf_hs', 'wf_hs_dir', 'wf_hs_width', 'wf_hs_gap', 'wf_hs_len', 'wf_hs_snap', 'wf_hs_from', 'wf_hs_bar', 'wf_hs_bar_color' ),
			);
			break;
		}
		unset( $tab );
	}

	// 3. Rendu : on ajoute notre CSS à celui de la section, avant que YOOtheme
	//    ne le préfixe et l'imprime. Le 0 place ce transform en tête de liste.
	$builder->addTransform(
		'prerender',
		function ( $node, $params ) {
			if ( ! is_object( $node ) ) { return; }
			if ( ( $node->type ?? '' ) !== 'section' ) { return; }
			$props = (array) ( $node->props ?? array() );
			$css   = wf_shs_css( $props, wf_shs_count_panels( $node ) );
			if ( '' === $css ) { return; }
			$node->props['css'] = trim( $css . ' ' . (string) ( $props['css'] ?? '' ) );
		},
		0
	);
}
endif;
