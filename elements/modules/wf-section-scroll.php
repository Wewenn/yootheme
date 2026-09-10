<?php
/**
 * WeFrame – Défilement latéral dans les réglages de Section
 *
 * Ajoute un groupe de champs à l'élément « Section » natif de YOOtheme pour
 * qu'une section se parcoure à l'horizontale au lieu de descendre.
 *
 * ── Pourquoi ce n'est pas du détournement de molette ──────────────────────
 *
 * La tentation, c'est d'intercepter la molette (wheel + preventDefault) et de
 * piloter scrollLeft à la main. C'est le « scroll hijacking » : ça casse le
 * clavier, l'inertie du trackpad, la barre de défilement, la recherche dans la
 * page et le geste tactile. On ne le fait pas.
 *
 * Ici on ne touche jamais au défilement de la page. La section devient une zone
 * plus haute que l'écran, on y colle un hublot (position:sticky) et on décale
 * le rail à l'intérieur selon la position de cette zone dans l'écran. Le
 * défilement reste celui du navigateur, avec son inertie et son clavier.
 *
 * ── Pourquoi un script, et non du CSS seul ────────────────────────────────
 *
 * La v1 faisait tout en CSS avec animation-timeline. Deux problèmes de fond :
 *
 * 1. Elle devait DEVINER la structure rendue par YOOtheme (grille enfant
 *    direct de la section, ou nichée dans un ou deux conteneurs). Le CSS ne
 *    sait pas mesurer : chaque sélecteur était un pari. Un pari perdu = une
 *    section qui ne fait rien, sans le moindre message.
 * 2. animation-timeline reste derrière un drapeau dans Firefox stable
 *    (~84 % de support), donc l'épinglage n'existait pas pour un visiteur
 *    sur six.
 *
 * Un script, lui, MESURE. Il trouve le rail en descendant dans le vrai DOM,
 * compte les panneaux, lit les largeurs réelles, fabrique le hublot manquant
 * s'il n'existe pas, et dit dans la console ce qu'il n'a pas trouvé. Plus de
 * pari, et ça marche dans Firefox.
 *
 * Le socle reste en CSS pur : sans JavaScript, la section est une vraie bande
 * latérale (overflow-x + scroll-snap) qu'on parcourt au doigt, au trackpad et
 * aux flèches. Le mode épinglé est un enrichissement par-dessus, jamais un
 * prérequis.
 *
 * ── Le mouvement ─────────────────────────────────────────────────────────
 *
 * Le réglage « Souplesse » interpole la position vers sa cible au lieu de la
 * suivre au pixel. C'est ce qui sépare un défilement latéral fait à la main
 * d'un site de studio : à 0 le rail collé au scroll, à 100 il glisse encore un
 * instant après l'arrêt. Le défilement de la page n'est jamais retardé pour
 * autant — seul le rail est amorti.
 *
 * ── Côté serveur : une classe, et c'est tout ─────────────────────────────
 *
 * On n'ajoute aucune balise au rendu. La section reçoit la classe « wf-hs »
 * via son champ « class » natif, et ses valeurs par des variables CSS posées
 * dans son champ CSS (que YOOtheme préfixe avec l'id du nœud). Le script lit
 * ces variables : aucune donnée en dur dans le HTML, et une section sans
 * script reste une section valide.
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
				'Non' => '',
				'Bande latérale (le visiteur fait défiler)' => 'strip',
				'Épinglée (le scroll de la page fait défiler)' => 'pin',
			),
			'description' => "Demande une seule ligne dans la section : ses colonnes deviennent les panneaux. Sans JavaScript, « Épinglée » redevient une bande latérale.",
		),
		'wf_hs_dir' => array(
			'label'   => 'Sens du défilement',
			'type'    => 'select',
			'options' => array(
				'Vers la gauche (on avance à droite)'   => 'left',
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
			'label'  => 'Longueur de la traversée (%)',
			'type'   => 'number',
			'attrs'  => array( 'min' => 50, 'max' => 400, 'step' => 10 ),
			'source' => true,
			'enable' => "wf_hs === 'pin'",
			'description' => "Rapport entre le défilement vertical demandé et la largeur à parcourir. 100 = un pixel vers le bas pour un pixel vers la gauche. Plus haut, la traversée est plus lente.",
		),
		'wf_hs_ease' => array(
			'label'  => 'Souplesse du mouvement',
			'type'   => 'number',
			'attrs'  => array( 'min' => 0, 'max' => 100, 'step' => 5 ),
			'source' => true,
			'enable' => "wf_hs === 'pin'",
			'description' => "0 : le rail suit le scroll au pixel. 100 : il glisse encore un instant après l'arrêt. C'est ce réglage qui donne le rendu « studio ». Ramené à 0 si le visiteur demande moins d'animations.",
		),
		'wf_hs_snap' => array(
			'label'  => 'Accroche',
			'type'   => 'checkbox',
			'text'   => 'Accrocher les panneaux',
			'enable' => "wf_hs === 'strip'",
			'description' => "En mode épinglé, c'est la longueur de la traversée qui règle le rythme.",
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
			'description' => "En dessous, la section reprend son empilement vertical normal.",
		),
		'wf_hs_bar' => array(
			'label'  => 'Progression',
			'type'   => 'checkbox',
			'text'   => 'Afficher une barre de progression',
			'enable' => "wf_hs === 'pin'",
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
		'wf_hs_ease'      => 70,
		'wf_hs_snap'      => true,
		'wf_hs_from'      => '960',
		'wf_hs_bar'       => false,
		'wf_hs_bar_color' => '#EA5C1C',
	);
}
endif;

if ( ! function_exists( 'wf_shs_css' ) ) :
/**
 * CSS d'une section : le socle « bande latérale » plus les variables que le
 * script relit. Retourne '' si rien n'est demandé.
 *
 * @param array $p Props du nœud.
 * @return string
 */
function wf_shs_css( array $p ) {
	$mode = (string) ( $p['wf_hs'] ?? '' );
	if ( 'strip' !== $mode && 'pin' !== $mode ) { return ''; }

	// Une section prise dans un parcours en escalier est déjà posée sur un
	// plateau par wf-section-ribbon.php : elle ne peut pas en plus s'épingler
	// pour elle-même. Le parcours l'emporte, sans discussion possible.
	if ( ! empty( $p['wf_rb'] ) ) { return ''; }

	$dir   = ( 'right' === ( $p['wf_hs_dir'] ?? 'left' ) ) ? 'right' : 'left';
	$width = (string) ( $p['wf_hs_width'] ?? '100' );
	if ( ! in_array( $width, array( '100', '75', '66', '50', '33', 'auto' ), true ) ) { $width = '100'; }
	$gap  = max( 0, min( 160, (int) ( $p['wf_hs_gap'] ?? 0 ) ) );
	$len  = max( 50, min( 400, (int) ( $p['wf_hs_len'] ?? 100 ) ) );
	$ease = max( 0, min( 100, (int) ( $p['wf_hs_ease'] ?? 70 ) ) );
	$snap = ! empty( $p['wf_hs_snap'] ) && 'strip' === $mode;
	$bar  = ! empty( $p['wf_hs_bar'] ) && 'pin' === $mode;
	$barc = (string) ( $p['wf_hs_bar_color'] ?? '#EA5C1C' );

	$bp = (int) ( $p['wf_hs_from'] ?? 960 );
	if ( ! in_array( $bp, array( 0, 640, 960, 1200 ), true ) ) { $bp = 960; }
	$mq = $bp > 0 ? '@media (min-width:' . $bp . 'px)' : '@media screen';

	// Le rail, à n'importe quelle profondeur. .uk-grid est une classe publique
	// UIkit : c'est le seul repère stable dont on dispose côté CSS. Le script,
	// lui, a un repli si elle est absente.
	$rail  = '.el-element .uk-grid';
	$panel = '.el-element .uk-grid > *';

	// Les valeurs voyagent en variables CSS plutôt qu'en attributs : le script
	// les relit avec getComputedStyle, et une section sans script reste propre.
	$vars = '.el-element{'
		. '--wf-hs-mode:' . $mode . ';'
		. '--wf-hs-dir:' . $dir . ';'
		. '--wf-hs-len:' . $len . ';'
		. '--wf-hs-ease:' . $ease . ';'
		. '--wf-hs-bp:' . $bp . ';'
		. ( $bar ? '--wf-hs-bar:' . $barc . ';' : '' )
		. '}';

	$flex = 'auto' === $width ? 'flex:0 0 auto;' : 'flex:0 0 ' . $width . '%;';

	/* Socle : une vraie bande latérale, partout, sans JavaScript. */
	$base = $mq . '{'
		. $rail . '{'
			. 'flex-wrap:nowrap;'
			. 'margin-left:0;'
			. 'overflow-x:auto;'
			. 'overscroll-behavior-x:contain;'
			. 'scrollbar-width:thin;'
			. ( $gap > 0 ? 'gap:' . $gap . 'px;' : '' )
			. ( $snap ? 'scroll-snap-type:x mandatory;' : '' )
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

	/* Le script pose .is-pinned ; tout ce qui suit n'existe que dans ce cas,
	   donc rien ne bouge s'il ne tourne pas. */
	$pinned = '';
	if ( 'pin' === $mode ) {
		// Marges verticales à zéro : sinon la hauteur posée par le script est
		// rognée de leur épaisseur (box-sizing:border-box) et la traversée
		// s'arrête juste avant la fin.
		$pinned = '.el-element.is-pinned{padding-top:0;padding-bottom:0;}'
			. '.el-element.is-pinned .wf-hs-view{'
				. 'position:sticky;top:0;height:100vh;overflow:hidden;'
			. '}'
			. '.el-element.is-pinned .uk-grid{'
				. 'overflow:visible;scroll-snap-type:none;will-change:transform;'
				. 'align-items:stretch;height:100%;'
			. '}'
			// Panneaux plein écran : c'est ce qui donne le rendu « chapitre ».
			// Le contenu n'est pas rogné — il peut déborder sur le panneau
			// suivant, ce qui est justement l'effet recherché.
			. '.el-element.is-pinned .uk-grid > *{scroll-snap-align:none;min-height:100vh;display:flex;flex-direction:column;justify-content:center;}';
		if ( $bar ) {
			$pinned .= '.el-element.is-pinned .wf-hs-view::after{'
				. 'content:"";position:absolute;top:0;left:0;right:0;height:3px;'
				. 'background:' . $barc . ';'
				. 'transform:scaleX(var(--wf-hs-progress,0));'
				. 'transform-origin:' . ( 'right' === $dir ? 'right' : 'left' ) . ' center;'
				. 'z-index:2;pointer-events:none;'
			. '}';
		}
	}

	return $vars . $base . $pinned;
}
endif;

if ( ! function_exists( 'wf_shs_script' ) ) :
/**
 * Le moteur d'épinglage. Imprimé une seule fois, et seulement si une section
 * de la page l'a demandé.
 *
 * @return string
 */
function wf_shs_script() {
	ob_start();
	?>
<script>
(function(){
	'use strict';

	function nombre(cs, nom, dflt){
		var v = parseFloat(cs.getPropertyValue(nom));
		return isNaN(v) ? dflt : v;
	}

	/**
	 * Trouve le rail dans le vrai DOM. .uk-grid d'abord — c'est ce que rend
	 * YOOtheme. Sinon, le descendant le moins profond qui a au moins deux
	 * enfants éléments : ça couvre un balisage inattendu sans rien supposer.
	 */
	function trouverRail(sec){
		var rail = sec.querySelector('.uk-grid');
		if (rail) { return rail; }
		var file = [sec], n;
		while (file.length) {
			n = file.shift();
			var enfants = [];
			for (var i = 0; i < n.children.length; i++) { enfants.push(n.children[i]); }
			if (n !== sec && enfants.length > 1) { return n; }
			for (var j = 0; j < enfants.length; j++) { file.push(enfants[j]); }
		}
		return null;
	}

	function preparer(sec){
		var cs = getComputedStyle(sec);
		if (cs.getPropertyValue('--wf-hs-mode').trim() !== 'pin') { return null; }
		if (window.innerWidth < nombre(cs, '--wf-hs-bp', 960)) { return null; }

		var rail = trouverRail(sec);
		if (!rail) {
			if (window.console) { console.warn('WF défilement latéral : aucune ligne trouvée dans', sec); }
			return null;
		}
		var panneaux = rail.children.length;
		if (panneaux < 2) {
			if (window.console) { console.warn('WF défilement latéral : il faut au moins deux colonnes, ' + panneaux + ' trouvée(s) dans', sec); }
			return null;
		}

		// Le hublot est le parent du rail. Si le rail est enfant direct de la
		// section, ce parent n'existe pas : on le fabrique. C'est le niveau de
		// DOM que le CSS seul ne pouvait pas inventer.
		var vue = rail.parentElement;
		if (vue === sec) {
			vue = document.createElement('div');
			vue.className = 'wf-hs-view';
			sec.insertBefore(vue, rail);
			vue.appendChild(rail);
		} else {
			vue.classList.add('wf-hs-view');
		}

		return { sec: sec, vue: vue, rail: rail };
	}

	function activer(o){
		var sec = o.sec, vue = o.vue, rail = o.rail;
		var cs = getComputedStyle(sec);
		var len = nombre(cs, '--wf-hs-len', 100) / 100;
		var douceur = nombre(cs, '--wf-hs-ease', 70);
		var versDroite = cs.getPropertyValue('--wf-hs-dir').trim() === 'right';

		var calme = false;
		if (window.matchMedia) {
			if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { calme = true; }
		}
		// Moins d'animations : on épingle quand même (c'est une mise en page,
		// pas une décoration) mais le rail suit le scroll au pixel, sans
		// glissement résiduel.
		var k = calme ? 1 : 1 - (douceur / 100) * 0.86;

		var course = 0, haut = 0, bas = 0, x = 0, cible = 0, raf = 0, visible = true;
		var actif = false;

		// L'hôte porte la hauteur de défilement. Ce n'est pas forcément la
		// section : un élément collant est borné par la boîte de contenu de son
		// PARENT. Avec deux niveaux de conteneurs, gonfler la section laissait
		// le conteneur intermédiaire à un écran de haut, et le hublot ne collait
		// pas. Mesuré : le hublot filait à -1170 px au lieu de rester à 0.
		var hote = vue.parentElement ? vue.parentElement : sec;

		function mesurer(){
			// Tout est mesuré, rien n'est supposé. Mais pas avec scrollWidth :
			// un élément sans boîte de défilement le renvoie égal à clientWidth,
			// et le mode épinglé met justement overflow:visible sur le rail — la
			// course valait alors zéro. On prend donc la géométrie réelle, du
			// bord gauche du premier panneau au bord droit du dernier.
			rail.style.transform = 'none';
			var prem = rail.children[0].getBoundingClientRect();
			var dern = rail.children[rail.children.length - 1].getBoundingClientRect();
			// Contre la largeur du rail, pas celle du hublot : clientWidth
			// inclut le padding du conteneur et décalerait la fin de course.
			var large = rail.getBoundingClientRect().width;
			course = Math.max(0, Math.round(dern.right - prem.left - large));
			var hVue = Math.round(vue.getBoundingClientRect().height);
			hote.style.height = (hVue + Math.round(course * len)) + 'px';
			var r = hote.getBoundingClientRect();
			haut = r.top + window.pageYOffset;
			bas  = haut + hote.offsetHeight - hVue;
			if (bas <= haut) { bas = haut + 1; }
			cible = versDroite ? course : 0;
			x = cible;
		}

		function progression(){
			var y = window.pageYOffset;
			var p = (y - haut) / (bas - haut);
			return p < 0 ? 0 : (p > 1 ? 1 : p);
		}

		function peindre(){
			var p = progression();
			cible = versDroite ? course * (1 - p) : course * p;
			x += (cible - x) * k;
			if (Math.abs(cible - x) < 0.15) { x = cible; }
			rail.style.transform = 'translate3d(' + (-x).toFixed(2) + 'px,0,0)';
			sec.style.setProperty('--wf-hs-progress', p.toFixed(4));
		}

		function boucle(){
			if (!actif) { raf = 0; return; }
			peindre();
			if (!visible) { raf = 0; return; }
			if (x === cible) { raf = 0; return; }
			raf = requestAnimationFrame(boucle);
		}
		function relancer(){
			if (raf) { return; }
			if (!visible) { return; }
			raf = requestAnimationFrame(boucle);
		}

		// Un seul interrupteur. Sans lui, le ResizeObserver ci-dessous rallumait
		// l'épinglage juste après que le seuil d'écran l'avait éteint : la
		// hauteur et la translation revenaient, et la section restait cassée
		// sur mobile.
		function auSeuil(){
			return window.innerWidth >= nombre(getComputedStyle(sec), '--wf-hs-bp', 960);
		}
		function allumer(){
			actif = true;
			sec.classList.add('is-pinned');
			mesurer();
			peindre();
		}
		function eteindre(){
			actif = false;
			sec.classList.remove('is-pinned');
			hote.style.height = '';
			rail.style.transform = '';
			sec.style.removeProperty('--wf-hs-progress');
		}

		allumer();

		window.addEventListener('scroll', function(){
			if (!actif) { return; }
			relancer();
		}, { passive: true });

		var tmo = 0;
		window.addEventListener('resize', function(){
			clearTimeout(tmo);
			tmo = setTimeout(function(){
				if (auSeuil()) { allumer(); } else { eteindre(); }
			}, 150);
		});

		if ('IntersectionObserver' in window) {
			var io = new IntersectionObserver(function(e){
				for (var i = 0; i < e.length; i++) {
					visible = e[i].isIntersecting;
					if (visible) { relancer(); }
				}
			}, { rootMargin: '200px 0px' });
			io.observe(sec);
		}

		// Les images qui arrivent après coup changent la largeur du rail.
		if (window.ResizeObserver) {
			var ro = new ResizeObserver(function(){
				if (!actif) { return; }
				mesurer();
				peindre();
			});
			ro.observe(rail);
		}
	}

	function demarrer(){
		var secs = document.querySelectorAll('.wf-hs');
		for (var i = 0; i < secs.length; i++) {
			var o = preparer(secs[i]);
			if (o) { activer(o); }
		}
	}

	if (window.WF) { if (WF.ready) { WF.ready(demarrer); return; } }
	if (document.readyState !== 'loading') { demarrer(); }
	else { document.addEventListener('DOMContentLoaded', demarrer); }
})();
</script>
	<?php
	return (string) ob_get_clean();
}
endif;

/**
 * Branchement : champs dans le panneau, CSS et classe au rendu, script en pied
 * de page si au moins une section l'a demandé.
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
			$flat = wp_json_encode( $tab['fields'] );
			if ( is_string( $flat ) && false !== strpos( $flat, 'wf_hs' ) ) { break; }
			$tab['fields'][] = array(
				'label'   => 'Défilement latéral',
				'type'    => 'group',
				'divider' => true,
				'fields'  => array( 'wf_hs', 'wf_hs_dir', 'wf_hs_width', 'wf_hs_gap', 'wf_hs_len', 'wf_hs_ease', 'wf_hs_snap', 'wf_hs_from', 'wf_hs_bar', 'wf_hs_bar_color' ),
			);
			break;
		}
		unset( $tab );
	}

	// 3. Rendu : CSS ajouté à celui de la section, plus la classe qui sert de
	//    prise au script. Le 0 place ce transform en tête de liste.
	$builder->addTransform(
		'prerender',
		function ( $node, $params ) {
			if ( ! is_object( $node ) ) { return; }
			if ( ( $node->type ?? '' ) !== 'section' ) { return; }
			$props = (array) ( $node->props ?? array() );
			$css   = wf_shs_css( $props );
			if ( '' === $css ) { return; }

			$node->props['css'] = trim( $css . ' ' . (string) ( $props['css'] ?? '' ) );

			$classes = trim( (string) ( $props['class'] ?? '' ) );
			if ( false === strpos( ' ' . $classes . ' ', ' wf-hs ' ) ) {
				$node->props['class'] = trim( $classes . ' wf-hs' );
			}

			$GLOBALS['wf_shs_needed'] = true;
		},
		0
	);

	// 4. Le moteur, une seule fois, et seulement s'il sert à quelque chose.
	add_action( 'wp_footer', function () {
		if ( empty( $GLOBALS['wf_shs_needed'] ) ) { return; }
		echo wf_shs_script(); // phpcs:ignore WordPress.Security.EscapeOutput -- balisage fixe.
	}, 99 );
}
endif;
