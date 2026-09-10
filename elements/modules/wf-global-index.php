<?php
/**
 * WeFrame – Annuaire global des éléments
 *
 * Ajoute une entrée « WeFrame » au menu principal du customizer, avec trois
 * vues : l'inventaire complet, l'en-tête et le dialog, la navigation et les
 * mégamenus. Elle répond à la question « où est mon élément ? » quand on ne
 * sait plus dans quelle page, quel widget ou quel mégamenu il vit.
 *
 * ── Le problème qu'il règle ──────────────────────────────────────────────
 *
 * Nos éléments ne sont pas « quelque part dans le builder » : ils sont
 * éparpillés dans CINQ contextes d'édition, chacun derrière une porte
 * différente du menu.
 *
 *   Pages            post_content, dans un commentaire HTML
 *   Templates        wp_options.yootheme -> templates
 *   Widgets          wp_options.widget_builder
 *   Items de mégamenu  theme_mod « config » -> menu.items[].content
 *   Pied de page     theme_mod « config » -> footer.content
 *
 * L'annuaire les relit tous et dit, pour chaque élément, où il se trouve.
 *
 * ── Ce qu'il fait, et ce qu'il ne fait pas ───────────────────────────────
 *
 * Un clic amène à la BONNE PORTE, en une fois : le pied de page s'ouvre
 * directement, un item de mégamenu ouvre son menu, un widget ouvre la liste
 * des widgets, une page ouvre la liste des pages. Il n'entre pas dans
 * l'élément lui-même : depuis l'extérieur d'un builder, aucun point d'entrée
 * public ne permet d'ouvrir la page X et d'y sélectionner un nœud. C'est le
 * panneau contextuel (wf-builder-index.php) qui prend le relais une fois la
 * mise en page ouverte.
 *
 * ── Aucune modification du cœur ──────────────────────────────────────────
 *
 * L'entrée de menu est une section de config, comme « Style » dans
 * packages/styler/config/customizer.php. Les boutons qui sautent vers les
 * réglages natifs du dialog sont des champs « button-panel », comme
 * dialog._offcanvas dans packages/theme/config/customizer.php — donc du PHP,
 * sans une ligne de JavaScript. Seules les listes sont un composant, déclaré
 * dans window.$fields.
 *
 * Relevé dans customizer.js : l'application fournit à la barre latérale
 * { ...panels, ...sections } — sections et panneaux vivent dans la même table.
 * Un « button-panel » peut donc viser aussi bien « dialog-offcanvas » (un
 * panneau) que « wordpress-widgets » (une section).
 *
 * ── Le coût ──────────────────────────────────────────────────────────────
 *
 * Le balayage ne tourne QUE dans le customizer, jamais sur le site, et son
 * résultat est mis en cache. La requête des pages est bornée et ne retient que
 * les contenus qui portent la marque d'un layout.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WF_GIDX_CACHE' ) ) {
	define( 'WF_GIDX_CACHE', 'wf_gidx_v1' );
}

if ( ! function_exists( 'wf_gidx_reglages' ) ) :
/**
 * Réglages et libellés. Sortis du JavaScript pour rester traduisibles.
 *
 * @return array<string, mixed>
 */
function wf_gidx_reglages() {
	return array(
		'prefixes'  => array( 'wf_' ),
		'groupes'   => array( 'WeFrame' ),
		'pages_max' => 300,
		'cache_min' => 10,
		'libelles'  => array(
			'vide'    => 'Aucun élément WeFrame trouvé.',
			'filtre'  => 'Filtrer…',
			'aller'   => 'Ouvrir',
			'rien'    => 'Rien dans cette zone.',
			'total'   => 'éléments au total',
			'unique'  => 'élément au total',
		),
	);
}
endif;

if ( ! function_exists( 'wf_gidx_titres' ) ) :
/**
 * Table « nom interne -> titre, groupe, icône », lue dans les element.json du
 * plugin. C'est la seule source qui donne des noms lisibles hors du builder :
 * en dehors d'une mise en page ouverte, Builder.types n'existe pas côté
 * navigateur.
 *
 * @return array<string, array<string, string>>
 */
function wf_gidx_titres() {
	static $table = null;
	if ( null !== $table ) { return $table; }

	$table = array();
	$dir   = __DIR__ . '/element';
	if ( ! is_dir( $dir ) ) { return $table; }

	$reglages = wf_gidx_reglages();
	$url      = '';
	if ( class_exists( 'WF_YOOtheme_Elements' ) ) {
		$url = WF_YOOtheme_Elements::getInstance()->url() . '/modules/element/';
	}

	foreach ( (array) glob( $dir . '/*/element.json' ) as $fichier ) {
		$json = json_decode( (string) file_get_contents( $fichier ), true );
		if ( ! is_array( $json ) || empty( $json['name'] ) ) { continue; }

		$nom    = (string) $json['name'];
		$groupe = (string) ( $json['group'] ?? '' );
		if ( ! wf_gidx_est_wf( $nom, $groupe ) ) { continue; }

		$dossier = basename( dirname( $fichier ) );
		$table[ $nom ] = array(
			'titre'  => (string) ( $json['title'] ?? $nom ),
			'groupe' => $groupe,
			'icone'  => $url ? $url . $dossier . '/images/ic30.svg' : '',
			// Un item (« Photo », « Carte »…) n'est pas un élément qu'on va
			// chercher : il n'a de sens que dans son parent. Il reste dans la
			// table pour ne pas être pris pour un type inconnu, mais il n'est
			// pas listé.
			'liste'  => ! empty( $json['element'] ),
		);
	}

	return $table;
}
endif;

if ( ! function_exists( 'wf_gidx_est_wf' ) ) :
/**
 * Le groupe d'abord — il survit à un renommage de dossier —, le préfixe du nom
 * interne en repli.
 */
function wf_gidx_est_wf( $nom, $groupe = '' ) {
	$r = wf_gidx_reglages();
	foreach ( $r['prefixes'] as $p ) {
		if ( 0 === strpos( $nom, $p ) ) { return true; }
	}
	foreach ( $r['groupes'] as $g ) {
		if ( '' !== $groupe && 0 === strpos( $groupe, $g ) ) { return true; }
	}
	return false;
}
endif;

if ( ! function_exists( 'wf_gidx_extraire' ) ) :
/**
 * Relève les éléments WeFrame d'une mise en page, avec leur emplacement.
 *
 * Même règle que le panneau contextuel : les deux ancêtres les plus EXTÉRIEURS,
 * une fois écartés ceux qui ne situent rien. Ce sont eux qui distinguent deux
 * éléments, pas la ligne et la colonne.
 *
 * @param mixed $layout Nœud décodé, ou JSON.
 * @return array<int, array<string, string>>
 */
function wf_gidx_extraire( $layout ) {
	if ( is_string( $layout ) ) {
		$layout = json_decode( $layout, true );
	}
	if ( ! is_array( $layout ) ) { return array(); }

	$titres  = wf_gidx_titres();
	$muets   = array( 'layout' => 1, 'fragment' => 1, 'column' => 1 );
	$trouves = array();

	$descendre = function ( $noeud, array $chemin ) use ( &$descendre, &$trouves, $titres, $muets ) {
		if ( ! is_array( $noeud ) ) { return; }

		$type = (string) ( $noeud['type'] ?? '' );
		$ou   = implode( ' › ', $chemin );

		if ( '' !== $type && isset( $titres[ $type ] ) ) {
			// Les items ne sont pas listés : « Photo », « Carte », « Mot » n'ont
			// de sens que dans leur parent, et on ne va pas les chercher.
			if ( ! empty( $titres[ $type ]['liste'] ) ) {
				$trouves[] = array(
					'type'  => $type,
					'titre' => $titres[ $type ]['titre'],
					'icone' => $titres[ $type ]['icone'],
					'ou'    => $ou,
				);
			}
		} elseif ( '' !== $type && wf_gidx_est_wf( $type ) ) {
			// Un élément WeFrame dont la définition a disparu du plugin : on le
			// signale plutôt que de le taire, c'est justement ce qu'on veut voir.
			$trouves[] = array(
				'type'  => $type,
				'titre' => $type,
				'icone' => '',
				'ou'    => $ou,
				'perdu' => '1',
			);
		}

		$enfants = $noeud['children'] ?? array();
		if ( ! is_array( $enfants ) ) { return; }

		foreach ( array_values( $enfants ) as $rang => $enfant ) {
			$suite = $chemin;
			$te    = (string) ( $enfant['type'] ?? '' );

			// Seule la STRUCTURE situe : un élément ne fait pas partie de son
			// propre emplacement. Sans ce filtre, un élément posé directement
			// dans une section s'annonçait « Section 1 › Panneau ++ 1 ».
			$structurel = ( '' !== $te ) && ! isset( $muets[ $te ] )
				&& ! isset( $titres[ $te ] ) && ! wf_gidx_est_wf( $te );

			if ( $structurel && count( $suite ) < 2 ) {
				$suite[] = wf_gidx_nom_natif( $te ) . ' ' . ( $rang + 1 );
			}
			$descendre( $enfant, $suite );
		}
	};

	$descendre( $layout, array() );
	return $trouves;
}
endif;

if ( ! function_exists( 'wf_gidx_nom_natif' ) ) :
/** Les quelques types de structure de YOOtheme, en français. */
function wf_gidx_nom_natif( $type ) {
	$noms = array(
		'section' => 'Section',
		'row'     => 'Ligne',
		'grid'    => 'Grille',
		'column'  => 'Colonne',
	);
	return $noms[ $type ] ?? ucfirst( $type );
}
endif;

if ( ! function_exists( 'wf_gidx_layout_page' ) ) :
/**
 * Le layout d'une page vit dans post_content, à l'intérieur d'un commentaire
 * HTML. PostHelper::matchContent est l'API de YOOtheme pour l'en extraire —
 * autant s'en servir plutôt que de refaire son motif.
 */
function wf_gidx_layout_page( $contenu ) {
	$classe = '\\YOOtheme\\Builder\\Wordpress\\PostHelper';
	if ( class_exists( $classe ) && method_exists( $classe, 'matchContent' ) ) {
		return $classe::matchContent( $contenu );
	}
	// Repli : le motif public de la même classe.
	return preg_match( '/<!--\s?(\{.*})\s?-->/', (string) $contenu, $m ) ? $m[1] : null;
}
endif;

if ( ! function_exists( 'wf_gidx_config_theme' ) ) :
/**
 * La configuration du thème : menus, pied de page, dialog. Écrite par le
 * customizer avec set_theme_mod('config', json) — voir
 * packages/theme-wordpress/src/CustomizerController::save.
 *
 * @return array<string, mixed>
 */
function wf_gidx_config_theme() {
	static $valeurs = null;
	if ( null !== $valeurs ) { return $valeurs; }

	$brut    = get_theme_mod( 'config' );
	$valeurs = is_string( $brut ) ? json_decode( $brut, true ) : array();
	if ( ! is_array( $valeurs ) ) { $valeurs = array(); }

	return $valeurs;
}
endif;

if ( ! function_exists( 'wf_gidx_scan' ) ) :
/**
 * Le balayage des cinq contextes. Mis en cache : il ne tourne que dans le
 * customizer, mais il n'a pas à retourner en base à chaque ouverture d'un
 * panneau.
 *
 * @param bool $forcer Ignorer le cache.
 * @return array<string, mixed>
 */
function wf_gidx_scan( $forcer = false ) {
	$reglages = wf_gidx_reglages();

	if ( ! $forcer ) {
		$cache = get_transient( WF_GIDX_CACHE );
		if ( is_array( $cache ) ) { return $cache; }
	}

	$contextes = array();

	// ── 1. Pages et articles ────────────────────────────────────────────
	global $wpdb;
	$lignes = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, post_title, post_type, post_content
			   FROM {$wpdb->posts}
			  WHERE post_status NOT IN ('trash','auto-draft','inherit')
			    AND post_content LIKE %s
			  ORDER BY post_modified DESC
			  LIMIT %d",
			'%<!--%{%',
			(int) $reglages['pages_max']
		)
	);

	foreach ( (array) $lignes as $ligne ) {
		$json = wf_gidx_layout_page( $ligne->post_content );
		if ( ! $json ) { continue; }
		$elements = wf_gidx_extraire( $json );
		if ( ! $elements ) { continue; }

		$contextes[] = array(
			'genre'    => 'page',
			'titre'    => $ligne->post_title ? $ligne->post_title : '(sans titre)',
			'sous'     => 'Page · ' . $ligne->post_type,
			'zone'     => '',
			'cible'    => 'builder-pages',
			'elements' => $elements,
		);
	}

	// ── 2. Templates ────────────────────────────────────────────────────
	$templates = array();
	$option    = get_option( 'yootheme' );
	if ( is_string( $option ) ) {
		$decode = json_decode( $option, true );
		if ( is_array( $decode ) && isset( $decode['templates'] ) && is_array( $decode['templates'] ) ) {
			$templates = $decode['templates'];
		}
	}
	foreach ( $templates as $tpl ) {
		if ( ! is_array( $tpl ) ) { continue; }
		$elements = wf_gidx_extraire( $tpl['content'] ?? null );
		if ( ! $elements ) { continue; }
		$contextes[] = array(
			'genre'    => 'template',
			'titre'    => (string) ( $tpl['name'] ?? 'Template' ),
			'sous'     => 'Template',
			'zone'     => '',
			'cible'    => 'builder-templates',
			'elements' => $elements,
		);
	}

	// ── 3. Widgets, avec la zone où ils sont posés ──────────────────────
	$zones     = wp_get_sidebars_widgets();
	$ou_widget = array();
	foreach ( (array) $zones as $zone => $ids ) {
		if ( 'wp_inactive_widgets' === $zone ) { continue; }
		foreach ( (array) $ids as $id ) { $ou_widget[ $id ] = (string) $zone; }
	}

	$widgets = get_option( 'widget_builder' );
	if ( is_array( $widgets ) ) {
		foreach ( $widgets as $num => $inst ) {
			if ( ! is_array( $inst ) ) { continue; }
			$elements = wf_gidx_extraire( $inst['content'] ?? null );
			if ( ! $elements ) { continue; }

			$id   = 'builder-' . $num;
			$zone = $ou_widget[ $id ] ?? '';
			$contextes[] = array(
				'genre'    => 'widget',
				'titre'    => (string) ( $inst['title'] ?? '' ) ?: 'Widget ' . $num,
				'sous'     => $zone ? 'Widget · ' . $zone : 'Widget · non assigné',
				'zone'     => $zone,
				'cible'    => 'wordpress-widgets',
				'elements' => $elements,
			);
		}
	}

	// ── 4. Items de mégamenu ────────────────────────────────────────────
	$config = wf_gidx_config_theme();
	$items  = $config['menu']['items'] ?? array();
	if ( is_array( $items ) ) {
		foreach ( $items as $id => $item ) {
			if ( ! is_array( $item ) ) { continue; }
			$elements = wf_gidx_extraire( $item['content'] ?? null );
			if ( ! $elements ) { continue; }

			$objet = is_numeric( $id ) ? get_post( (int) $id ) : null;
			$contextes[] = array(
				'genre'    => 'menuitem',
				'titre'    => $objet && $objet->post_title ? $objet->post_title : 'Item ' . $id,
				'sous'     => 'Item de mégamenu',
				'zone'     => 'navbar',
				'cible'    => 'wordpress-menus',
				'elements' => $elements,
			);
		}
	}

	// ── 5. Pied de page ─────────────────────────────────────────────────
	$pied = wf_gidx_extraire( $config['footer']['content'] ?? null );
	if ( $pied ) {
		$contextes[] = array(
			'genre'    => 'footer',
			'titre'    => 'Pied de page',
			'sous'     => 'Mise en page du pied',
			'zone'     => '',
			'cible'    => 'footer-builder',
			'elements' => $pied,
		);
	}

	$total = 0;
	foreach ( $contextes as $c ) { $total += count( $c['elements'] ); }

	$resultat = array(
		'contextes' => $contextes,
		'total'     => $total,
		'genere'    => current_time( 'H:i' ),
		'dialog'    => wf_gidx_dialog( $config ),
	);

	set_transient( WF_GIDX_CACHE, $resultat, max( 1, (int) $reglages['cache_min'] ) * MINUTE_IN_SECONDS );

	return $resultat;
}
endif;

if ( ! function_exists( 'wf_gidx_dialog' ) ) :
/**
 * De quoi est fait le dialog, en clair : sa forme, son déclencheur, et les
 * zones de widgets qui l'alimentent.
 *
 * @param array $config Configuration du thème.
 * @return array<string, mixed>
 */
function wf_gidx_dialog( array $config ) {
	$layout = (string) ( $config['dialog']['layout'] ?? 'offcanvas-top' );
	$forme  = 'offcanvas';
	if ( 0 === strpos( $layout, 'dropbar' ) ) { $forme = 'dropbar'; }
	if ( 0 === strpos( $layout, 'modal' ) ) { $forme = 'modal'; }

	return array(
		'layout' => $layout,
		'forme'  => $forme,
		'toggle' => (string) ( $config['dialog']['toggle'] ?? '' ),
		'zones'  => array( 'dialog', 'dialog-push', 'dialog-mobile', 'dialog-mobile-push' ),
	);
}
endif;

if ( ! function_exists( 'wf_gidx_customizer' ) ) :
/**
 * L'entrée de menu et ses trois panneaux.
 *
 * La priorité 45 place « WeFrame » entre Widgets (40) et Paramètres (60) :
 * c'est un outil transversal, pas une zone de contenu.
 *
 * @return array<string, mixed>
 */
function wf_gidx_customizer() {
	$l = wf_gidx_reglages()['libelles'];

	return array(
		'sections' => array(
			'weframe' => array(
				'title'    => 'WeFrame',
				'priority' => 45,
				'width'    => 380,
				'fields'   => array(
					'weframe' => array(
						'type'  => 'menu',
						'items' => array(
							'weframe-index'  => 'Où sont mes éléments ?',
							'weframe-dialog' => 'En-tête & Dialog',
							'weframe-navbar' => 'Navigation & mégamenus',
						),
					),
				),
			),
		),

		'panels' => array(

			'weframe-index' => array(
				'title'  => 'Où sont mes éléments ?',
				'width'  => 420,
				'fields' => array(
					'wf_gidx_tout' => array( 'type' => 'wf-global-index' ),
				),
			),

			// Les boutons ci-dessous sont des champs natifs « button-panel » :
			// ils ouvrent un panneau déclaré ailleurs dans la config, sans une
			// ligne de JavaScript. Les expressions « show » sont celles que
			// YOOtheme emploie lui-même pour ces mêmes panneaux.
			'weframe-dialog' => array(
				'title'  => 'En-tête & Dialog',
				'width'  => 420,
				'fields' => array(
					'wf_dlg_dropbar' => array(
						'type'  => 'button-panel',
						'text'  => 'Réglages du dropbar',
						'panel' => 'dialog-dropbar',
						'show'  => '$match(dialog.layout, \'^dropbar\')',
					),
					'wf_dlg_offcanvas' => array(
						'type'  => 'button-panel',
						'text'  => "Réglages de l'offcanvas",
						'panel' => 'dialog-offcanvas',
						'show'  => '$match(dialog.layout, \'^offcanvas\')',
					),
					'wf_dlg_modal' => array(
						'type'  => 'button-panel',
						'text'  => 'Réglages du modal',
						'panel' => 'dialog-modal',
						'show'  => '$match(dialog.layout, \'^modal\')',
					),
					'wf_dlg_header' => array(
						'type'  => 'button-panel',
						'text'  => "Réglages de l'en-tête",
						'panel' => 'header',
					),
					'wf_dlg_widgets' => array(
						'type'  => 'button-panel',
						'text'  => 'Gérer les widgets',
						'panel' => 'wordpress-widgets',
					),
					'wf_gidx_dialog' => array(
						'type' => 'wf-global-index',
						'zone' => 'dialog',
					),
				),
			),

			'weframe-navbar' => array(
				'title'  => 'Navigation & mégamenus',
				'width'  => 420,
				'fields' => array(
					'wf_nav_menus' => array(
						'type'  => 'button-panel',
						'text'  => 'Gérer les menus',
						'panel' => 'wordpress-menus',
					),
					'wf_nav_header' => array(
						'type'  => 'button-panel',
						'text'  => "Réglages de l'en-tête",
						'panel' => 'header',
					),
					'wf_nav_mobile' => array(
						'type'  => 'button-panel',
						'text'  => 'Réglages mobile',
						'panel' => 'mobile',
					),
					'wf_gidx_navbar' => array(
						'type' => 'wf-global-index',
						'zone' => 'navbar',
					),
				),
			),
		),
	);
}
endif;

if ( ! function_exists( 'wf_gidx_script' ) ) :
/**
 * Le composant de liste, enregistré comme type de champ « wf-global-index ».
 *
 * Vue est livré en build runtime seul : pas de « template », uniquement render.
 *
 * @return string
 */
function wf_gidx_script() {
	$donnees = 'window.wfGlobalIndex = ' . wp_json_encode(
		array(
			'index'    => wf_gidx_scan(),
			'libelles' => wf_gidx_reglages()['libelles'],
		)
	) . ';';

	$js = <<<'JS'
(function () {
	'use strict';

	function crier(raison) {
		if (window.console) { console.warn('WF annuaire : ' + raison); }
	}

	function donnees() {
		var d = window.wfGlobalIndex || {};
		return {
			index: d.index || { contextes: [], total: 0 },
			mots: d.libelles || {}
		};
	}

	/* Les genres de contexte et ce qu'un clic doit ouvrir. La cible est le nom
	   d'une section ou d'un panneau de la config : l'application fournit à la
	   barre latérale { ...panels, ...sections }, donc un nom suffit. */
	var Champ = {
		name: 'FieldWfGlobalIndex',

		/* Le rendu des champs passe « field » et « values » en attributs :
		   sans props déclarées ils tomberaient dans $attrs, et field.zone
		   serait introuvable. On les déclare plutôt que d'aller chercher le
		   composant de base par une voie interne. */
		props: {
			field: { type: Object, default: function () { return {}; } },
			values: { type: Object, default: function () { return {}; } }
		},

		data: function () {
			return { filtre: '' };
		},

		computed: {
			mots: function () {
				return donnees().mots;
			},
			zone: function () {
				return (this.field && this.field.zone) || '';
			},
			contextes: function () {
				var d = donnees().index;
				var zone = this.zone;
				var f = String(this.filtre || '').trim().toLowerCase();

				return (d.contextes || []).filter(function (c) {
					if (zone && String(c.zone || '').indexOf(zone) !== 0) { return false; }
					if (!f) { return true; }
					if ((c.titre + ' ' + c.sous).toLowerCase().indexOf(f) !== -1) { return true; }
					return (c.elements || []).some(function (e) {
						return (e.titre + ' ' + e.ou).toLowerCase().indexOf(f) !== -1;
					});
				});
			},
			total: function () {
				return this.contextes.reduce(function (n, c) {
					return n + (c.elements || []).length;
				}, 0);
			}
		},

		methods: {
			aller: function (c, ev) {
				ev.preventDefault();
				if (!c.cible) { return; }
				this.$trigger('openPanel', [c.cible]);
			},
			/* Les libellés viennent du PHP, mais chaque appel porte son propre
			   repli : si la charge de données manquait, le panneau resterait
			   muet — un panneau vide sans explication est pire qu'un panneau
			   qui dit qu'il est vide. */
			mot: function (cle, secours) {
				return this.mots[cle] || secours;
			}
		},

		render: function (h) {
			var self = this;
			var d = donnees().index;

			if (!d.contextes || !d.contextes.length) {
				return h('div', { class: 'uk-panel' }, [
					h('p', { class: 'uk-text-meta' }, this.mot('vide', 'Aucun élément WeFrame trouvé.'))
				]);
			}

			var champ = h('div', { class: 'uk-margin-small' }, [
				h('input', {
					class: 'uk-input',
					attrs: { type: 'search', placeholder: this.mot('filtre', 'Filtrer…') },
					domProps: { value: this.filtre },
					on: { input: function (ev) { self.filtre = ev.target.value; } }
				})
			]);

			var blocs = this.contextes.map(function (c, rang) {
				var lignes = (c.elements || []).map(function (e, i) {
					var visuel = e.icone
						? h('img', { class: 'uk-preserve-width', attrs: { src: e.icone, width: 18, height: 18, alt: '' } })
						: h('span', {}, '▪');
					var etiquettes = [
						h('div', { class: 'uk-text-truncate' + (e.perdu ? ' uk-text-danger' : '') }, e.titre)
					];
					if (e.ou) {
						etiquettes.push(h('div', { class: 'uk-text-meta uk-text-truncate' }, e.ou));
					}
					return h('li', { key: rang + '-' + i, class: 'uk-flex uk-flex-middle' }, [
						h('div', { class: 'uk-width-auto uk-margin-small-right' }, [visuel]),
						h('div', { class: 'uk-width-expand' }, etiquettes)
					]);
				});

				var entete = h('a', {
					attrs: { href: '', title: self.mot('aller', 'Ouvrir') },
					class: 'uk-flex uk-flex-middle uk-flex-between',
					on: { click: function (ev) { self.aller(c, ev); } }
				}, [
					h('span', { class: 'uk-text-bold uk-text-truncate' }, c.titre),
					h('span', { class: 'uk-text-meta uk-margin-small-left' }, String((c.elements || []).length))
				]);

				return h('div', { key: 'c' + rang, class: 'uk-margin-small' }, [
					entete,
					h('div', { class: 'uk-text-meta' }, c.sous),
					h('ul', { class: 'uk-list uk-list-collapse uk-margin-small-top' }, lignes)
				]);
			});

			if (!blocs.length) {
				blocs = [h('p', { class: 'uk-text-meta' }, this.mot('rien', 'Rien dans cette zone.'))];
			}

			return h('div', { class: 'uk-panel' }, [
				champ,
				h('div', {}, blocs),
				h('p', { class: 'uk-text-meta uk-margin-small-top' },
					this.total + ' ' + (this.total > 1 ? this.mot('total', 'éléments au total') : this.mot('unique', 'élément au total')))
			]);
		}
	};

	var essais = 0;
	(function attendre() {
		if (window.$fields) {
			// Le registre associe un type de champ à « Field » + son nom en
			// PascalCase : button-panel -> FieldButtonPanel. Notre champ
			// « wf-global-index » attend donc FieldWfGlobalIndex.
			window.$fields.FieldWfGlobalIndex = Champ;
			window.WFAnnuaire = { champ: Champ, donnees: donnees };
			return;
		}
		if (++essais > 600) {
			crier('window.$fields n\'est jamais apparu : le champ ne sera pas rendu.');
			return;
		}
		requestAnimationFrame(attendre);
	})();
})();
JS;

	return $donnees . "\n" . $js;
}
endif;

if ( ! class_exists( 'WF_Global_Index_Listener' ) ) :
/**
 * Écouteur de « customizer.init » : la config de l'entrée de menu, puis le
 * script. Même forme que packages/theme/src/Listener/LoadCustomizerData.php.
 */
class WF_Global_Index_Listener {

	public \YOOtheme\Config $config;
	public \YOOtheme\Metadata $metadata;

	public function __construct( \YOOtheme\Config $config, \YOOtheme\Metadata $metadata ) {
		$this->config   = $config;
		$this->metadata = $metadata;
	}

	public function handle(): void {
		// Une purge à la demande, pour ne pas attendre l'expiration du cache
		// après avoir modifié une page : ?wf_refresh=1 sur l'URL du builder.
		if ( isset( $_GET['wf_refresh'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			delete_transient( WF_GIDX_CACHE );
		}

		$this->config->add( 'customizer', wf_gidx_customizer() );
		$this->metadata->set( 'script:wf-global-index', wf_gidx_script() );
	}
}
endif;

/**
 * Le cache tombe dès qu'une mise en page change : sauvegarde d'une page, d'un
 * widget, ou de la configuration du thème.
 */
if ( ! function_exists( 'wf_gidx_purger' ) ) :
function wf_gidx_purger() {
	delete_transient( WF_GIDX_CACHE );
}
endif;

add_action( 'save_post', 'wf_gidx_purger' );
add_action( 'update_option_widget_builder', 'wf_gidx_purger' );
add_action( 'update_option_yootheme', 'wf_gidx_purger' );
add_action( 'customize_save_after', 'wf_gidx_purger' );
