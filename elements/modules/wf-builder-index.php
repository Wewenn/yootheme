<?php
/**
 * WeFrame – Index des éléments dans le builder
 *
 * Ajoute au builder YOOtheme un panneau « Éléments WeFrame » qui liste les
 * éléments de la maison présents dans la mise en page ouverte, et permet de
 * sauter directement aux réglages de l'un d'eux — sans descendre dans l'arbre.
 *
 * ── Trois actions, exactement celles de l'arbre natif ─────────────────────
 *
 * Le panneau ne réinvente rien : il appelle les mêmes fonctions que les
 * vignettes de l'arbre du builder, relevées dans customizer.js :
 *
 *   clic     -> Builder.edit(node)                        (ouvre ses réglages)
 *   survol   -> trigger('hoverNode',  [node, Builder])    (le surligne)
 *   sortie   -> trigger('leaveNode',  [node, Builder])
 *   viseur   -> trigger('scrollNode', [node, Builder])    (amène à l'écran)
 *
 * ── Aucune modification du cœur ──────────────────────────────────────────
 *
 * Tout passe par les mécanismes que YOOtheme utilise pour lui-même :
 *
 *   - l'événement « customizer.init », comme packages/theme/bootstrap.php ;
 *   - Metadata::set('script:…', '…'), qui accepte un script en ligne — c'est
 *     ainsi que LoadConfigData pose window.yootheme.config ;
 *   - window.$fields, le seul objet que customizer.js expose sans condition.
 *
 * ── Trois contraintes relevées dans le code, qui expliquent l'écriture ───
 *
 * 1. Vue est livré en build RUNTIME SEUL : assets/admin/js/vue.js ne contient
 *    ni compileToFunctions ni parseHTML. Une option « template » serait
 *    silencieusement ignorée. Tout le rendu passe donc par render(h).
 *
 * 2. Un panneau poussé dans la barre latérale est un FRÈRE du builder, pas son
 *    enfant : la Sidebar empile ses panneaux côte à côte. Un inject('Builder')
 *    ne trouverait donc rien. YOOtheme lui-même contourne en passant le store
 *    en prop (voir editNode : props:{node, builder, values}) ; comme nous
 *    ouvrons le panneau depuis l'extérieur, nous n'avons pas cette prop : le
 *    store est retrouvé en parcourant l'arbre des composants.
 *
 * 3. Le bus d'événements n'est pas exposé. On emprunte donc, une seule fois,
 *    la méthode open() du champ « button-panel » : elle fait exactement
 *    ie.trigger('openPanel', descripteur) et n'utilise pas « this ». Une fois
 *    le panneau monté, notre composant a $trigger et n'en a plus besoin.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wf_bidx_config' ) ) :
/**
 * Réglages lus par le script. Sortis du JavaScript pour rester traduisibles et
 * modifiables sans toucher au moteur.
 *
 * @return array<string, mixed>
 */
function wf_bidx_config() {
	return array(
		// Un élément est « à nous » si son nom interne commence par l'un de
		// ces préfixes, ou si son groupe commence par l'une de ces étiquettes.
		'prefixes' => array( 'wf_' ),
		'groupes'  => array( 'WeFrame' ),
		'titre'    => 'Éléments WeFrame',
		'vide'     => 'Aucun élément WeFrame dans cette mise en page.',
		'absent'   => "Ouvre d'abord une page, un template, un widget ou un item de menu : l'index liste les éléments de la mise en page en cours d'édition.",
		'filtre'   => 'Filtrer…',
		'bouton'   => 'Éléments WeFrame',
		'viseur'   => "Amener à l'écran",
		'largeur'  => 340,
	);
}
endif;

if ( ! function_exists( 'wf_bidx_script' ) ) :
/**
 * Le script, imprimé en ligne dans le customizer.
 *
 * @return string
 */
function wf_bidx_script() {
	$config = 'window.wfIndexConfig = ' . wp_json_encode( wf_bidx_config() ) . ';';

	$js = <<<'JS'
(function () {
	'use strict';

	var CFG = window.wfIndexConfig || {};
	var NOM = 'wf-elements-index';

	function crier(raison, quoi) {
		if (window.console) {
			console.warn('WF index des éléments : ' + raison, quoi === undefined ? '' : quoi);
		}
	}

	/* ── Le pont vers le bus ──────────────────────────────────────────────
	   Seul window.$fields est exposé. La méthode open() du champ
	   « button-panel » fait ie.trigger('openPanel', descripteur) et n'utilise
	   pas « this » : on peut donc l'appeler détachée. C'est le seul chemin
	   public pour parler au bus depuis l'extérieur de l'application. */
	function ouvreur() {
		var reg = window.$fields;
		if (!reg) { return null; }
		var bp = reg.FieldButtonPanel;
		if (!bp) { return null; }
		var m = bp.methods || (bp.options && bp.options.methods);
		if (!m || typeof m.open !== 'function') { return null; }
		return m.open;
	}

	function ouvrirPanneau(descripteur) {
		var open = ouvreur();
		if (!open) {
			crier('le champ « button-panel » est introuvable dans window.$fields : le panneau ne peut pas s\'ouvrir. YOOtheme a peut-être changé de registre de champs.');
			return false;
		}
		open(descripteur);
		return true;
	}

	/* ── Retrouver le store Builder vivant ────────────────────────────────
	   Deux pistes, dans l'ordre : une prop « builder » (c'est ainsi que
	   YOOtheme passe le store aux panneaux de réglages d'un nœud), puis la
	   valeur fournie par provide('Builder'). La seconde lit _provided, un
	   interne de Vue 2 — stable, mais gardé : si la piste se ferme, le
	   panneau le dit au lieu de casser. */
	function estBuilder(o) {
		return !!o && typeof o === 'object' && typeof o.edit === 'function' && typeof o.type === 'function' && 'node' in o;
	}

	function trouverBuilder(vm) {
		if (!vm) { return null; }
		if (vm.$props && estBuilder(vm.$props.builder)) { return vm.$props.builder; }
		if (vm._provided && estBuilder(vm._provided.Builder)) { return vm._provided.Builder; }
		var enfants = vm.$children || [];
		for (var i = 0; i < enfants.length; i++) {
			var trouve = trouverBuilder(enfants[i]);
			if (trouve) { return trouve; }
		}
		return null;
	}

	/* ── Ce qui est « à nous » ────────────────────────────────────────────
	   Le groupe d'abord : il vient de element.json et survit à un renommage
	   de dossier. Le préfixe du nom interne sert de repli. */
	function estWeFrame(builder, type) {
		var i;
		var prefixes = CFG.prefixes || [];
		for (i = 0; i < prefixes.length; i++) {
			if (type.indexOf(prefixes[i]) === 0) { return true; }
		}
		var def = builder.types && builder.types[type];
		var groupe = def && def.group ? String(def.group) : '';
		var groupes = CFG.groupes || [];
		for (i = 0; i < groupes.length; i++) {
			if (groupe.indexOf(groupes[i]) === 0) { return true; }
		}
		return false;
	}

	/** Parcourt l'arbre de la mise en page et relève nos éléments, dans l'ordre. */
	function collecter(builder) {
		var trouves = [];
		if (!builder || !builder.node) { return trouves; }
		(function descendre(n) {
			if (!n) { return; }
			var type = String(n.type || '');
			if (type && estWeFrame(builder, type)) { trouves.push(n); }
			var enfants = n.children || [];
			for (var i = 0; i < enfants.length; i++) { descendre(enfants[i]); }
		})(builder.node);
		return trouves;
	}

	function titre(builder, node) {
		var def = builder.type(node) || {};
		return def.title || String(node.type || '');
	}

	/**
	 * « Section 2 › Ligne 1 » : les deux ancêtres les plus EXTÉRIEURS, une fois
	 * écartés ceux qui ne situent rien.
	 *
	 * Les deux derniers seraient la ligne et la colonne — c'est-à-dire les
	 * moins discriminants : deux éléments de sections différentes afficheraient
	 * le même emplacement. C'est la section qui situe, la colonne qui ne dit
	 * rien, et la mise en page qui est commune à tous.
	 */
	function emplacement(builder, node) {
		if (typeof builder.path !== 'function') { return ''; }
		var chemin;
		try { chemin = builder.path(node) || []; } catch (e) { return ''; }
		var muets = { layout: 1, fragment: 1, column: 1 };
		var etapes = [];
		for (var i = 0; i < chemin.length; i++) {
			var a = chemin[i];
			if (a === node) { continue; }
			var t = String(a.type || '');
			if (!t || muets[t]) { continue; }
			var def = builder.type(a) || {};
			var rang = typeof builder.index === 'function' ? builder.index(a) + 1 : 0;
			etapes.push((def.title || t) + (rang > 0 ? ' ' + rang : ''));
			if (etapes.length === 2) { break; }
		}
		return etapes.join(' › ');
	}

	/* ── Le panneau ───────────────────────────────────────────────────────
	   Vue est en build runtime seul : pas de « template », uniquement render. */
	var Panneau = {
		name: 'WfElementsIndex',

		data: function () {
			return { filtre: '', builder: null, survole: null };
		},

		created: function () {
			this.builder = trouverBuilder(this.$root);
			if (!this.builder) {
				crier('aucun builder ouvert : le panneau reste vide.');
			}
		},

		computed: {
			elements: function () {
				var b = this.builder;
				if (!b) { return []; }
				var f = String(this.filtre || '').trim().toLowerCase();
				return collecter(b)
					.map(function (node, rang) {
						return {
							node: node,
							titre: titre(b, node),
							ou: emplacement(b, node),
							icone: (b.type(node) || {}).iconSmall || '',
							// Le rang sert de repli : lire self.elements ici
							// rappellerait la propriété calculée en cours
							// d'évaluation, donc une récursion sans fin.
							cle: typeof b.key === 'function' ? b.key(node) : rang
						};
					})
					.filter(function (e) {
						if (!f) { return true; }
						return (e.titre + ' ' + e.ou).toLowerCase().indexOf(f) !== -1;
					});
			}
		},

		methods: {
			ouvrir: function (e) {
				this.builder.edit(e.node);
			},
			entrer: function (e) {
				this.survole = e.cle;
				this.$trigger('hoverNode', [e.node, this.builder]);
			},
			sortir: function (e) {
				this.survole = null;
				this.$trigger('leaveNode', [e.node, this.builder]);
			},
			viser: function (e, evt) {
				evt.preventDefault();
				evt.stopPropagation();
				this.$trigger('scrollNode', [e.node, this.builder]);
			},
			mot: function (cle, secours) {
				return CFG[cle] || secours;
			}
		},

		render: function (h) {
			var self = this;

			if (!this.builder) {
				return h('div', { class: 'uk-panel' }, [
					h('p', { class: 'uk-text-meta' }, this.mot('absent', ''))
				]);
			}

			var champ = h('div', { class: 'uk-margin-small' }, [
				h('input', {
					class: 'uk-input',
					attrs: { type: 'search', placeholder: this.mot('filtre', 'Filtrer…'), autofocus: true },
					domProps: { value: this.filtre },
					on: {
						input: function (ev) { self.filtre = ev.target.value; }
					}
				})
			]);

			var lignes = this.elements.map(function (e) {
				var visuel = e.icone
					? h('img', { class: 'uk-preserve-width', attrs: { src: e.icone, width: 20, height: 20, alt: '' } })
					: h('span', { class: 'uk-margin-small-right' }, '▪');

				var etiquettes = [h('div', { class: 'uk-text-truncate' }, e.titre)];
				if (e.ou) {
					etiquettes.push(h('div', { class: 'uk-text-meta uk-text-truncate' }, e.ou));
				}

				var viseur = h('a', {
					class: 'yo-builder-icon-scroll-to',
					attrs: { href: '', title: self.mot('viseur', ''), 'aria-label': self.mot('viseur', '') },
					on: { click: function (ev) { self.viser(e, ev); } }
				});

				return h('li', { key: e.cle, class: self.survole === e.cle ? 'uk-active' : '' }, [
					h('a', {
						attrs: { href: '' },
						class: 'uk-flex uk-flex-middle',
						on: {
							click: function (ev) { ev.preventDefault(); self.ouvrir(e); },
							mouseenter: function () { self.entrer(e); },
							mouseleave: function () { self.sortir(e); }
						}
					}, [
						h('div', { class: 'uk-width-auto uk-margin-small-right' }, [visuel]),
						h('div', { class: 'uk-width-expand' }, etiquettes),
						h('div', { class: 'uk-width-auto' }, [viseur])
					])
				]);
			});

			var corps = lignes.length
				? h('ul', { class: 'uk-nav uk-nav-default' }, lignes)
				: h('p', { class: 'uk-text-meta' }, this.mot('vide', ''));

			return h('div', { class: 'uk-panel' }, [
				champ,
				corps,
				h('p', { class: 'uk-text-meta uk-margin-small-top' },
					this.elements.length + (this.elements.length > 1 ? ' éléments' : ' élément'))
			]);
		}
	};

	function ouvrir() {
		return ouvrirPanneau({
			name: NOM,
			title: CFG.titre || 'Éléments',
			width: CFG.largeur || 340,
			component: Panneau
		});
	}

	/* ── Points d'entrée ──────────────────────────────────────────────────
	   Un raccourci clavier, qui ne dépend d'aucun balisage, et un bouton posé
	   dans l'en-tête de la barre latérale tant qu'un builder est ouvert. Si
	   l'ancrage disparaît dans une version future de YOOtheme, le bouton
	   disparaît, le raccourci reste, et la console le dit une fois. */
	var prevenuAncrage = false;

	function ajusterBouton() {
		var bouton = document.querySelector('.wf-index-bouton');
		var builderOuvert = !!document.querySelector('.yo-builder');

		if (!builderOuvert) {
			if (bouton && bouton.parentNode) { bouton.parentNode.removeChild(bouton); }
			return;
		}
		if (bouton) { return; }

		var entete = document.querySelector('.yo-sidebar-header');
		if (!entete) {
			if (!prevenuAncrage) {
				prevenuAncrage = true;
				crier('l\'en-tête de la barre latérale (.yo-sidebar-header) est introuvable : pas de bouton, le raccourci clavier reste actif.');
			}
			return;
		}

		var b = document.createElement('button');
		b.type = 'button';
		b.className = 'uk-button uk-button-text uk-button-small wf-index-bouton';
		b.style.marginLeft = 'auto';
		b.textContent = CFG.bouton || 'Éléments';
		b.addEventListener('click', function (ev) {
			ev.preventDefault();
			ouvrir();
		});

		var cible = entete.querySelector('.yo-sidebar-close') || entete;
		cible.appendChild(b);
	}

	function surveiller() {
		var enAttente = 0;
		var obs = new MutationObserver(function () {
			if (enAttente) { return; }
			enAttente = requestAnimationFrame(function () {
				enAttente = 0;
				ajusterBouton();
			});
		});
		obs.observe(document.body, { childList: true, subtree: true });
		ajusterBouton();
	}

	document.addEventListener('keydown', function (ev) {
		if (!(ev.ctrlKey || ev.metaKey) || !ev.shiftKey) { return; }
		if (ev.key !== 'E' && ev.key !== 'e') { return; }
		ev.preventDefault();
		ouvrir();
	});

	/* ── Démarrage ────────────────────────────────────────────────────────
	   customizer.js est un module : il s'exécute après ce script. On attend
	   donc que window.$fields apparaisse, sans bloquer et sans attendre
	   indéfiniment. */
	var essais = 0;
	(function attendre() {
		if (ouvreur()) {
			surveiller();
			return;
		}
		if (++essais > 600) {
			crier('window.$fields n\'est jamais apparu : le customizer n\'a pas démarré, ou son registre de champs a changé.');
			return;
		}
		requestAnimationFrame(attendre);
	})();

	/* Une poignée pour la console, et pour un banc d'essai. */
	window.WFElements = {
		ouvrir: ouvrir,
		trouverBuilder: trouverBuilder,
		collecter: collecter,
		emplacement: emplacement,
		titre: titre,
		estWeFrame: estWeFrame,
		panneau: Panneau
	};
})();
JS;

	return $config . "\n" . $js;
}
endif;

if ( ! class_exists( 'WF_Builder_Index_Listener' ) ) :
/**
 * Écouteur de « customizer.init ». La forme (classe + '@methode', dépendances
 * injectées par le constructeur) est celle qu'attend EventLoader et celle
 * qu'emploie packages/theme/src/Listener/LoadCustomizerScript.php.
 */
class WF_Builder_Index_Listener {

	public \YOOtheme\Metadata $metadata;

	public function __construct( \YOOtheme\Metadata $metadata ) {
		$this->metadata = $metadata;
	}

	public function handle(): void {
		$this->metadata->set( 'script:wf-builder-index', wf_bidx_script() );
	}
}
endif;
