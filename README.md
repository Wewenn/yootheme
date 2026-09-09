# YOOtheme Development avec UIkit

Infrastructure de développement pour créer des sites WordPress avec YOOtheme Pro et UIkit.

## 📚 Documentation

Ce repository contient toute la documentation et les exemples nécessaires pour développer efficacement avec YOOtheme et UIkit.

### 📖 Guides disponibles

- **[Référence UIkit](docs/uikit/UIKIT_REFERENCE.md)** - Guide complet des classes UIkit
  - Layout & Grid
  - Typography
  - Buttons
  - Images & Media
  - Components (Cards, Sections, Articles, etc.)
  - Navigation
  - Forms
  - Utilities
  - JavaScript Components
  - Animations
  - Responsive

- **[Guide YOOtheme WordPress](docs/YOOTHEME_WORDPRESS.md)** - Intégration WordPress
  - Architecture YOOtheme
  - Utilisation des classes UIkit
  - Hooks WordPress
  - Création d'éléments custom
  - Patterns courants
  - Intégration WordPress
  - Custom CSS & JavaScript
  - Optimisations

## 🎯 Structure du projet

```
yootheme/
├── docs/                           # Documentation
│   ├── uikit/
│   │   └── UIKIT_REFERENCE.md     # Référence complète UIkit
│   └── YOOTHEME_WORDPRESS.md      # Guide YOOtheme/WordPress
│
├── examples/                       # Exemples de composants
│   └── before-after-element/      # Composant Avant/Après
│       ├── element.json
│       ├── element.php
│       ├── icon.svg
│       ├── README.md
│       ├── templates/
│       │   ├── template.php
│       │   └── content.php
│       └── assets/
│           ├── css/
│           │   └── before-after.css
│           └── js/
│               └── before-after.js
│
├── elements/                       # Éléments prêts pour le plugin wf-yoo-elements
│   ├── README.md                   # Installation + description des réglages
│   └── modules/element/
│       ├── panel-pp/               # Panneau ++ (fond image, sous-mise en page, responsive)
│       ├── perspective-slider/     # Carrousel perspective infini
│       └── perspective-slider-item/
│
└── README.md                       # Ce fichier
```

## 🚀 Démarrage rapide

### Prérequis

- WordPress 5.0+
- YOOtheme Pro 2.0+
- PHP 7.4+

### Utilisation de la documentation

1. **Pour développer avec UIkit** :
   - Consultez [docs/uikit/UIKIT_REFERENCE.md](docs/uikit/UIKIT_REFERENCE.md)
   - Trouvez les classes dont vous avez besoin
   - Utilisez-les dans le builder YOOtheme ou en HTML custom

2. **Pour créer des éléments custom** :
   - Consultez [docs/YOOTHEME_WORDPRESS.md](docs/YOOTHEME_WORDPRESS.md)
   - Suivez les exemples de création d'éléments
   - Voir l'exemple complet dans `examples/before-after-element/`

3. **Pour utiliser les exemples** :
   - Copiez le dossier de l'exemple dans votre child theme
   - Suivez le README de l'exemple
   - Personnalisez selon vos besoins

## 💡 Exemples de composants

### Composant Avant/Après

Un élément YOOtheme pour créer des comparaisons d'images interactives.

**Fonctionnalités** :
- ✅ Orientation horizontale et verticale
- ✅ 3 styles de curseur
- ✅ Labels personnalisables
- ✅ Navigation clavier
- ✅ Responsive et tactile
- ✅ Performance optimale

**Documentation** : [examples/before-after-element/README.md](examples/before-after-element/README.md)

## 📋 Comment utiliser ce repository

### Pour demander du code

Lorsque vous me demandez de créer du code, je peux maintenant :

1. **Utiliser les classes UIkit** correctement grâce à la référence complète
2. **Créer des éléments YOOtheme** en suivant les bonnes pratiques
3. **Intégrer avec WordPress** de manière optimale
4. **Fournir du code cohérent** avec les standards YOOtheme/UIkit

### Exemples de demandes

```
"Crée-moi une section hero avec un parallax"
→ J'utiliserai les classes UIkit appropriées

"Fais un composant YOOtheme pour afficher des témoignages"
→ Je créerai un élément custom complet

"Comment faire un grid responsive avec des cards ?"
→ Je fournirai le code UIkit avec les bonnes classes
```

## 🎨 Classes UIkit les plus utilisées

### Layout
```html
<div class="uk-container">...</div>
<div class="uk-grid" uk-grid>...</div>
<div class="uk-width-1-2@s uk-width-1-3@m">...</div>
```

### Components
```html
<div class="uk-card uk-card-default uk-card-body">...</div>
<div class="uk-section uk-section-muted">...</div>
<button class="uk-button uk-button-primary">...</button>
```

### Utilities
```html
<div class="uk-margin">...</div>
<div class="uk-text-center">...</div>
<div class="uk-flex uk-flex-middle">...</div>
```

Voir la [référence complète](docs/uikit/UIKIT_REFERENCE.md) pour plus de détails.

## 🔗 Ressources

### Documentation officielle
- [YOOtheme Pro](https://yootheme.com/support/yootheme-pro)
- [UIkit](https://getuikit.com/docs/)
- [WordPress Developer](https://developer.wordpress.org/)

### Outils utiles
- [UIkit Icons](https://getuikit.com/docs/icon) - Bibliothèque d'icônes
- [UIkit Tests](https://getuikit.com/assets/uikit/tests/) - Tests des composants
- [YOOtheme Changelog](https://yootheme.com/blog/category/releases) - Nouveautés

## 💻 Développement

### Bonnes pratiques

1. **Toujours utiliser les classes UIkit** plutôt que du CSS custom
2. **Penser mobile-first** avec les breakpoints (@s, @m, @l, @xl)
3. **Utiliser les composants YOOtheme** quand ils existent
4. **Créer des éléments custom** pour les besoins récurrents
5. **Tester sur différents appareils** et navigateurs

### Workflow recommandé

```bash
1. Consulter la documentation UIkit/YOOtheme
2. Utiliser le builder YOOtheme pour prototyper
3. Créer des éléments custom si nécessaire
4. Tester et optimiser
5. Documenter les customisations
```

## 🤝 Contribution

N'hésitez pas à ajouter :
- De nouveaux exemples de composants
- Des patterns courants
- Des snippets utiles
- Des améliorations à la documentation

## 📄 Licence

Documentation et exemples libres d'utilisation.

## ✨ Prochaines étapes

Quelques idées pour étendre ce repository :

- [ ] Composant de galerie custom
- [ ] Composant de formulaire avancé
- [ ] Composant de timeline
- [ ] Templates de sections complètes
- [ ] Snippets PHP utiles
- [ ] Guide d'optimisation performance
- [ ] Guide de migration entre versions YOOtheme

---

**Maintenu par** : Claude
**Version** : 1.0.0
**Dernière mise à jour** : 2026-01-18

---

## 🎯 Comment me demander du code

Maintenant que cette infrastructure est en place, vous pouvez me demander :

### ✅ Composants UIkit
```
"Fais-moi une card avec image, titre et bouton"
"Crée une section avec un background parallax"
"Fais un menu responsive"
```

### ✅ Éléments YOOtheme custom
```
"Crée un composant pour afficher des statistiques"
"Fais un élément de pricing table"
"Crée un composant de FAQ avec accordion"
```

### ✅ Intégration WordPress
```
"Affiche les derniers posts en grid"
"Crée un custom post type avec YOOtheme"
"Fais un shortcode pour afficher X"
```

### ✅ Personnalisations
```
"Ajoute du CSS custom pour X"
"Crée une animation au scroll"
"Personnalise le style des boutons"
```

Je pourrai vous fournir du code précis et bien structuré en utilisant toute la documentation que nous avons mise en place ! 🚀
