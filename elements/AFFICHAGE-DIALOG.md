# Affichage du dialog — la taille, que YOOtheme n'expose pas

Module `modules/wf-dialog-display.php` : un panneau **WeFrame → En-tête &
Dialog → Affichage du dialog** qui donne au dialog de l'en-tête une taille.

```
┌────────────────────────────────────────────┐
│ ‹  Affichage du dialog                     │
├────────────────────────────────────────────┤
│  Forme        [dropbar][offcanvas][modal]  │
│                                            │
│  Taille       [ La moitié          ▾ ]     │
│  Ne pas dépasser  [ 520px           ]      │
│                                            │
│  Taille sur petit écran [ Plein écran ▾ ]  │
│  Petit écran, c'est en dessous de [640 px] │
│  ────────────────────────────────────────  │
│  Animation    [ Slide ▾ ]                  │
│  Côté         ☐ Ouvrir à droite            │
│               ☐ Superposer au site         │
└────────────────────────────────────────────┘
```

**Taille** : plein écran · trois quarts · deux tiers · la moitié · un tiers ·
un quart · personnalisée. Une dimension libre accepte `420px`, `60vw`, `32rem`
— ou un nombre nu, qui vaut des pixels.

Un **dropbar qui descend se règle en hauteur**, celui qui vient du côté en
largeur : c'est sa géométrie qui décide, pas un réglage de plus.

---

## Pourquoi ça n'existait pas

Le dialog natif propose : le mode (slide / reveal / push), « à droite »,
« superposer », et pour le modal cinq largeurs de contenu UIkit. **Aucune taille
du panneau lui-même.** La largeur d'un offcanvas vient du CSS du thème :

```css
.uk-offcanvas-bar         { width: 270px }
@media (min-width:640px)  { width: 350px }
```

Elle n'est exposée nulle part dans le customizer.

---

## Zéro JavaScript, et l'aperçu suit

Les champs sont des champs de config ordinaires : le customizer les rend, évalue
leurs conditions `show` et les enregistre lui-même dans `Config.values`, que
`set_theme_mod('config')` écrit. Le CSS est émis dans `wp_head` à partir de
`get_theme_mod('config')`.

Et ce `get_theme_mod` renvoie la configuration **en cours d'édition** pendant
l'aperçu :

```php
// packages/theme-wordpress/bootstrap.php
'filters' => [
    'theme_mod_config' => [Listener\LoadCustomizerSession::class => ['@handle', -10]],
],
```

`LoadCustomizerSession` applique les changements du customizer avant de rendre
la valeur. **Le réglage se voit donc en direct, sans une ligne de script et
sans dupliquer la logique côté navigateur.** C'est ce qui rend ce module aussi
court.

---

## Le piège : la largeur ne se change pas seule

UIkit place le panneau **fermé** à un décalage négatif égal à sa largeur. Quatre
familles de règles en dépendent, et il faut les réécrire toutes :

| Règle native | Ce qu'elle fait | Si on l'oublie |
|---|---|---|
| `.uk-offcanvas-bar { width; left: -width }` | position fermée | le panneau **dépasse** à l'état fermé |
| `.uk-open > .uk-offcanvas-bar { left: 0 }` | position ouverte | posée sur un id, **notre** règle gagne : il ne s'ouvre plus |
| `.uk-open > .uk-offcanvas-reveal { width }` | enveloppe du mode reveal | le mode reveal reste à la largeur d'origine |
| `.uk-offcanvas-container-animation { left: ±width }` | décalage de la page en mode push | le site est poussé de la mauvaise distance |

La deuxième est la plus vicieuse : un sélecteur d'id l'emporte sur
`.uk-open > .uk-offcanvas-bar`, donc **écrire la largeur sans réécrire l'état
ouvert empêche le dialog de s'ouvrir**.

La quatrième ne peut pas être limitée à notre dialog — UIkit la pose sur le
conteneur du site, sans id auquel se raccrocher. Elle n'est donc émise **qu'en
mode push**, où elle est nécessaire.

---

## Deux défauts trouvés par le banc d'essai

Aucun des deux ne se voit à la relecture.

**`uk-offcanvas-flip` n'est pas sur le dialog.** J'avais écrit
`#tm-dialog.uk-offcanvas-flip`. La source d'UIkit dit autre chose :

```js
I(body, this.clsContainer, this.clsFlip)   // addClass(body, …, 'uk-offcanvas-flip')
```

La classe va sur `<body>`. Le sélecteur correct est
`.uk-offcanvas-flip #tm-dialog …` — sans quoi **rien n'accroche à droite** : le
panneau reste à gauche, ouvert comme fermé.

**UIkit pose un `max-width` en ligne à l'ouverture :**

```js
c(this.panel, "maxWidth", e.clientWidth)
```

Un style en ligne bat n'importe quelle règle de feuille. Sans `!important`, le
plafond « Ne pas dépasser » aurait été **ignoré à chaque ouverture**. C'est le
seul `!important` du fichier, et il est commenté sur place.

---

## Les unités

Les fractions sont en `vw` (ou `vh` pour un dropbar qui descend), pas en `%`.
Le conteneur d'un offcanvas n'a pas de largeur propre — `position:fixed;
top:0; bottom:0; left:0`, sans `right` — donc un pourcentage n'aurait rien
contre quoi se calculer.

Une valeur libre passe par une validation stricte
(`/^\d+(\.\d+)?(px|em|rem|%|vw|vh|vmin|vmax)$/`) : ce qui ne ressemble pas à une
longueur est **refusé**, pas échappé. Rien d'autre n'entre dans la feuille.

---

## Ce que ça ne fait pas

Le panneau règle **l'affichage**, pas le contenu. Le contenu d'un dialog, ce
sont les widgets posés dans la zone « Dialog » — et le dialog n'apparaît sur le
site que si cette zone contient au moins un widget (`is_active_sidebar('dialog')`
dans `templates/header.php`). Le bouton « Gérer les widgets » du panneau
« En-tête & Dialog » y mène.

En modal, la taille remplace la « largeur de contenu » du panneau natif : un
seul réglage au lieu de deux qui se contredisent.

Les mêmes règles s'appliquent au dialog mobile (`#tm-dialog-mobile`) : les deux
ne s'affichent jamais en même temps, et la « taille sur petit écran » couvre le
cas.

---

## Installation

```
elements/modules/wf-dialog-display.php  ->  _sources\wf-yoo-elements\modules\
```

À charger **avant** `wf-global-index.php`, qui teste `function_exists('wf_dlg_bouton')`
pour ajouter le bouton dans son panneau « En-tête & Dialog » :

```php
// Affichage du dialog : taille, forme et comportement
require_once __DIR__ . '/wf-dialog-display.php';
// Annuaire global : entree WeFrame dans le menu du customizer
require_once __DIR__ . '/wf-global-index.php';
```

```php
'events' => [
    'customizer.init' => [
        WF_Dialog_Display_Listener::class => '@handle',
    ],
],
```

Les deux modules restent indépendants : sans celui-ci, l'annuaire n'affiche
simplement pas le bouton.

---

## Vérifié

**32 assertions dans un Chromium réel** (`dlg-probe.mjs`), sur le **vrai CSS
d'offcanvas du thème** recopié depuis `css/theme.css`, avec notre feuille
par-dessus — quinze cas rendus par `wf_dlg_css()` :

| Ce qui est mesuré | Attendu |
|---|---|
| Fermé, largeur « moitié » | 500 px sur une fenêtre de 1000 |
| Fermé, décalage | −500 px : **rien ne dépasse** |
| Fermé, flip | −500 px à droite |
| Ouvert | collé au bord, à la largeur voulue |
| Ouvert, flip | collé à droite |
| Un quart / plein écran | 250 / 1000 |
| Dimension libre `420px` / `360` | 420 / 360 |
| Plafond 300 px | 300, **même face au `max-width` en ligne d'UIkit** |
| Mode reveal | l'enveloppe et le panneau à la même largeur, sans décalage |
| **Sans réglage** | 350 px, la largeur du thème, intacte |
| Modal | le corps à la moitié, centré |
| Dropbar | réglé en **hauteur** (400 px sur 800), pleine largeur |
| Mode autre que push | aucune règle `container-animation` émise |

Le banc a aussi corrigé mes propres erreurs de mesure avant de valider le code :
un offcanvas fermé est en `display:none` (rien à mesurer), et `50vw` se calcule
sur la fenêtre — un cadre d'essai plus étroit faussait tout.

**Pas vérifié** : le rendu dans un vrai customizer YOOtheme. À contrôler en
premier : le panneau s'ouvre depuis « En-tête & Dialog », et un changement de
taille se voit dans l'aperçu sans enregistrer.
