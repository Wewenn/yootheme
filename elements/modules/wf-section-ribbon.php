<?php
/**
 * WeFrame – Parcours en lignes (sections côte à côte)
 *
 * Ajoute un groupe de champs à la Section native : au lieu de descendre, la page
 * enchaîne plusieurs sections côte à côte sur une même ligne, et le défilement
 * les fait défiler de gauche à droite. Quand la ligne est finie, la page reprend
 * son cours vers le bas jusqu'à la ligne suivante.
 *
 * ── Ce que ça fait ───────────────────────────────────────────────────────
 *
 *     S1 → S2            ligne 1 : on part de la GAUCHE, on va vers la droite
 *     ↓ (défilement vertical normal)
 *     S3                 ligne 2 : une seule section, rien de spécial
 *     ↓ (défilement vertical normal)
 *     S4 → S5 → S6       ligne 3 : de la GAUCHE vers la droite, à nouveau
 *
 * ── Pourquoi ce n'est plus un plateau à deux dimensions ──────────────────
 *
 * La première version posait toutes les sections sur un seul plateau et y
 * promenait une caméra : la ligne suivante démarrait à la colonne où la
 * précédente s'était arrêtée, en escalier. Résultat à l'écran : les lignes ne
 * commençaient pas au même endroit, et la descente entre deux lignes était un
 * mouvement de caméra vers le bas au milieu du plateau.
 *
 * Ici chaque ligne est un bloc indépendant : elle s'épingle le temps de sa
 * traversée, puis relâche. Entre deux lignes, il n'y a pas de mouvement
 * particulier à inventer — c'est le défilement vertical ordinaire de la page.
 *
 * Trois gains : chaque ligne commence à gauche et finit à droite ; il n'y a
 * plus de trajet diagonal ni de retour chariot à mettre en scène ; et une ligne
 * d'une seule section n'est rien d'autre qu'une section normale.
 *
 * ── Ce que ce n'est pas ──────────────────────────────────────────────────
 *
 * Ce n'est pas wf-section-scroll.php. Celui-là fait défiler l'INTÉRIEUR d'une
 * section : ses colonnes deviennent des panneaux, et rien ne sort de la
 * section. Ici ce sont des sections ENTIÈRES qu'on met côte à côte.
 *
 * Une section YOOtheme ne peut pas en contenir une autre : la ligne ne peut
 * donc pas être un élément du builder. C'est le script qui, à l'exécution,
 * repère les sections voisines déclarées d'une même ligne, fabrique le cadre
 * autour d'elles et les y place. D'où le réglage : il ne dit pas « cette
 * section défile », il dit « cette section se pose là ».
 *
 * ── Le défilement n'est jamais détourné ──────────────────────────────────
 *
 * Ni molette interceptée, ni scrollTop écrit à la main. Une ligne devient une
 * zone verticale plus haute que l'écran, on y colle un hublot, et le décalage
 * horizontal se déduit de la progression de cette zone. Le clavier, l'inertie
 * du trackpad, la barre de défilement et le geste tactile restent ceux du
 * navigateur.
 *
 * ── Sans script, la page reste la page ───────────────────────────────────
 *
 * Aucune règle CSS n'est posée sur les sections tant que le cadre n'est pas
 * monté. Script absent, écran trop étroit, mouvement réduit demandé : les
 * sections restent empilées dans l'ordre. Le repli n'est pas une version
 * dégradée, c'est la page normale.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wf_rb_fields' ) ) :
/**
 * Champs ajoutés à la Section.
 *
 * Les réglages du parcours vivent sur la section qui le démarre : un parcours,
 * un jeu de réglages, quel que soit son nombre de lignes.
 *
 * @return array<string, array<string, mixed>>
 */
function wf_rb_fields() {
	return array(
		'wf_rb' => array(
			'label'   => 'Sections côte à côte',
			'type'    => 'select',
			'options' => array(
				'Non'                                => '',
				'Démarre la première ligne'          => 'start',
				'À droite de la précédente'          => 'right',
				'Nouvelle ligne (retour à gauche)'   => 'down',
			),
			'description' => "Les sections d'un même parcours doivent se suivre immédiatement dans la page. Chaque ligne part de la gauche et défile vers la droite ; entre deux lignes, la page descend normalement.",
		),
		'wf_rb_w' => array(
			'label'   => 'Largeur de cette section',
			'type'    => 'select',
			'options' => array(
				'Plein écran'      => '100',
				'Trois quarts'     => '75',
				'La moitié'        => '50',
				'Un tiers'         => '33',
				'Selon le contenu' => 'auto',
			),
			'enable'  => 'wf_rb',
			'description' => "Si toute la ligne tient dans l'écran, elle ne défile pas : les sections sont simplement posées côte à côte.",
		),
		'wf_rb_len' => array(
			'label'  => 'Longueur de la traversée (%)',
			'type'   => 'number',
			'attrs'  => array( 'min' => 50, 'max' => 400, 'step' => 10 ),
			'source' => true,
			'enable' => "wf_rb === 'start'",
			'description' => "100 = un pixel de défilement vertical pour un pixel parcouru vers la droite. Plus haut, la traversée est plus lente.",
		),
		'wf_rb_ease' => array(
			'label'  => 'Souplesse du mouvement',
			'type'   => 'number',
			'attrs'  => array( 'min' => 0, 'max' => 100, 'step' => 5 ),
			'source' => true,
			'enable' => "wf_rb === 'start'",
			'description' => "0 : la ligne suit le défilement au pixel. 100 : elle glisse encore un instant après l'arrêt.",
		),
		'wf_rb_from' => array(
			'label'   => 'À partir de',
			'type'    => 'select',
			'options' => array(
				'Tablette (≥ 640 px)'     => '640',
				'Bureau (≥ 960 px)'       => '960',
				'Grand écran (≥ 1200 px)' => '1200',
			),
			'enable'  => "wf_rb === 'start'",
			'description' => "En dessous, les sections reprennent leur empilement vertical normal, dans l'ordre.",
		),
		'wf_rb_bar' => array(
			'label'  => 'Progression',
			'type'   => 'checkbox',
			'text'   => 'Afficher une barre de progression sur chaque ligne',
			'enable' => "wf_rb === 'start'",
		),
		'wf_rb_bar_color' => array(
			'label'  => 'Couleur de la barre',
			'type'   => 'color',
			'enable' => "wf_rb === 'start' && wf_rb_bar",
		),
	);
}
endif;

if ( ! function_exists( 'wf_rb_defaults' ) ) :
function wf_rb_defaults() {
	return array(
		'wf_rb'           => '',
		'wf_rb_w'         => '100',
		'wf_rb_len'       => 100,
		'wf_rb_ease'      => 70,
		'wf_rb_from'      => '960',
		'wf_rb_bar'       => false,
		'wf_rb_bar_color' => '#EA5C1C',
	);
}
endif;

if ( ! function_exists( 'wf_rb_css' ) ) :
/**
 * Les valeurs voyagent en variables CSS, jamais en attributs : le script les
 * relit avec getComputedStyle, et une section sans script reste propre.
 *
 * Aucune règle de mise en page ici — tout ce qui positionne est posé par le
 * script, et seulement quand le cadre est monté.
 *
 * @param array $p Props du nœud.
 * @return string
 */
function wf_rb_css( array $p ) {
	$role = (string) ( $p['wf_rb'] ?? '' );
	if ( ! in_array( $role, array( 'start', 'right', 'down' ), true ) ) { return ''; }

	$w = (string) ( $p['wf_rb_w'] ?? '100' );
	if ( ! in_array( $w, array( '100', '75', '50', '33', 'auto' ), true ) ) { $w = '100'; }

	$css = '.el-element{--wf-rb-role:' . $role . ';--wf-rb-w:' . $w . ';';

	// Les réglages du parcours ne sont lus que sur sa tête.
	if ( 'start' === $role ) {
		$len  = max( 50, min( 400, (int) ( $p['wf_rb_len'] ?? 100 ) ) );
		$ease = max( 0, min( 100, (int) ( $p['wf_rb_ease'] ?? 70 ) ) );
		$bp   = (int) ( $p['wf_rb_from'] ?? 960 );
		if ( ! in_array( $bp, array( 640, 960, 1200 ), true ) ) { $bp = 960; }

		$css .= '--wf-rb-len:' . $len . ';--wf-rb-ease:' . $ease . ';--wf-rb-bp:' . $bp . ';';

		if ( ! empty( $p['wf_rb_bar'] ) ) {
			$css .= '--wf-rb-bar:' . (string) ( $p['wf_rb_bar_color'] ?? '#EA5C1C' ) . ';';
		}
	}

	return $css . '}';
}
endif;

if ( ! function_exists( 'wf_rb_script' ) ) :
/**
 * Le moteur. Imprimé une seule fois, et seulement si une section l'a demandé.
 *
 * @return string
 */
function wf_rb_script() {
	ob_start();
	?>
<style>
/* Structure d'une ligne. Rien de tout cela n'existe avant que le script ne la
   monte : ces classes sont posées par lui. */
.wf-rb-stage{position:relative;}
.wf-rb-view{position:sticky;top:0;height:100vh;overflow:hidden;}
.wf-rb-board{position:relative;will-change:transform;}
/* !important sur la marge : une section peut porter la sienne via le champ
   « Marge », et la marge d'un élément absolu décale sa position. */
.wf-rb-cell{position:absolute;top:0;margin:0 !important;box-sizing:border-box;}
.wf-rb-stage[data-bar] .wf-rb-view::after{
	content:"";position:absolute;top:0;left:0;right:0;height:3px;z-index:2;
	background:var(--wf-rb-bar,currentColor);pointer-events:none;
	transform:scaleX(var(--wf-rb-progress,0));transform-origin:left center;
}
</style>
<script>
(function(){
	'use strict';

	function nombre(cs, nom, dflt){
		var v = parseFloat(cs.getPropertyValue(nom));
		return isNaN(v) ? dflt : v;
	}
	function texte(cs, nom){
		return cs.getPropertyValue(nom).trim();
	}
	function crier(sec, raison){
		if (window.console) {
			console.warn('WF sections côte à côte : ' + raison, sec);
		}
	}

	/**
	 * Un élément qui n'occupe aucune place : entre deux sections d'un parcours,
	 * c'est tolérable. Une balise <style> par nœud, un script analytique, un
	 * bloc masqué par une condition d'affichage — rien de tout cela ne rompt le
	 * voisinage, et c'est justement ce que rend YOOtheme entre deux sections.
	 */
	function sansBoite(el){
		var tag = el.tagName;
		if ('STYLE' === tag || 'SCRIPT' === tag || 'LINK' === tag || 'TEMPLATE' === tag || 'NOSCRIPT' === tag) {
			return true;
		}
		return 'none' === getComputedStyle(el).display;
	}

	/** Le voisin visible qui précède, en sautant ce qui ne se voit pas. */
	function precedent(sec){
		var p = sec.previousElementSibling;
		while (p && sansBoite(p)) { p = p.previousElementSibling; }
		return p;
	}

	/**
	 * Rassemble les sections voisines en parcours. Un parcours = une section
	 * « Démarre », suivie immédiatement de sections « à droite » ou
	 * « nouvelle ligne ». Le voisinage est vérifié, pas supposé : une section
	 * ordinaire glissée au milieu casserait la mise en page en silence.
	 */
	function grouper(){
		var secs = document.querySelectorAll('.wf-rb');
		var groupes = [], courant = null, i;
		for (i = 0; i < secs.length; i++) {
			var sec = secs[i];
			var r = texte(getComputedStyle(sec), '--wf-rb-role');
			if ('start' === r) {
				courant = { cases: [sec], roles: ['start'] };
				groupes.push(courant);
				continue;
			}
			if (!courant) {
				crier(sec, 'aucune section « Démarre la première ligne » avant celle-ci');
				continue;
			}
			var dernier = courant.cases[courant.cases.length - 1];
			if (sec.parentElement !== dernier.parentElement || precedent(sec) !== dernier) {
				crier(sec, 'elle ne suit pas immédiatement la section précédente du parcours');
				courant = null;
				continue;
			}
			courant.cases.push(sec);
			courant.roles.push('down' === r ? 'down' : 'right');
		}
		return groupes;
	}

	/**
	 * Découpe un parcours en lignes. Chaque « nouvelle ligne » ouvre une ligne,
	 * et c'est tout : les lignes sont indépendantes les unes des autres, ce qui
	 * est précisément ce qui les fait toutes partir de la gauche.
	 */
	function decouper(g){
		var lignes = [], courante = null;
		for (var i = 0; i < g.cases.length; i++) {
			if (0 === i || 'down' === g.roles[i]) {
				courante = [];
				lignes.push(courante);
			}
			courante.push(g.cases[i]);
		}
		return lignes;
	}

	/**
	 * Monte une ligne : les sections passent côte à côte dans un cadre, et le
	 * défilement vertical de la page les fait glisser vers la gauche.
	 *
	 * @param {Element[]} cases Les sections de cette ligne, dans l'ordre.
	 * @param {Object}    reg   Réglages lus sur la tête du parcours.
	 */
	function activerLigne(cases, reg){
		var n = cases.length;
		var parent = cases[0].parentElement;
		var ancre = document.createComment('wf-rb');
		parent.insertBefore(ancre, cases[0]);

		var stage = null, vue = null, board = null, io = null;
		var actif = false, visible = true, raf = 0;
		var larg = [], course = 0, hVue = 0;
		var cx = 0, vx = 0;
		var k = reg.k;

		function monter(){
			stage = document.createElement('div');
			stage.className = 'wf-rb-stage';
			vue = document.createElement('div');
			vue.className = 'wf-rb-view';
			board = document.createElement('div');
			board.className = 'wf-rb-board';
			stage.appendChild(vue);
			vue.appendChild(board);
			parent.insertBefore(stage, ancre);
			if (reg.barre) { stage.setAttribute('data-bar', ''); }
			for (var i = 0; i < n; i++) {
				cases[i].classList.add('wf-rb-cell');
				board.appendChild(cases[i]);
			}
			// L'observateur suit le cadre lui-même. Première version : il suivait
			// « ce qui vient après l'ancre », qui se trouve être une balise
			// <style> — un élément sans boîte, donc jamais visible. La ligne ne
			// repeignait jamais.
			if ('IntersectionObserver' in window) {
				io = new IntersectionObserver(function(e){
					for (var j = 0; j < e.length; j++) {
						visible = e[j].isIntersecting;
						if (visible) { relancer(); }
					}
				}, { rootMargin: '200px 0px' });
				io.observe(stage);
			}
		}

		function demonter(){
			if (io) { io.disconnect(); io = null; }
			visible = true;
			// Les sections retournent à leur place, dans l'ordre, devant l'ancre.
			for (var i = 0; i < n; i++) {
				var c = cases[i];
				c.classList.remove('wf-rb-cell');
				c.style.left = '';
				c.style.width = '';
				c.style.height = '';
				parent.insertBefore(c, ancre);
			}
			if (stage && stage.parentElement) { stage.parentElement.removeChild(stage); }
			stage = vue = board = null;
		}

		/** Largeur naturelle d'une section, bornée : « selon le contenu ». */
		function auto(el, vp){
			var avant = el.style.width;
			el.style.width = 'max-content';
			var w = Math.round(el.getBoundingClientRect().width);
			el.style.width = avant;
			if (!w) { w = vp; }
			return Math.max(Math.round(vp * 0.25), Math.min(Math.round(vp * 3), w));
		}

		function mesurer(){
			hVue = Math.round(vue.getBoundingClientRect().height);
			// Contre la largeur du hublot, pas window.innerWidth : celle-ci
			// compte la barre de défilement, et le parent peut être borné.
			var vp = Math.round(vue.getBoundingClientRect().width);
			var x = 0, i;

			for (i = 0; i < n; i++) {
				var brut = texte(getComputedStyle(cases[i]), '--wf-rb-w');
				larg[i] = ('auto' === brut)
					? auto(cases[i], vp)
					: Math.round(vp * (parseFloat(brut) || 100) / 100);
				cases[i].style.left = x + 'px';
				cases[i].style.width = larg[i] + 'px';
				cases[i].style.height = hVue + 'px';
				x += larg[i];
			}
			board.style.width = x + 'px';
			board.style.height = hVue + 'px';

			// La ligne s'arrête quand son bord droit rejoint celui du hublot.
			// Si elle tient déjà dans l'écran, il n'y a rien à parcourir : les
			// sections sont simplement posées côte à côte.
			course = Math.max(0, x - vp);
			stage.style.height = (hVue + Math.round(course * reg.len)) + 'px';
			cx = 0; vx = 0;
		}

		/**
		 * Lue en direct sur la position du cadre à l'écran, et pas sur des
		 * bornes calculées une fois pour toutes : une image qui arrive en
		 * retard, une police qui se substitue, une ligne qui se monte au-dessus
		 * — tout cela déplace le cadre, et des bornes gardées en mémoire
		 * deviennent fausses sans prévenir.
		 */
		function progression(){
			var total = stage.offsetHeight - hVue;
			if (total < 1) { return 0; }
			var p = -stage.getBoundingClientRect().top / total;
			return p < 0 ? 0 : (p > 1 ? 1 : p);
		}

		function peindre(){
			if (!actif) { return; }
			var p = progression();
			vx = course * p;
			cx += (vx - cx) * k;
			if (Math.abs(vx - cx) < 0.15) { cx = vx; }
			board.style.transform = 'translate3d(' + (-cx).toFixed(2) + 'px,0,0)';
			stage.style.setProperty('--wf-rb-progress', p.toFixed(4));
		}

		function boucle(){
			if (!actif) { raf = 0; return; }
			peindre();
			if (!visible) { raf = 0; return; }
			if (cx === vx) { raf = 0; return; }
			raf = requestAnimationFrame(boucle);
		}
		function relancer(){
			if (raf || !visible || !actif) { return; }
			raf = requestAnimationFrame(boucle);
		}

		function allumer(){
			if (actif) { return; }
			actif = true;
			monter();
			mesurer();
			peindre();
		}
		function eteindre(){
			if (!actif) { return; }
			actif = false;
			demonter();
		}

		return { allumer: allumer, eteindre: eteindre, relancer: relancer, remesurer: function(){
			if (!actif) { return; }
			mesurer();
			peindre();
		} };
	}

	function demarrer(){
		var groupes = grouper();
		var lignesActives = [];
		var calme = false;
		if (window.matchMedia) {
			// Une mise en page qui glisse sous les yeux à chaque tour de molette
			// est exactement ce qu'un visiteur sensible au mouvement demande à
			// ne pas subir. La page verticale, elle, est complète.
			if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { calme = true; }
		}

		for (var i = 0; i < groupes.length; i++) {
			var g = groupes[i];
			var cs = getComputedStyle(g.cases[0]);
			var douceur = nombre(cs, '--wf-rb-ease', 70);
			var reg = {
				len:   nombre(cs, '--wf-rb-len', 100) / 100,
				seuil: nombre(cs, '--wf-rb-bp', 960),
				barre: texte(cs, '--wf-rb-bar'),
				k:     1 - (douceur / 100) * 0.86
			};

			var lignes = decouper(g);
			var posees = 0;
			for (var j = 0; j < lignes.length; j++) {
				// Une ligne d'une seule section n'est rien d'autre qu'une
				// section : on la laisse tranquille, dans le flux de la page.
				if (lignes[j].length < 2) { continue; }
				posees++;
				lignesActives.push({ ligne: activerLigne(lignes[j], reg), seuil: reg.seuil, calme: calme });
			}
			if (!posees) {
				crier(g.cases[0], 'aucune ligne de ce parcours n\'a deux sections ou plus');
			}
		}

		if (!lignesActives.length) { return; }

		function auSeuil(l){
			return !l.calme && window.innerWidth >= l.seuil;
		}
		function ajuster(){
			for (var i = 0; i < lignesActives.length; i++) {
				var l = lignesActives[i];
				if (auSeuil(l)) { l.ligne.allumer(); l.ligne.remesurer(); }
				else { l.ligne.eteindre(); }
			}
		}

		// Premier montage : dans l'ordre du document, pour que chaque ligne
		// mesure sa position une fois que celles du dessus ont pris leur place.
		for (var m = 0; m < lignesActives.length; m++) {
			if (auSeuil(lignesActives[m])) { lignesActives[m].ligne.allumer(); }
		}

		window.addEventListener('scroll', function(){
			for (var i = 0; i < lignesActives.length; i++) { lignesActives[i].ligne.relancer(); }
		}, { passive: true });

		var tmo = 0;
		window.addEventListener('resize', function(){
			clearTimeout(tmo);
			tmo = setTimeout(ajuster, 150);
		});
	}

	if (window.WF && WF.ready) { WF.ready(demarrer); return; }
	if (document.readyState !== 'loading') { demarrer(); }
	else { document.addEventListener('DOMContentLoaded', demarrer); }
})();
</script>
	<?php
	return (string) ob_get_clean();
}
endif;

if ( ! function_exists( 'wf_rb_boot' ) ) :
/**
 * Branchement : champs dans le panneau, variables et classe au rendu, moteur en
 * pied de page si au moins une section l'a demandé.
 *
 * @param object $builder Instance YOOtheme\Builder.
 */
function wf_rb_boot( $builder ) {
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

	foreach ( wf_rb_fields() as $key => $def ) {
		if ( ! isset( $section->fields[ $key ] ) ) {
			$section->fields[ $key ] = $def;
		}
	}
	if ( isset( $section->defaults ) && is_array( $section->defaults ) ) {
		$section->defaults += wf_rb_defaults();
	}

	if ( isset( $section->fieldset['default']['fields'] ) && is_array( $section->fieldset['default']['fields'] ) ) {
		foreach ( $section->fieldset['default']['fields'] as &$tab ) {
			if ( ! is_array( $tab ) ) { continue; }
			$title = $tab['title'] ?? '';
			if ( 'Settings' !== $title && 'Paramètres' !== $title && 'Parametres' !== $title ) { continue; }
			if ( ! isset( $tab['fields'] ) || ! is_array( $tab['fields'] ) ) { continue; }
			$flat = wp_json_encode( $tab['fields'] );
			if ( is_string( $flat ) && false !== strpos( $flat, 'wf_rb' ) ) { break; }
			$tab['fields'][] = array(
				'label'   => 'Sections côte à côte',
				'type'    => 'group',
				'divider' => true,
				'fields'  => array( 'wf_rb', 'wf_rb_w', 'wf_rb_len', 'wf_rb_ease', 'wf_rb_from', 'wf_rb_bar', 'wf_rb_bar_color' ),
			);
			break;
		}
		unset( $tab );
	}

	$builder->addTransform(
		'prerender',
		function ( $node, $params ) {
			if ( ! is_object( $node ) ) { return; }
			if ( ( $node->type ?? '' ) !== 'section' ) { return; }
			$props = (array) ( $node->props ?? array() );
			$css   = wf_rb_css( $props );
			if ( '' === $css ) { return; }

			$node->props['css'] = trim( $css . ' ' . (string) ( $props['css'] ?? '' ) );

			$classes = trim( (string) ( $props['class'] ?? '' ) );
			if ( false === strpos( ' ' . $classes . ' ', ' wf-rb ' ) ) {
				$node->props['class'] = trim( $classes . ' wf-rb' );
			}

			$GLOBALS['wf_rb_needed'] = true;
		},
		0
	);

	add_action( 'wp_footer', function () {
		if ( empty( $GLOBALS['wf_rb_needed'] ) ) { return; }
		echo wf_rb_script(); // phpcs:ignore WordPress.Security.EscapeOutput -- balisage fixe.
	}, 99 );
}
endif;
