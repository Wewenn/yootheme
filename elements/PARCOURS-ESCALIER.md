# Parcours en escalier — plusieurs sections sur un plateau

Nouveau module `modules/wf-section-ribbon.php` : un groupe de champs
« Parcours en escalier » dans l'onglet **Paramètres** de la Section native.

Au lieu de descendre, la page enchaîne les sections sur un plateau à deux
dimensions, et le défilement y promène une caméra.

```
De base                    Ce que le parcours donne

S1                         S1 → S2 ┐
S2                                 ↓
S3            devient             S3
S4                                 ↓
S5                                S4 → S5 → S6
S6
```

**La descente se fait à la colonne où la ligne s'arrête.** S3 se pose sous S2,
S4 sous S3, puis la ligne repart vers la droite. C'est un escalier qui descend
vers la droite, pas un serpent qui fait des allers-retours.

---

## Ce n'est pas le défilement latéral

Deux modules, deux choses différentes, et il faut les distinguer avant de
choisir :

| | `wf-section-scroll.php` | `wf-section-ribbon.php` |
|---|---|---|
| Ce qui bouge | **l'intérieur** d'une section : ses colonnes deviennent des panneaux | **des sections entières**, posées côte à côte |
| Portée | une section, rien n'en sort | une suite de sections voisines |
| Réglage | « cette section défile à l'horizontale » | « cette section se pose à droite / en dessous » |

Une section prise dans un parcours ignore son propre défilement latéral : le
parcours l'emporte, et `wf_shs_css()` ne produit rien pour elle. Les deux
modules cohabitent donc sans se marcher dessus.

---

## Pourquoi c'est un script qui monte le plateau

**Une Section YOOtheme ne peut pas en contenir une autre.** Le plateau ne peut
donc pas être un élément du builder : il n'existe aucun endroit où le déclarer.

C'est le script qui, à l'exécution, repère les sections voisines déclarées d'un
même parcours, fabrique le plateau autour d'elles, et les y place :

```
.wf-rb-stage      hauteur = un écran + la longueur du chemin  → la place à défiler
  .wf-rb-view     sticky, un écran de haut, débordement caché → le hublot
    .wf-rb-board  translaté en x et y                         → le plateau
      section     absolue, à sa case                          → les sections
```

D'où la forme du réglage : il ne dit pas « cette section défile », il dit
**« cette section se pose là »**. Le reste est mesuré.

---

## Le défilement n'est jamais détourné

Ni `wheel`, ni `preventDefault`, ni `scrollTop` écrit à la main. Le parcours
devient une zone verticale plus haute que l'écran ; la position de la caméra sur
le plateau se déduit de la progression de cette zone dans l'écran. Le clavier,
l'inertie du trackpad, la barre de défilement et le geste tactile restent ceux
du navigateur.

Un pixel de défilement = un pixel parcouru sur le plateau. Un pas vers la droite
coûte donc une largeur de section, un pas vers le bas coûte une hauteur d'écran —
la caméra avance à vitesse constante, quel que soit le virage. Le réglage
**Longueur du parcours** met tout à l'échelle.

---

## Sans script, la page reste la page

Aucune règle CSS n'est posée sur les sections tant que le plateau n'est pas
monté. Le module n'émet que des variables (`--wf-rb-role`, `--wf-rb-w`…) que le
script relit.

Quatre situations, un seul comportement de repli :

- script absent ou en erreur → les sections restent empilées ;
- écran sous le seuil → le plateau est **démonté**, les sections retournent dans
  la page, dans l'ordre, sans styles résiduels ;
- `prefers-reduced-motion: reduce` → le parcours ne se monte pas du tout ;
- structure incohérente → le parcours est refusé, avec la raison en console.

Le repli n'est pas une version dégradée : **c'est la page normale**.

### Pourquoi le mouvement réduit coupe tout ici

Le mode épinglé de `wf-section-scroll` reste actif sous `prefers-reduced-motion`
(c'est une mise en page, pas une décoration), simplement sans glissement
résiduel. Ici c'est différent : une caméra qui se déplace sur **deux axes** est
exactement ce qui déclenche les troubles vestibulaires. Le parcours est donc
désactivé pour ces visiteurs, et la page redevient une colonne — complète et
lisible, rien n'est perdu.

---

## Les réglages

Sur **chaque** section du parcours :

| Champ | Rôle |
|---|---|
| **Parcours en escalier** | Non · Démarre le parcours · À droite de la précédente · En dessous de la précédente |
| **Largeur de cette section** | plein écran · ¾ · ½ · ⅓ · selon le contenu |

Sur la section qui **démarre** le parcours, et sur elle seule — un parcours, un
jeu de réglages :

| Champ | Rôle |
|---|---|
| **Longueur du parcours** | 50 à 400 % ; 100 = un pixel de défilement pour un pixel parcouru |
| **Souplesse du mouvement** | 0 : la caméra colle au défilement · 100 : elle glisse encore un instant après l'arrêt |
| **À partir de** | tablette · bureau (défaut) · grand écran |
| **Progression** | fine barre en haut du hublot, avec sa couleur |

**La hauteur d'une case est toujours d'un écran.** C'est elle qui aligne les
lignes du parcours : sans hauteur commune, la descente n'aurait pas de repère.
Un contenu plus haut qu'un écran déborde sur la case suivante — c'est voulu,
c'est ce qui fait le liant d'une section à l'autre.

---

## Trois refus, et ils se disent

Un parcours mal formé ne produit jamais un plateau à moitié monté. Il est
refusé, et la console nomme la section fautive — cliquable dans l'inspecteur :

```
WF parcours en escalier : un parcours demande au moins deux sections
WF parcours en escalier : aucune section « Démarre le parcours » avant celle-ci
WF parcours en escalier : elle ne suit pas immédiatement la section précédente
```

Le troisième mérite une précision : entre deux sections d'un parcours, le module
tolère tout ce qui **n'occupe aucune place** — une balise `<style>`, un script,
un bloc masqué par une condition d'affichage. C'est exactement ce que rend
YOOtheme entre deux sections. En revanche une section ordinaire glissée au
milieu rompt le parcours, et c'est dit.

---

## Deux pièges trouvés à l'exécution

**1. L'observateur suivait un élément sans boîte.** La boucle d'animation ne se
relance que si le parcours est à l'écran, ce que dit un `IntersectionObserver`.
Il observait « ce qui vient après l'ancre » — qui se trouve être une balise
`<style>`. Un élément en `display:none` n'intersecte jamais rien : `visible`
restait faux, et **la caméra ne repeignait jamais**. Le plateau se montait
parfaitement, les six sections étaient à leur case au pixel près, et rien ne
bougeait au défilement. L'observateur suit maintenant le plateau lui-même.

**2. Des bornes de défilement gardées en mémoire.** La progression se calculait
entre deux bornes mesurées au montage. Or un parcours qui se monte plus haut
dans la page déplace tous ceux qui le suivent, et une image en retard ou une
police de substitution font la même chose. La progression est maintenant lue en
direct sur la position du plateau à l'écran :

```js
var p = -stage.getBoundingClientRect().top / (stage.offsetHeight - hVue);
```

Une lecture de géométrie par image, faite juste avant l'écriture du `transform` :
c'est le bon ordre, et ça vaut mieux qu'une borne fausse sans prévenir.

---

## Ce qu'il faut savoir avant de l'activer

Le parcours a un coût, indépendamment de la qualité de la mise en œuvre :

- la page devient plus haute que son contenu, donc **la barre de défilement
  ment** ;
- les ancres internes et la **recherche dans la page** n'atteignent pas les
  sections hors champ ;
- le module **déplace les sections dans le DOM** pour les poser sur le plateau.
  Un script tiers qui aurait mémorisé leur position avant le montage peut s'en
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
// Parcours en escalier : plusieurs sections sur un plateau
require_once __DIR__ . '/wf-section-ribbon.php';
```

```php
// Parcours en escalier ajoute a la Section native.
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

Le ZIP `dist/wf-yoo-elements-sans-wc-6.45.0.zip` contient tout cela câblé, avec
`wf-section-scroll.php` par la même occasion.

---

## Vérifié

**62 assertions dans un Chromium réel** (`rb-probe.mjs`), sur le CSS et le script
réellement produits, et d'abord sur **la figure exacte du croquis** :

| Ce qui est mesuré | Attendu |
|---|---|
| Position des six cases | (0,0) (1280,0) (1280,800) (1280,1600) (2560,1600) (3840,1600) |
| S3 sous S2, même colonne | oui |
| S4 démarre la ligne du bas à la colonne de S3 | oui |
| Plateau | 4 colonnes × 3 lignes |
| Hauteur de défilement | un écran + 3 largeurs + 2 hauteurs |

Puis le trajet de la caméra : à l'origine au départ, **pile sur S2, S3, S4 et
S5** aux fractions correspondantes du parcours, sur S6 à l'arrivée, avec le
hublot collé à `top: 0` à chaque étape et la dernière section remplissant
exactement le hublot.

Plus :

- **largeurs mélangées** (½, plein écran, ¾) : chaque case à sa largeur, la
  suivante qui commence au bout de la précédente, la descente à la bonne
  colonne, et le chemin qui vaut 640 + 800 ;
- **selon le contenu** : la case mesure 520 px au lieu de 1280, et le chemin
  suit ;
- **souplesse à 80** : la caméra est mesurée *en retard* juste après un saut de
  défilement, puis rejoint sa cible à moins de 12 px ;
- **longueur à 200 %** : hauteur = écran + 2 × chemin ;
- **barre de progression** : présente (3 px), à `scaleX(0,5)` à mi-parcours ;
- **les trois refus**, chacun avec son avertissement en console ;
- **la page autour** : le plateau reste entre la section qui le précède et celle
  qui le suit, aucun débordement horizontal ;
- **sous le seuil** (560 px) : plus aucun plateau, plus aucune case, styles de
  position nettoyés, les six sections de retour dans le corps de page, empilées
  dans l'ordre, sans débordement ;
- **retour au grand écran** : le plateau se remonte, dans le bon ordre ;
- **`prefers-reduced-motion`** : aucun plateau monté, sections empilées.

**Pas vérifié** : le rendu dans un vrai YOOtheme, et le panneau du builder
(champs, conditions `enable`, groupe dans l'onglet Paramètres). Le banc d'essai
imite le préfixeur (`.el-element` → `#id`) et pose entre chaque section la
balise `<style>` que produit le rendu — c'est d'ailleurs elle qui a révélé le
premier piège. La structure réelle reste à confirmer à la première pose ; la
différence avec un pari, c'est qu'une structure inattendue ne casse plus rien en
silence : le script mesure, refuse, et dit pourquoi.
