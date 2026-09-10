<?php
/**
 * WeFrame – Parcours en escalier (ruban de sections)
 *
 * Ajoute un second groupe de champs à la Section native : au lieu de descendre,
 * la page enchaîne les sections sur un plateau à deux dimensions, et le
 * défilement y promène une caméra.
 *
 * ── Ce que ça fait, en une image ──────────────────────────────────────────
 *
 *     S1 → S2 ┐          « À droite » : la section se pose à droite
 *             ↓
 *            S3          « En dessous » : elle descend, SANS revenir à gauche
 *             ↓
 *            S4 → S5 → S6
 *
 * La descente se fait à la colonne où la ligne s'arrête. C'est un escalier qui
 * descend vers la droite, pas un serpent qui fait des allers-retours.
 *
 * ── Ce que ce n'est pas ──────────────────────────────────────────────────
 *
 * Ce n'est pas wf-section-scroll.php. Celui-là fait défiler l'INTÉRIEUR d'une
 * section : ses colonnes deviennent des panneaux, et rien ne sort de la
 * section. Ici ce sont des sections ENTIÈRES qu'on pose côte à côte.
 *
 * Une section YOOtheme ne peut pas en contenir une autre : le plateau ne peut
 * donc pas être un élément du builder. C'est le script qui, à l'exécution,
 * repère les sections voisines qui se déclarent d'un même parcours, fabrique le
 * plateau autour d'elles et les y place. D'où le réglage : il ne dit pas
 * « cette section défile », il dit « cette section se pose là ».
 *
 * ── Le défilement n'est jamais détourné ──────────────────────────────────
 *
 * Comme pour le défilement latéral : on ne touche ni à la molette, ni à
 * scrollTop. Le parcours devient une zone verticale plus haute que l'écran, on
 * y colle un hublot, et la position de la caméra sur le plateau se déduit de la
 * progression de cette zone. Le clavier, l'inertie du trackpad, la barre de
 * défilement et le geste tactile restent ceux du navigateur.
 *
 * ── Sans script, la page reste la page ───────────────────────────────────
 *
 * Aucune règle CSS n'est posée sur les sections tant que le script n'a pas
 * monté le plateau. Script absent, script en erreur, écran trop étroit,
 * visiteur qui demande moins d'animations : dans les quatre cas les sections
 * restent empilées dans l'ordre, et la page fonctionne. Le repli n'est pas une
 * version dégradée, c'est la page normale.
 *
 * ── Pourquoi le mouvement réduit coupe le parcours ───────────────────────
 *
 * Le mode épinglé de wf-section-scroll reste actif sous prefers-reduced-motion
 * (c'est une mise en page, pas une décoration) mais sans glissement résiduel.
 * Ici c'est différent : une caméra qui se déplace sur deux axes est exactement
 * ce qui déclenche les troubles vestibulaires. Le parcours est donc désactivé
 * pour ces visiteurs, et la page redevient une colonne — complète et lisible.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wf_rb_fields' ) ) :
/**
 * Champs ajoutés à la Section.
 *
 * Les réglages du parcours (longueur, souplesse, seuil, barre) vivent sur la
 * section qui le démarre : un parcours, un jeu de réglages.
 *
 * @return array<string, array<string, mixed>>
 */
function wf_rb_fields() {
	return array(
		'wf_rb' => array(
			'label'   => 'Parcours en escalier',
			'type'    => 'select',
			'options' => array(
				'Non'                          => '',
				'Démarre le parcours'          => 'start',
				'À droite de la précédente'    => 'right',
				'En dessous de la précédente'  => 'down',
			),
			'description' => "Les sections d'un même parcours doivent se suivre immédiatement dans la page. La première le démarre, les suivantes disent où elles se posent.",
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
			'description' => "La hauteur est toujours d'un écran : c'est elle qui aligne les lignes du parcours.",
		),
		'wf_rb_len' => array(
			'label'  => 'Longueur du parcours (%)',
			'type'   => 'number',
			'attrs'  => array( 'min' => 50, 'max' => 400, 'step' => 10 ),
			'source' => true,
			'enable' => "wf_rb === 'start'",
			'description' => "100 = un pixel de défilement pour un pixel parcouru sur le plateau. Plus haut, la caméra avance plus lentement.",
		),
		'wf_rb_ease' => array(
			'label'  => 'Souplesse du mouvement',
			'type'   => 'number',
			'attrs'  => array( 'min' => 0, 'max' => 100, 'step' => 5 ),
			'source' => true,
			'enable' => "wf_rb === 'start'",
			'description' => "0 : la caméra suit le défilement au pixel. 100 : elle glisse encore un instant après l'arrêt.",
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
			'text'   => 'Afficher une barre de progression',
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
 * script, et seulement quand le plateau est monté.
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
/* Structure du plateau. Rien de tout cela n'existe avant que le script ne
   monte le parcours : ces classes sont posées par lui. */
.wf-rb-stage{position:relative;}
.wf-rb-view{position:sticky;top:0;height:100vh;overflow:hidden;}
.wf-rb-board{position:relative;will-change:transform;}
/* !important sur la marge : une section peut porter la sienne via le champ
   « Marge », et la marge d'un élément absolu décale sa position. */
.wf-rb-cell{position:absolute;margin:0 !important;box-sizing:border-box;}
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
			console.warn('WF parcours en escalier : ' + raison, sec);
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
	 * « en dessous ». « Immédiatement » est vérifié, pas supposé : une section
	 * ordinaire glissée au milieu casserait le plateau en silence.
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
				crier(sec, 'aucune section « Démarre le parcours » avant celle-ci');
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

	function activer(g){
		var cases = g.cases, roles = g.roles, n = cases.length;
		var tete = cases[0];
		var cs = getComputedStyle(tete);
		var len     = nombre(cs, '--wf-rb-len', 100) / 100;
		var douceur = nombre(cs, '--wf-rb-ease', 70);
		var seuil   = nombre(cs, '--wf-rb-bp', 960);
		var barre   = texte(cs, '--wf-rb-bar');
		var k = 1 - (douceur / 100) * 0.86;

		var parent = tete.parentElement;
		var ancre  = document.createComment('wf-rb');
		parent.insertBefore(ancre, tete);

		var stage = null, vue = null, board = null, io = null;
		var actif = false, visible = true, raf = 0;
		var larg = [], posX = [], posY = [];
		var course = 0, hVue = 0;
		var cx = 0, cy = 0, vx = 0, vy = 0;

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
			if (barre) { stage.setAttribute('data-bar', ''); }
			for (var i = 0; i < n; i++) {
				cases[i].classList.add('wf-rb-cell');
				board.appendChild(cases[i]);
			}

			// L'observateur suit le plateau lui-même. Première version : il
			// suivait « ce qui vient après l'ancre », qui se trouve être une
			// balise <style> — un élément sans boîte, donc jamais visible. La
			// caméra ne repeignait jamais.
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
				c.style.top = '';
				c.style.width = '';
				c.style.height = '';
				parent.insertBefore(c, ancre);
			}
			if (stage && stage.parentElement) { stage.parentElement.removeChild(stage); }
			stage = vue = board = null;
		}

		/** Largeur naturelle d'une case, bornée : « selon le contenu ». */
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
			var i;

			for (i = 0; i < n; i++) {
				var brut = texte(getComputedStyle(cases[i]), '--wf-rb-w');
				larg[i] = ('auto' === brut)
					? auto(cases[i], vp)
					: Math.round(vp * (parseFloat(brut) || 100) / 100);
			}

			// Le chemin : à droite on avance de la largeur de la case
			// précédente, en dessous on descend d'un écran. La colonne ne
			// change pas quand on descend — c'est tout l'escalier.
			var x = 0, y = 0;
			posX[0] = 0; posY[0] = 0;
			course = 0;
			for (i = 1; i < n; i++) {
				if ('down' === roles[i]) { y += hVue; course += hVue; }
				else { x += larg[i - 1]; course += larg[i - 1]; }
				posX[i] = x;
				posY[i] = y;
			}

			var maxX = 0, maxY = 0;
			for (i = 0; i < n; i++) {
				cases[i].style.left = posX[i] + 'px';
				cases[i].style.top = posY[i] + 'px';
				cases[i].style.width = larg[i] + 'px';
				cases[i].style.height = hVue + 'px';
				if (posX[i] + larg[i] > maxX) { maxX = posX[i] + larg[i]; }
				if (posY[i] + hVue > maxY) { maxY = posY[i] + hVue; }
			}
			board.style.width = maxX + 'px';
			board.style.height = maxY + 'px';

			stage.style.height = (hVue + Math.round(course * len)) + 'px';
			cx = 0; cy = 0; vx = 0; vy = 0;
		}

		/**
		 * Lue en direct sur la position du plateau à l'écran, et pas sur des
		 * bornes calculées une fois pour toutes : une image qui arrive en
		 * retard, une police qui se substitue, un autre parcours qui se monte
		 * au-dessus — tout cela déplace le plateau, et des bornes gardées en
		 * mémoire deviennent fausses sans prévenir.
		 */
		function progression(){
			var total = stage.offsetHeight - hVue;
			if (total < 1) { return 0; }
			var p = -stage.getBoundingClientRect().top / total;
			return p < 0 ? 0 : (p > 1 ? 1 : p);
		}

		/** Où est la caméra après avoir parcouru d pixels de chemin. */
		function camera(d){
			var x = 0, y = 0;
			for (var i = 1; i < n; i++) {
				var pas = ('down' === roles[i]) ? hVue : larg[i - 1];
				if (d <= pas) {
					if ('down' === roles[i]) { y += d; } else { x += d; }
					return [x, y];
				}
				d -= pas;
				if ('down' === roles[i]) { y += pas; } else { x += pas; }
			}
			return [x, y];
		}

		function peindre(){
			if (!actif) { return; }
			var p = progression();
			var c = camera(course * p);
			vx = c[0]; vy = c[1];
			cx += (vx - cx) * k;
			cy += (vy - cy) * k;
			if (Math.abs(vx - cx) < 0.15) { cx = vx; }
			if (Math.abs(vy - cy) < 0.15) { cy = vy; }
			board.style.transform = 'translate3d(' + (-cx).toFixed(2) + 'px,' + (-cy).toFixed(2) + 'px,0)';
			stage.style.setProperty('--wf-rb-progress', p.toFixed(4));
		}

		function boucle(){
			if (!actif) { raf = 0; return; }
			peindre();
			if (!visible) { raf = 0; return; }
			if (cx === vx && cy === vy) { raf = 0; return; }
			raf = requestAnimationFrame(boucle);
		}
		function relancer(){
			if (raf || !visible || !actif) { return; }
			raf = requestAnimationFrame(boucle);
		}

		var calme = false;
		if (window.matchMedia) {
			// Une caméra qui bouge sur deux axes est précisément ce qui rend
			// malade. Ici on ne réduit pas le mouvement : on rend la page.
			if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { calme = true; }
		}

		function auSeuil(){
			return !calme && window.innerWidth >= seuil;
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

		if (auSeuil()) { allumer(); }

		window.addEventListener('scroll', relancer, { passive: true });

		var tmo = 0;
		window.addEventListener('resize', function(){
			clearTimeout(tmo);
			tmo = setTimeout(function(){
				if (!auSeuil()) { eteindre(); return; }
				if (!actif) { allumer(); return; }
				mesurer();
				peindre();
			}, 150);
		});
	}

	function demarrer(){
		var groupes = grouper();
		for (var i = 0; i < groupes.length; i++) {
			if (groupes[i].cases.length < 2) {
				crier(groupes[i].cases[0], 'un parcours demande au moins deux sections');
				continue;
			}
			activer(groupes[i]);
		}
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
				'label'   => 'Parcours en escalier',
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
