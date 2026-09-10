# Sections côte à côte — chaque ligne de gauche à droite

Module `modules/wf-section-ribbon.php` : un groupe de champs « Sections côte à
côte » dans l'onglet **Paramètres** de la Section native.

Plusieurs sections entières se posent sur une même ligne, que le défilement
parcourt de gauche à droite. Quand la ligne est finie, la page reprend son cours
vers le bas jusqu'à la suivante.

```
De base                    Ce que ça donne

S1                         S1 → S2            ligne 1, de la GAUCHE vers la droite
S2                         ↓  (la page descend normalement)
S3            devient      S3                 seule : une section ordinaire
S4                         ↓  (la page descend normalement)
S5                         S4 → S5 → S6       ligne 2, de la GAUCHE vers la droite
S6
```

---

## Pourquoi ce n'est plus un plateau

La première version posait toutes les sections sur **un seul plateau** à deux
dimensions et y promenait une caméra. La ligne suivante démarrait à la colonne
où la précédente s'était arrêtée — un escalier. À l'usage, deux défauts :

- **les lignes ne commençaient pas au même endroit** : la 2ᵉ et la 3ᵉ partaient
  du milieu du plateau, pas de la gauche ;
- **la descente entre deux lignes était un mouvement de caméra** à inventer :
  vertical au milieu d'une mise en page horizontale, ni tout à fait un
  défilement, ni tout à fait une transition.

Corriger le premier défaut sans changer d'architecture obligeait à mettre en
scène un **retour chariot** — la caméra revenant vers la gauche en descendant.
Trois façons de le faire, toutes mauvaises : en diagonale (le contenu part vers
la droite alors que le visiteur défile vers le bas), en deux temps (bas puis
gauche, saccadé), ou en coupure sèche (un saut).

**Le retour chariot n'a pas à être mis en scène : il suffit de ne pas en avoir
besoin.** Chaque ligne est un bloc indépendant qui s'épingle le temps de sa
traversée, puis relâche. Entre deux lignes, il n'y a rien à inventer — c'est le
défilement vertical ordinaire de la page.

Trois gains :

| | Plateau (v1) | Lignes indépendantes (v2) |
|---|---|---|
| Départ d'une ligne | à la colonne précédente | **toujours à gauche** |
| Entre deux lignes | mouvement de caméra vers le bas | défilement vertical normal |
| Ligne d'une seule section | une case du plateau | **une section ordinaire**, intacte |

Et le moteur y perd une dimension : plus de caméra en (x, y), juste un décalage
horizontal par ligne. Moins de code, moins de cas limites.

---

## Ce n'est pas le défilement latéral

Deux modules, deux choses différentes :

| | `wf-section-scroll.php` | `wf-section-ribbon.php` |
|---|---|---|
| Ce qui bouge | **l'intérieur** d'une section : ses colonnes deviennent des panneaux | **des sections entières**, mises côte à côte |
| Portée | une section, rien n'en sort | une suite de sections voisines |
| Réglage | « cette section défile à l'horizontale » | « cette section se pose là » |

Une section prise dans une ligne ignore son propre défilement latéral : la ligne
l'emporte, et `wf_shs_css()` ne produit rien pour elle.

---

## Pourquoi c'est un script qui monte la ligne

**Une Section YOOtheme ne peut pas en contenir une autre.** La ligne ne peut
donc pas être un élément du builder : il n'existe aucun endroit où la déclarer.

C'est le script qui, à l'exécution, repère les sections voisines déclarées d'une
même ligne, fabrique le cadre autour d'elles et les y place :

```
.wf-rb-stage      hauteur = un écran + ce qui dépasse  → la place à défiler
  .wf-rb-view     sticky, un écran, débordement caché  → le hublot
    .wf-rb-board  translaté horizontalement            → la ligne
      section     absolue, à sa place sur la ligne
```

D'où la forme du réglage : il ne dit pas « cette section défile », il dit
**« cette section se pose là »**. Le reste est mesuré.

---

## Le défilement n'est jamais détourné

Ni `wheel`, ni `preventDefault`, ni `scrollTop` écrit à la main. Une ligne
devient une zone verticale plus haute que l'écran ; le décalage horizontal se
déduit de la progression de cette zone dans l'écran. Le clavier, l'inertie du
trackpad, la barre de défilement et le geste tactile restent ceux du navigateur.

Un pixel de défilement = un pixel parcouru vers la droite. Le réglage
**Longueur de la traversée** met le rapport à l'échelle.

**Une ligne qui tient dans l'écran ne défile pas du tout.** Deux demi-sections,
trois tiers : la course vaut zéro, aucune hauteur n'est ajoutée, et les sections
sont simplement posées côte à côte. C'est une mise en page, pas un effet.

---

## Sans script, la page reste la page

Aucune règle CSS n'est posée sur les sections tant que le cadre n'est pas monté.
Le module n'émet que des variables (`--wf-rb-role`, `--wf-rb-w`…) que le script
relit.

- script absent ou en erreur → les sections restent empilées ;
- écran sous le seuil → les cadres sont **démontés**, les sections retournent
  dans la page, dans l'ordre, sans styles résiduels ;
- `prefers-reduced-motion: reduce` → rien n'est monté ;
- structure incohérente → la ligne est refusée, avec la raison en console.

Le repli n'est pas une version dégradée : **c'est la page normale.**

Sur le mouvement réduit, ce module est plus strict que le mode épinglé de
`wf-section-scroll`, qui lui reste actif. Une mise en page qui glisse sous les
yeux à chaque tour de molette est précisément ce qu'un visiteur sensible au
mouvement demande à ne pas subir — et la page verticale est complète.

---

## Les réglages

Sur **chaque** section de la ligne :

| Champ | Rôle |
|---|---|
| **Sections côte à côte** | Non · Démarre la première ligne · À droite de la précédente · Nouvelle ligne (retour à gauche) |
| **Largeur de cette section** | plein écran · ¾ · ½ · ⅓ · selon le contenu |

Sur la section qui **démarre** le parcours, et sur elle seule — un parcours, un
jeu de réglages, quel que soit son nombre de lignes :

| Champ | Rôle |
|---|---|
| **Longueur de la traversée** | 50 à 400 % ; 100 = un pixel de défilement pour un pixel vers la droite |
| **Souplesse du mouvement** | 0 : la ligne colle au défilement · 100 : elle glisse encore un instant après l'arrêt |
| **À partir de** | tablette · bureau (défaut) · grand écran |
| **Progression** | fine barre en haut de chaque ligne, avec sa couleur |

**La hauteur d'une section en ligne est d'un écran.** Un contenu plus haut
déborde sur la section suivante — c'est voulu, c'est ce qui fait le liant.

---

## Trois refus, et ils se disent

Une ligne mal formée n'est jamais montée à moitié. Elle est refusée, et la
console nomme la section fautive — cliquable dans l'inspecteur :

```
WF sections côte à côte : aucune ligne de ce parcours n'a deux sections ou plus
WF sections côte à côte : aucune section « Démarre la première ligne » avant celle-ci
WF sections côte à côte : elle ne suit pas immédiatement la section précédente
```

Le troisième mérite une précision : entre deux sections d'une ligne, le module
tolère tout ce qui **n'occupe aucune place** — une balise `<style>`, un script,
un bloc masqué par une condition d'affichage. C'est exactement ce que rend
YOOtheme entre deux sections. En revanche une section ordinaire glissée au
milieu rompt la ligne, et c'est dit.

---

## Deux pièges trouvés à l'exécution

**1. L'observateur suivait un élément sans boîte.** La boucle d'animation ne se
relance que si la ligne est à l'écran, ce que dit un `IntersectionObserver`. Il
observait « ce qui vient après l'ancre » — qui se trouve être une balise
`<style>`. Un élément en `display:none` n'intersecte jamais rien : la ligne se
montait parfaitement, les sections étaient à leur place au pixel près, et **rien
ne bougeait au défilement**.

**2. Des bornes de défilement gardées en mémoire.** La progression se calculait
entre deux bornes mesurées au montage. Or une ligne qui se monte plus haut dans
la page déplace toutes celles qui suivent, et une image en retard fait pareil.
Elle est maintenant lue en direct :

```js
var p = -stage.getBoundingClientRect().top / (stage.offsetHeight - hVue);
```

Une lecture de géométrie par image, juste avant l'écriture du `transform` :
c'est le bon ordre, et ça vaut mieux qu'une borne fausse sans prévenir.

---

## Ce qu'il faut savoir avant de l'activer

- la page devient plus haute que son contenu, donc **la barre de défilement
  ment** ;
- les ancres internes et la **recherche dans la page** n'atteignent pas les
  sections hors champ d'une ligne ;
- le module **déplace les sections dans le DOM** pour les mettre en ligne. Un
  script tiers qui aurait mémorisé leur position avant le montage peut s'en
  trouver dérangé. Le module s'exécute après `DOMContentLoaded` (ou via
  `WF.ready`), donc après l'initialisation d'UIkit, mais c'est le point à
  surveiller en premier si un comportement voisin se met à faire l'idiot.

C'est pourquoi le défaut est « Non » et le seuil « Bureau ».

---

## Installation

```
elements/modules/wf-section-ribbon.php  ->  _sources\wf-yoo-elements\modules\
```

Deux ajouts dans `modules/bootstrap.php`, à côté de ceux de `wf-section-fx.php` :

```php
// Sections cote a cote : chaque ligne defile de gauche a droite
require_once __DIR__ . '/wf-section-ribbon.php';
```

```php
// Sections cote a cote ajoutees a la Section native.
if ( function_exists( 'wf_rb_boot' ) ) {
    try {
        wf_rb_boot( $builder );
    } catch ( \Throwable $e ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'WF section ribbon: ' . $e->getMessage() );
        }
    }
}
```

Rien à ajouter dans `assets/` : le module porte sa structure CSS et son script,
imprimés une seule fois par page et seulement si une section les demande.

Le ZIP `dist/wf-yoo-elements-sans-wc-6.46.0.zip` contient tout cela câblé, avec
`wf-section-scroll.php` par la même occasion.

---

## Vérifié

**62 assertions dans un Chromium réel** (`rb2-probe.mjs`), sur le CSS et le
script réellement produits, et d'abord sur ce qui a motivé la réécriture :

| Ce qui est mesuré | Attendu |
|---|---|
| Départ de la ligne 1 | `left: 0` |
| Départ de la ligne 3 | `left: 0` |
| Sections de la ligne 3 | 0, 1 et 2 largeurs d'écran |
| Décalage vertical d'une section en ligne | aucun (`top: 0` partout) |
| Mouvement vertical du cadre pendant la traversée | **zéro**, du départ à l'arrivée |

Puis : chaque ligne a sa propre hauteur de défilement (écran + 1 largeur pour
la ligne 1, écran + 2 pour la ligne 3) ; la traversée va de 0 à la course
entière, hublot collé à `top: 0`, la dernière section remplissant exactement le
hublot ; entre les lignes, la ligne 1, puis S3, puis la ligne 3 se suivent au
même niveau dans la page.

Plus :

- **S3, seule sur sa ligne** : dans aucun cadre, aucun style imposé — une
  section ordinaire ;
- **une ligne qui tient dans l'écran** (deux moitiés) : posée côte à côte,
  aucune hauteur ajoutée, et rien ne bouge au défilement ;
- **selon le contenu** : la section mesure 520 px au lieu de 1280, et la course
  ne compte que ce qui dépasse de l'écran ;
- **souplesse à 80** : la ligne est mesurée *en retard* juste après un saut,
  puis rejoint sa cible à moins de 12 px ;
- **longueur à 200 %** : hauteur = écran + 2 × course ;
- **barre de progression** : présente (3 px), à `scaleX(0,5)` à mi-course, et
  chaque ligne a la sienne ;
- **les trois refus**, chacun avec son avertissement ;
- **sous le seuil** (560 px) : plus aucun cadre, plus aucune case, styles
  nettoyés, les six sections de retour dans la page, empilées dans l'ordre,
  sans débordement horizontal ;
- **retour au grand écran** : les lignes se remontent, dans le bon ordre, et
  repartent de la gauche ;
- **`prefers-reduced-motion`** : aucun cadre monté.

**Pas vérifié** : le rendu dans un vrai YOOtheme, et le panneau du builder. Le
banc d'essai imite le préfixeur (`.el-element` → `#id`) et pose entre chaque
section la balise `<style>` que produit le rendu — c'est elle qui a révélé le
premier piège. La structure réelle reste à confirmer à la première pose ; la
différence avec un pari, c'est qu'une structure inattendue ne casse plus rien en
silence : le script mesure, refuse, et dit pourquoi.

Le site de référence `construction.le-trait-union.fr` **n'a pas pu être
consulté** : il est bloqué par le proxy de sortie de l'environnement de
développement (`CONNECT tunnel failed, 403`). Cette version répond à la phrase
« il faut que les lignes soient de gauche à droite », pas à une observation du
rendu de ce site.
