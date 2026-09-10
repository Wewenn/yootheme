# Annuaire global — entrée « WeFrame » au menu du customizer

Module `modules/wf-global-index.php` : une entrée **WeFrame** dans le menu
principal, entre Widgets et Paramètres, qui répond à la question « où est mon
élément ? » quand on ne sait plus dans quelle page, quel widget ou quel
mégamenu il vit.

```
┌─────────────────┐      ┌──────────────────────────────────┐
│    YOOtheme     │      │ ‹  WeFrame                       │
├─────────────────┤      ├──────────────────────────────────┤
│ MODÈLE          │      │  Où sont mes éléments ?       ›  │
│ STYLE           │      │  En-tête & Dialog             ›  │
│ PAGES           │      │  Navigation & mégamenus       ›  │
│ TEMPLATES       │      └──────────────────────────────────┘
│ MENUS           │
│ WIDGETS         │
│ WEFRAME     ←   │   priorité 45 : un outil transversal,
│ PARAMÈTRES      │   pas une zone de contenu
└─────────────────┘
```

---

## Le problème qu'il règle

Nos éléments ne sont pas « quelque part dans le builder ». Ils sont éparpillés
dans **cinq contextes d'édition**, chacun derrière une porte différente du menu,
et chacun stocké ailleurs :

| Contexte | Où c'est stocké | Comment on le lit |
|---|---|---|
| Pages, articles | `post_content`, dans un commentaire HTML | `PostHelper::matchContent()` |
| Templates | `wp_options.yootheme` → `templates` | JSON |
| Widgets | `wp_options.widget_builder` | `$instance['content']` |
| Items de mégamenu | `theme_mod « config »` → `menu.items[].content` | `get_theme_mod('config')` |
| Pied de page | `theme_mod « config »` → `footer.content` | idem |

L'annuaire les relit tous et dit, pour chaque élément, où il se trouve —
« Section 2 › Ligne 1 » comprise.

`PostHelper::matchContent()` est l'API publique de YOOtheme pour extraire un
layout d'un `post_content` : autant s'en servir plutôt que de refaire son motif.
Le repli, si la classe venait à disparaître, est ce même motif.

---

## Les trois vues

**Où sont mes éléments ?** — l'inventaire complet, groupé par contexte. Chaque
bloc porte le nom du contexte, son genre, le nombre d'éléments, puis la liste.
Le filtre porte sur le titre de l'élément, son emplacement **et** le nom du
contexte.

**En-tête & Dialog** — les éléments des zones du dialog (`dialog`,
`dialog-mobile`, et leurs zones « push »), plus cinq boutons :

```
Réglages du dropbar      → dialog-dropbar     (si layout dropbar)
Réglages de l'offcanvas  → dialog-offcanvas   (si layout offcanvas)
Réglages du modal        → dialog-modal       (si layout modal)
Réglages de l'en-tête    → header
Gérer les widgets        → wordpress-widgets
```

**Navigation & mégamenus** — les éléments de la navbar et des items de
mégamenu, plus les accès aux menus, à l'en-tête et aux réglages mobile.

Les boutons ne sont **pas** du JavaScript : ce sont des champs natifs
`button-panel`, avec les mêmes expressions `show` que YOOtheme emploie pour ces
mêmes panneaux (`$match(dialog.layout, '^offcanvas')`). Huit sauts, zéro ligne
de script.

---

## Ce qu'il fait, et ce qu'il ne fait pas

Un clic sur un contexte mène à la **bonne porte**, en une fois :

| Genre | Ce qui s'ouvre |
|---|---|
| Pied de page | son builder, directement |
| Item de mégamenu | la gestion des menus |
| Widget | la liste des widgets |
| Page, article | la liste des pages |
| Template | la liste des templates |

**Il n'entre pas dans l'élément lui-même.** Depuis l'extérieur d'un builder,
aucun point d'entrée public ne permet d'ouvrir la page X *et* d'y sélectionner
un nœud : les composants qui le feraient (`WidgetBuilder`, `BuilderSection`)
sont internes au bundle, et le store `Builder` d'une page n'existe pas tant
qu'elle n'est pas ouverte. C'est le [panneau contextuel](INDEX-BUILDER.md) qui
prend le relais une fois la mise en page ouverte : l'annuaire dit *où*, le
panneau contextuel amène *dessus*.

C'est une limite du terrain, pas un raccourci : je préfère l'annoncer que la
maquiller derrière un bouton qui ouvrirait la mauvaise chose.

---

## Deux détails qui changent l'usage

**Les items ne sont pas listés.** « Photo », « Carte », « Mot » n'ont de sens
que dans leur parent, et on ne va pas les chercher. Ils restent connus de la
table des titres — pour ne pas être pris pour des types inconnus — mais
n'apparaissent pas. Sans ce filtre, une galerie de douze photos produisait
treize lignes.

**Un élément disparu est signalé en rouge**, avec son nom interne, au lieu
d'être passé sous silence. Si le plugin perd une définition — c'est arrivé :
dix en sont sorties entre la 6.31 et la 6.44 —, l'annuaire montre exactement
quelles pages en dépendent encore.

---

## Le coût

Le balayage **ne tourne que dans le customizer**, jamais sur le site. Il est mis
en cache dix minutes, et le cache tombe dès qu'une page, un widget ou la
configuration du thème est enregistré :

```php
add_action( 'save_post', 'wf_gidx_purger' );
add_action( 'update_option_widget_builder', 'wf_gidx_purger' );
add_action( 'update_option_yootheme', 'wf_gidx_purger' );
add_action( 'customize_save_after', 'wf_gidx_purger' );
```

La requête des pages est bornée à 300 entrées, triée par date de modification,
sans la corbeille, et ne retient que les contenus qui portent la marque d'une
mise en page. Pour forcer un rafraîchissement : `?wf_refresh=1` sur l'URL du
builder.

---

## Aucune modification du cœur

| Ce qu'on utilise | Où YOOtheme s'en sert |
|---|---|
| `customizer.init` + `Config::add('customizer', …)` | `LoadCustomizerData` |
| une section de config | `packages/styler/config/customizer.php`, huit lignes |
| le champ `button-panel` | `dialog._offcanvas` dans `theme/config/customizer.php` |
| `Metadata::set('script:…', …)` | `LoadConfigData` |
| `window.$fields` | seul objet exposé sans condition |

Un point relevé dans `customizer.js` rend les boutons possibles : l'application
fournit à la barre latérale **`{ ...panels, ...sections }`** — sections et
panneaux vivent dans la même table. Un `button-panel` peut donc viser aussi bien
`dialog-offcanvas` (un panneau) que `wordpress-widgets` (une section).

Le composant de liste déclare **explicitement ses props** (`field`, `values`)
plutôt que d'hériter du composant de base par une voie interne : le rendu des
champs les passe en attributs, et sans props déclarées ils tomberaient dans
`$attrs` — `field.zone` serait alors introuvable et les panneaux Dialog et
Navbar montreraient tout.

---

## Installation

```
elements/modules/wf-global-index.php  ->  _sources\wf-yoo-elements\modules\
```

```php
// Annuaire global : entree WeFrame dans le menu du customizer
require_once __DIR__ . '/wf-global-index.php';
```

```php
'events' => [
    'customizer.init' => [
        WF_Builder_Index_Listener::class => '@handle',
        WF_Global_Index_Listener::class => '@handle',
    ],
],
```

Préfixes, groupes, bornes et libellés sont dans `wf_gidx_reglages()` : rien à
chercher dans le JavaScript.

---

## Vérifié

**91 assertions**, réparties des deux côtés.

**58 côté serveur** (`gidx-test.php`), sur un faux site complet — articles,
options, `theme_mod`, zones de widgets :

- reconnaissance par préfixe et par groupe, rejet des natifs et des autres
  marques ;
- table des titres lue dans les vrais `element.json`, avec la distinction
  élément / item ;
- extraction : trois entrées, les items exclus, **trois emplacements
  distincts**, l'élément disparu signalé ;
- un élément peu profond dont le nom n'entre pas dans son propre emplacement ;
- entrées vides : `null`, JSON invalide, layout sans enfants ;
- le balayage : les cinq contextes, 11 éléments, et l'exclusion de tout ce qui
  ne contient rien de WeFrame ;
- les widgets et leur zone, y compris le widget sans titre et l'inactif ;
- le cache, ses quatre purges, et le contournement forcé ;
- la requête : bornée, triée, sans corbeille, filtrée sur la marque ;
- la configuration : priorité 45, trois entrées, trois panneaux, cinq
  `button-panel` avec leurs conditions.

**33 côté navigateur** (`gidx-probe.mjs`), sur le script réellement produit :

- l'enregistrement dans `window.$fields` sous le nom qu'attend le type de champ,
  avec `field` et `values` en props ;
- le filtre par zone — `dialog` inclut bien `dialog-mobile` — et par texte, sur
  le titre, l'emplacement, le contexte et le sous-titre ;
- le rendu : une liste par contexte, le total, l'élément perdu en rouge ;
- le clic qui vise la bonne porte, pour les cinq genres ;
- une zone vide et une charge de données absente, qui donnent un message plutôt
  qu'un panneau muet.

Deux défauts trouvés par les bancs d'essai, tous deux corrigés :

**Les items étaient listés comme des éléments.** Une galerie de douze photos
produisait treize lignes. La distinction se fait maintenant sur `element: true`
dans `element.json`.

**Un élément peu profond entrait dans son propre emplacement.** Posé directement
dans une section, il s'annonçait « Section 1 › Panneau ++ 1 ». Seule la
structure situe.

Et un troisième, de robustesse : les replis des libellés étaient des chaînes
vides. Si la charge de données manquait, le panneau restait **muet** — un
panneau vide sans explication est pire qu'un panneau qui dit qu'il est vide.

**Pas vérifié** : le rendu dans un vrai customizer YOOtheme. À contrôler en
premier : l'apparition de l'entrée « WeFrame » dans le menu, et un bouton
`button-panel` qui ouvre bien le panneau visé.
