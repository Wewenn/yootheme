# Défilement latéral dans les réglages de Section

Nouveau module `modules/wf-section-scroll.php` : un groupe de champs
« Défilement latéral » dans l'onglet **Paramètres** de la Section native, pour
qu'une section se parcoure à l'horizontale au lieu de descendre.

---

## La question de départ : comment le faire correctement

Il y a deux familles de solutions, et une seule est défendable.

**Détourner la molette** — `wheel` + `preventDefault()` + `scrollLeft` piloté à
la main. C'est ce qu'on trouve dans la plupart des tutoriels, et c'est le
« scroll hijacking ». Ça casse le clavier (Page suivante, Origine, Fin), ça tue
l'inertie du trackpad, ça rend la barre de défilement mensongère, ça empêche la
recherche dans la page d'atteindre le contenu hors champ, et le geste tactile
devient imprévisible. **Écarté.**

**Ne jamais toucher au défilement de la page.** On construit une zone verticale
plus haute que l'écran, on y colle un hublot en `position: sticky`, et on décale
les panneaux à l'intérieur *en fonction de la progression de cette zone dans
l'écran*. Le défilement reste celui du navigateur : son inertie, son clavier, sa
barre, son geste tactile. C'est ce que font Apple sur ses pages produit et GSAP
ScrollTrigger. **Retenu.**

La progression est lue par le CSS lui-même (`animation-timeline: view()`), sans
une ligne de JavaScript : l'animation tourne hors du thread principal et ne peut
pas saccader parce qu'un script est occupé ailleurs.

---

## Deux couches, parce que Firefox

Les animations pilotées par le scroll sont dans Chrome, Edge et Safari, mais
**restent derrière un drapeau dans Firefox stable** (`layout.css.scroll-driven-animations.enabled`,
encore le cas en v152, juin 2026) — environ 84 % de support global. Une section
qui n'existerait qu'en mode épinglé serait cassée pour un visiteur sur six.

D'où deux couches superposées :

| Couche | Où | Ce que voit le visiteur |
|---|---|---|
| **Socle** | partout, sans JavaScript | une vraie bande latérale : `overflow-x` + `scroll-snap`, qu'on parcourt au doigt, au trackpad, aux flèches |
| **Enrichissement** | sous `@supports (animation-timeline: view())` et `prefers-reduced-motion: no-preference` | la même bande, épinglée, pilotée par le défilement vertical de la page |

Le mode « épinglé » **dégrade donc en bande latérale**, jamais en page cassée.
Et un visiteur qui a demandé moins d'animations garde la bande, qui n'anime
rien.

C'est la même double garde que `wf_anim` dans `wf-section-fx.php` : la maison
avait déjà tranché, on suit.

---

## La contrainte : zéro balisage, profondeur de DOM inconnue

Comme `wf-section-fx.php`, ce module **n'ajoute aucune balise**. Tout passe par
le champ CSS que la Section possède déjà, que YOOtheme préfixe avec l'id du
nœud (`.el-element` désigne la section).

Problème : la structure rendue varie. Selon que le conteneur est actif ou non,
la grille est enfant direct de la section **ou** petit-enfant. Impossible à
deviner de façon fiable sans les sources de YOOtheme.

La recette ci-dessous s'en affranchit en ne nommant que des **classes publiques
UIkit**, à n'importe quelle profondeur :

```
PARENT    .el-element:has(> .uk-grid), .el-element :has(> .uk-grid)
          → porte la hauteur de défilement et la timeline
HUBLOT    .el-element .uk-grid
          → collant, un écran de haut, débordement caché, une seule ligne
PANNEAUX  .el-element .uk-grid > *
          → un écran de large chacun, décalés ensemble
```

`:has(> .uk-grid)` désigne le parent direct de la grille **sans qu'on ait
besoin de connaître sa classe**, et les deux sélecteurs couvrent les deux
structures. Les panneaux se décalent eux-mêmes plutôt qu'un rail intermédiaire :
ça économise le niveau de DOM qui manquait.

Une seule keyframe suffit pour trois panneaux comme pour douze — la distance
arrive par la variable `--wf-hs-end`, posée par la section :

```css
@keyframes wf-hs-x { to { transform: translateX(var(--wf-hs-end, 0%)); } }
```

et le sens « vers la droite » se fait avec `animation-direction: reverse`.

---

## Le piège, trouvé à l'exécution

La première version posait la hauteur de défilement avec `padding-bottom` sur
le PARENT. **La grille ne collait pas du tout** : elle défilait comme un bloc
normal, alors que `position: sticky` et `top: 0` étaient bien calculés.

Cas minimal, mesuré dans Chromium — à 400 px après le début de la zone, la
grille devrait être à `top = 0` :

```
A · parent avec height:1600px          top=  0   COLLE
B · parent avec padding-bottom:1000px  top=-400  NE COLLE PAS
```

Le rectangle qui borne un élément collant est la **boîte de contenu** de son
parent, pas sa boîte de padding. Il faut donc une hauteur réelle :

```css
PARENT { height: calc(100vh + <défilement>vh); padding-top: 0; padding-bottom: 0; }
```

Ça ne se voit ni à la lecture du CSS, ni dans les inspecteurs : `position` vaut
bien `sticky`, `top` vaut bien `0`, et rien ne colle. C'est exactement le genre
de chose qu'un banc d'essai attrape et qu'une relecture ne trouve pas.

---

## Les réglages

| Champ | Rôle |
|---|---|
| **Défilement latéral** | Non · Bande latérale · Épinglée |
| **Sens du défilement** | vers la gauche (on avance à droite) · vers la droite (on part de la droite) |
| **Largeur d'un panneau** | plein écran · ¾ · ⅔ · ½ · ⅓ · selon le contenu |
| **Écart entre panneaux** | en px ; remplace la gouttière de la ligne, neutralisée pour que les panneaux s'alignent au pixel |
| **Défilement par panneau** | en % de hauteur d'écran ; 100 = un écran de scroll par palier |
| **Accroche** | `scroll-snap` sur les panneaux |
| **À partir de** | tous les écrans · tablette · bureau · grand écran — en dessous, la section reprend son empilement vertical |
| **Progression** | fine barre en haut du hublot (mode épinglé seulement : en bande latérale, la barre du navigateur suffit) |

**Le nombre de panneaux n'est pas un réglage** : il est lu dans l'arbre du
builder (`wf_shs_count_panels()`). Un chiffre saisi à la main se désynchronise
dès qu'on ajoute une colonne — et c'est lui qui fixe la hauteur de défilement.

**Une seule ligne, dont les colonnes sont les panneaux.** Avec un seul panneau,
le module ne produit rien du tout : il n'y a rien à faire défiler, et une
section épinglée de 0 vh serait juste une section cassée.

---

## Ce qu'il faut savoir avant de l'activer

Le mode épinglé a un coût, indépendamment de la qualité de la mise en œuvre :

- la page devient plus haute que son contenu, donc **la barre de défilement
  ment** sur la longueur réelle ;
- les ancres internes et la **recherche dans la page** n'atteignent pas les
  panneaux hors champ ;
- sur un panneau plus haut qu'un écran, le contenu est **rogné** par le hublot.

C'est pourquoi le défaut est « Non », que le seuil par défaut est « Bureau »
(≥ 960 px), et que la bande latérale — qui n'a aucun de ces défauts — est
proposée en premier.

---

## Installation

```
elements/modules/wf-section-scroll.php  ->  _sources\wf-yoo-elements\modules\
```

Deux lignes à ajouter dans `modules/bootstrap.php`, à côté de celles de
`wf-section-fx.php` :

```php
// Défilement latéral dans les réglages de Section
require_once __DIR__ . '/wf-section-scroll.php';
```

```php
if ( function_exists( 'wf_shs_boot' ) ) {
    try {
        wf_shs_boot( $builder );
    } catch ( \Throwable $e ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'WF section scroll: ' . $e->getMessage() );
        }
    }
}
```

Et les deux keyframes de `wf-section-scroll.keyframes.css` à recopier à la fin
de `assets/css/weframe-shared.css` — elles ne peuvent pas vivre dans le champ
CSS de la section, le préfixeur de YOOtheme transformerait `to` en `#id to`.

Le ZIP `dist/wf-yoo-elements-sans-wc-6.30.0.zip` contient déjà tout cela câblé.

---

## Vérifié

47 assertions dans un Chromium réel (`hs-probe.mjs`), sur le CSS réellement
produit par `wf_shs_css()` et sur **les deux structures de DOM** :

- épinglage : hublot collant à `top: 0` sur toute la traversée, un panneau =
  un hublot, décalage de 0 à −(n−1)×100 % linéaire avec le défilement, dernier
  panneau bien à l'écran en fin de course — identique que la grille soit enfant
  direct de la section ou nichée dans un conteneur ;
- sens inverse : de −(n−1)×100 % à 0 ;
- bande latérale : débordement horizontal réel, accroche, écart, largeur de
  panneau à ⅔ ;
- barre de progression à moitié remplie à mi-course ;
- seuil d'écran : sous 960 px, plus rien n'est collant, la ligne repasse à la
  ligne, la hauteur imposée disparaît, aucun débordement horizontal de page ;
- un seul panneau : aucune règle produite.

**Pas vérifié** : le rendu dans un vrai YOOtheme. Le banc d'essai imite le
préfixeur (`.el-element` → `#id`) et un sous-ensemble fidèle d'UIkit
(`uk-section`, `uk-container`, `uk-grid` et leurs gouttières), mais la
structure réelle de la Section reste à confirmer à la première pose — c'est
précisément pour ça que la recette ne nomme que des classes publiques et
couvre les deux profondeurs.

Firefox n'étant pas installé ici, la couche de repli n'a pas pu être vue à
l'œuvre ; elle est garantie par `@supports`, que Chromium confirme sélectif.
