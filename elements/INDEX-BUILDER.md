# Panneau « Éléments WeFrame » dans le builder

Module `modules/wf-builder-index.php` : un panneau qui liste les éléments
WeFrame de la mise en page ouverte, et saute directement aux réglages de l'un
d'eux — sans descendre dans l'arbre du builder.

```
┌────────────────────────────────────────────┐
│ ‹  Éléments WeFrame                        │
├────────────────────────────────────────────┤
│  Filtrer…                                  │
│                                            │
│  ▪ Panneau ++                         ⊙   │
│      Section 1 › Ligne 1                   │
│  ▪ Carrousel perspective              ⊙   │
│      Section 2 › Ligne 1                   │
│  ▪ Sections côte à côte               ⊙   │
│      Section 2 › Ligne 2                   │
│                                            │
│  3 éléments                                │
└────────────────────────────────────────────┘
```

| Geste | Effet |
|---|---|
| **Clic** sur une ligne | ouvre les réglages de l'élément |
| **Survol** | le surligne dans l'aperçu |
| **⊙** | l'amène à l'écran |
| **Filtre** | sur le titre *et* sur l'emplacement |

Deux façons d'ouvrir le panneau : le bouton **Éléments WeFrame** dans l'en-tête
de la barre latérale — présent tant qu'un builder est ouvert — et le raccourci
**Ctrl/⌘ + Maj + E**.

Il fonctionne partout où un builder s'ouvre : page, template, widget, item de
mégamenu, pied de page.

---

## Il ne réinvente rien

Les trois actions appellent **exactement les fonctions des vignettes de l'arbre
natif**, relevées dans `customizer.js` :

```js
clic    ->  Builder.edit(node)
survol  ->  trigger('hoverNode',  [node, Builder])
sortie  ->  trigger('leaveNode',  [node, Builder])
viseur  ->  trigger('scrollNode', [node, Builder])
```

Conséquence : le panneau ouvert par un clic est le même que celui de l'arbre, il
ouvre au passage toute la chaîne des parents, et la modification est enregistrée
par le même chemin. Aucune duplication de logique, donc rien à maintenir en
parallèle.

---

## Aucune modification du cœur

Trois points d'accroche, tous ceux que YOOtheme utilise pour lui-même :

| Ce qu'on utilise | Où YOOtheme s'en sert |
|---|---|
| l'événement `customizer.init` | `packages/theme/bootstrap.php` |
| `Metadata::set('script:…', '…')` — un script en ligne | `LoadConfigData` pose `window.yootheme.config` ainsi |
| `window.$fields` | seul objet exposé sans condition par `customizer.js` |

Le module s'enregistre depuis notre propre `bootstrap.php`, que le plugin charge
déjà avec `Application::getInstance()->load()` :

```php
'events' => [
    'customizer.init' => [
        WF_Builder_Index_Listener::class => '@handle',
    ],
],
```

La forme — une classe, `'@methode'`, les dépendances par le constructeur — est
celle qu'attend `EventLoader` et celle de `LoadCustomizerScript`.

---

## Trois contraintes relevées dans le code, qui expliquent l'écriture

Ce ne sont pas des choix de style : chacune aurait produit un échec silencieux.

**1. Vue est livré en build runtime seul.** `assets/admin/js/vue.js` ne contient
ni `compileToFunctions`, ni `parseHTML`, ni `generateCodeFrame`. Une option
`template` serait ignorée sans le moindre message. Tout le rendu passe donc par
`render(h)`.

**2. Un panneau est un frère du builder, pas son enfant.** La barre latérale
empile ses panneaux côte à côte — un `inject('Builder')` ne trouverait donc
rien. YOOtheme contourne en passant le store en prop (`editNode` :
`props:{node, builder, values}`), mais nous ouvrons le panneau depuis
l'extérieur : nous n'avons pas cette prop. Le store est retrouvé en parcourant
l'arbre des composants, avec deux pistes dans l'ordre — une prop `builder`, puis
la valeur d'un `provide('Builder')` — et un contrôle de forme (`edit`, `type`,
`node`) pour ne pas prendre n'importe quel objet pour un builder.

**3. Le bus d'événements n'est pas exposé.** On emprunte donc, une seule fois et
depuis l'extérieur, la méthode `open()` du champ `button-panel` : elle fait
exactement `ie.trigger('openPanel', descripteur)` et n'utilise pas `this`. Une
fois le panneau monté, le composant dispose de `$trigger` et n'en a plus besoin.

Ce qui est **écarté** : `globalThis.$store` et `$pinia`, qui ne sont posés que
par le greffon Vue devtools — ce n'est pas une API, bâtir dessus casserait sans
prévenir.

---

## Si YOOtheme déplace un point d'accroche

Le module se tait et dit lequel manque, plutôt que de casser le builder :

```
WF index des éléments : le champ « button-panel » est introuvable dans
  window.$fields : le panneau ne peut pas s'ouvrir.
WF index des éléments : l'en-tête de la barre latérale (.yo-sidebar-header)
  est introuvable : pas de bouton, le raccourci clavier reste actif.
WF index des éléments : window.$fields n'est jamais apparu.
WF index des éléments : aucun builder ouvert : le panneau reste vide.
```

Les deux points d'entrée sont indépendants exprès : si l'ancrage du bouton
disparaît, le raccourci continue de fonctionner. Et une poignée reste dans la
console : `WFElements.ouvrir()`.

---

## Ce qui est reconnu comme « à nous »

Le **groupe** d'abord — `WeFrame · …`, lu dans `element.json` — parce qu'il
survit à un renommage de dossier. Le **préfixe** du nom interne (`wf_`) sert de
repli pour un élément sans groupe.

Les deux listes sont dans `wf_bidx_config()`, avec les libellés : rien à
chercher dans le JavaScript pour ajouter un préfixe ou traduire une phrase.

---

## Installation

```
elements/modules/wf-builder-index.php  ->  _sources\wf-yoo-elements\modules\
```

Deux ajouts dans `modules/bootstrap.php` :

```php
// Index des elements WeFrame dans le builder (panneau contextuel)
require_once __DIR__ . '/wf-builder-index.php';
```

```php
return [
    'events' => [
        'customizer.init' => [
            WF_Builder_Index_Listener::class => '@handle',
        ],
    ],

    'extend' => [ /* … l'existant … */ ],
];
```

Rien à ajouter dans `assets/` : le script est imprimé en ligne, et seulement
dans le customizer.

---

## Vérifié

**61 assertions** (`bidx-probe.mjs`) sur le script **réellement produit par
`wf_bidx_script()`**, exécuté dans un environnement simulé, contre un faux store
Builder qui reproduit la forme relevée dans `customizer.js` — `node`, `types`,
`type()`, `path()`, `index()`, `key()`, `edit()` :

- **reconnaissance** : par préfixe, par groupe sans préfixe, et le rejet d'un
  élément natif, d'une autre marque, d'un type inconnu ;
- **parcours** : trois éléments relevés dans l'ordre du document, un arbre vide
  et un builder absent qui ne cassent rien ;
- **emplacements distincts** : `Section 1 › Ligne 1`, `Section 2 › Ligne 1`,
  `Section 2 › Ligne 2` ;
- **rendu** : champ de filtre, liste, une ligne par élément, un viseur par
  ligne, le décompte ;
- **filtre** : sur le titre, sur l'emplacement, et le message quand il ne reste
  rien ;
- **les trois actions** : `edit` sur le bon nœud, `hoverNode` / `leaveNode` avec
  le marquage de la ligne, `scrollNode` sans que le clic remonte à la ligne ;
- **sans builder** : le message qui explique quoi faire, et l'avertissement ;
- **le raccourci** : Ctrl+Maj+E et ⌘+Maj+e ouvrent, Ctrl+E seul ne fait rien ;
- **le bouton** : absent tant qu'aucun builder n'est ouvert ;
- **le repli** : si `window.$fields` perd `button-panel`, l'ouverture échoue
  proprement et la console dit pourquoi.

Deux défauts trouvés par le banc d'essai, tous deux corrigés :

**Une récursion sans fin.** Le repli de la clé de ligne lisait `self.elements`,
c'est-à-dire la propriété calculée en cours d'évaluation. Elle ne se déclenchait
que si `Builder.key()` venait à manquer — donc jamais en conditions normales, et
sûrement le jour d'une mise à jour.

**Un emplacement qui ne situait rien.** Je gardais les deux *derniers* ancêtres :
la ligne et la colonne, c'est-à-dire les moins discriminants. Deux éléments de
sections différentes affichaient le même emplacement. Ce sont les deux ancêtres
les plus *extérieurs* qui situent — la section d'abord.

**Pas vérifié** : le rendu dans un vrai customizer YOOtheme. Le banc d'essai
exécute le vrai script et reproduit fidèlement les contrats relevés dans
`customizer.js`, mais il ne remplace pas une ouverture du builder. À contrôler
en premier : l'apparition du bouton, et le clic qui ouvre bien les réglages.
