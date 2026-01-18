# Composant Avant/Après pour YOOtheme

Un élément personnalisé pour YOOtheme Pro permettant de créer des comparaisons d'images interactives avec un slider "avant/après".

![Preview](preview.gif)

## ✨ Fonctionnalités

- 🖼️ **Comparaison d'images** : Affiche deux images avec un slider interactif
- 🔄 **Orientation flexible** : Horizontal ou vertical
- 🎨 **Styles personnalisables** : 3 styles de curseur différents
- 📱 **Responsive** : Fonctionne parfaitement sur mobile et tablette
- ⌨️ **Accessible** : Navigation au clavier avec les flèches
- 🎯 **Labels** : Ajout optionnel de labels sur les images
- ⚡ **Performance** : Utilise les technologies modernes (clip-path CSS)
- 🎭 **Animation d'intro** : Pulsation pour indiquer l'interactivité

## 📦 Installation

### Option 1 : Via le Child Theme (Recommandé)

1. **Créer un child theme** si vous n'en avez pas déjà un :

```bash
wp-content/themes/
└── yootheme-child/
    ├── style.css
    └── functions.php
```

2. **Copier le dossier du composant** :

```bash
wp-content/themes/yootheme-child/
└── builder/
    └── before-after/
        ├── element.json
        ├── element.php
        ├── icon.svg
        ├── templates/
        │   └── template.php
        └── assets/
            ├── css/
            │   └── before-after.css
            └── js/
                └── before-after.js
```

3. **Enregistrer l'élément** dans `functions.php` :

```php
<?php
// Charger les éléments personnalisés YOOtheme
add_action('after_setup_theme', function () {
    $dir = get_stylesheet_directory() . '/builder';

    if (is_dir($dir)) {
        foreach (glob("{$dir}/*/element.json") as $file) {
            app('builder')->addElement(dirname($file));
        }
    }
});
```

### Option 2 : Via un plugin custom

1. **Créer un plugin** :

```php
<?php
/*
Plugin Name: YOOtheme Before After Element
Description: Ajoute un élément de comparaison avant/après
Version: 1.0.0
*/

add_action('after_setup_theme', function () {
    $element = __DIR__ . '/before-after-element';

    if (is_dir($element) && function_exists('app')) {
        app('builder')->addElement($element);
    }
});
```

2. **Structure du plugin** :

```
wp-content/plugins/
└── yootheme-before-after/
    ├── yootheme-before-after.php
    └── before-after-element/
        ├── element.json
        ├── element.php
        └── [autres fichiers...]
```

## 🎯 Utilisation

### Dans le Builder YOOtheme

1. Ouvrir le **YOOtheme Builder**
2. Cliquer sur **"Ajouter un élément"**
3. Trouver **"Avant/Après"** dans la catégorie "Custom"
4. Glisser-déposer l'élément dans votre layout

### Configuration

#### Onglet "Contenu"

- **Image Avant** : Sélectionnez l'image "avant" depuis la bibliothèque média
- **Image Après** : Sélectionnez l'image "après"
- **Label Avant** : Texte optionnel affiché sur l'image avant (ex: "Avant")
- **Label Après** : Texte optionnel affiché sur l'image après (ex: "Après")

#### Onglet "Paramètres"

- **Position initiale du slider** : 0-100% (défaut: 50%)
- **Orientation** : Horizontal ou Vertical
- **Style du curseur** :
  - Défaut : Curseur avec flèches et ligne
  - Cercle : Curseur circulaire simple
  - Flèches : Curseur avec grandes flèches
- **Largeur** : Auto, Petite, Moyenne, Grande, Pleine largeur
- **Hauteur** : Hauteur personnalisée en pixels
- **Bordures arrondies** : Aucune, Petite, Moyenne, Grande, Cercle

## 💡 Exemples d'utilisation

### Exemple 1 : Comparaison simple

```html
<!-- Configuration minimale -->
Image Avant: renovation-avant.jpg
Image Après: renovation-apres.jpg
Orientation: Horizontal
Position: 50%
```

### Exemple 2 : Avec labels et style custom

```html
<!-- Configuration complète -->
Image Avant: photo-originale.jpg
Label Avant: "Original"
Image Après: photo-retouchee.jpg
Label Après: "Retouché"
Orientation: Horizontal
Style du curseur: Flèches
Position: 30%
Largeur: Grande
Bordures: Moyenne
```

### Exemple 3 : Mode vertical

```html
<!-- Pour les comparaisons haut/bas -->
Image Avant: jardin-avant.jpg
Label Avant: "Printemps"
Image Après: jardin-apres.jpg
Label Après: "Été"
Orientation: Vertical
Position: 50%
```

## 🎨 Personnalisation CSS

Vous pouvez personnaliser l'apparence via le CSS custom de YOOtheme :

```css
/* Changer la couleur du slider */
.ba-slider {
    background: #1e87f0 !important;
}

/* Personnaliser le handle */
.ba-handle {
    background: #1e87f0 !important;
    color: white !important;
}

/* Modifier les labels */
.ba-label .uk-label {
    font-size: 1rem;
    padding: 5px 15px;
    border-radius: 20px;
}

/* Ajouter une ombre portée */
.ba-container {
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}
```

## ⌨️ Contrôles

### Souris
- **Clic + Glisser** : Déplacer le slider
- **Hover** : Le curseur s'agrandit légèrement

### Tactile
- **Toucher + Glisser** : Déplacer le slider sur mobile/tablette

### Clavier
- **Tab** : Sélectionner le composant
- **←/→** (horizontal) : Déplacer le slider gauche/droite
- **↑/↓** (vertical) : Déplacer le slider haut/bas

## 🔧 API JavaScript

Vous pouvez contrôler le composant via JavaScript :

```javascript
// Obtenir l'instance
var instance = jQuery('.ba-container').data('beforeAfter');

// Définir une position
instance.setPosition(75); // 75%

// Détruire l'instance
instance.destroy();

// Créer manuellement une instance
jQuery('.ba-container').beforeAfter();
```

## 📱 Cas d'usage

1. **Rénovation / Avant-Après** : Montrer des travaux de rénovation
2. **Retouche photo** : Comparer photo originale vs retouchée
3. **Design web** : Montrer l'évolution d'un design
4. **Comparaison de produits** : Comparer deux produits similaires
5. **Évolution temporelle** : Montrer l'évolution dans le temps
6. **Traitement d'image** : Démontrer l'efficacité d'un filtre
7. **Avant/Après médical** : Résultats de traitements (avec précautions)
8. **Restauration** : Œuvres d'art ou objets restaurés

## 🛠️ Développement

### Structure des fichiers

```
before-after-element/
├── element.json          # Configuration de l'élément
├── element.php          # Logique PHP et enqueue des assets
├── icon.svg            # Icône dans le builder
├── README.md           # Documentation
├── templates/
│   └── template.php    # Template HTML
└── assets/
    ├── css/
    │   └── before-after.css    # Styles
    └── js/
        └── before-after.js     # JavaScript interactif
```

### Technologies utilisées

- **PHP** : Logique serveur et rendu
- **JavaScript** : Interaction du slider (jQuery)
- **CSS3** : Styles et clip-path pour l'effet
- **UIkit** : Framework CSS/JS de base
- **YOOtheme API** : Intégration avec le builder

## ⚡ Performance

- ✅ Pas de bibliothèques externes
- ✅ Utilise `clip-path` CSS (hardware accelerated)
- ✅ Debounce sur le resize
- ✅ Lazy loading compatible (uk-img)
- ✅ Assets chargés uniquement si l'élément est utilisé

## 🐛 Résolution de problèmes

### L'élément n'apparaît pas dans le builder

1. Vérifier que le dossier est au bon endroit
2. Vérifier que `element.json` est valide
3. Vider le cache de YOOtheme (YOOtheme → Settings → Advanced → Clear Cache)
4. Vérifier les logs PHP pour les erreurs

### Le slider ne fonctionne pas

1. Vérifier que jQuery est chargé
2. Ouvrir la console du navigateur pour voir les erreurs JS
3. Vérifier que les assets (CSS/JS) sont bien chargés
4. Désactiver les autres plugins pour identifier les conflits

### Les images ne s'affichent pas correctement

1. Vérifier que les deux images ont le même ratio
2. Utiliser la hauteur personnalisée si nécessaire
3. Vérifier les chemins des images dans la console

## 📄 Licence

Ce composant est libre d'utilisation pour vos projets YOOtheme.

## 🤝 Contribution

N'hésitez pas à améliorer ce composant et à partager vos modifications !

## 📞 Support

Pour toute question ou problème :
1. Vérifier la documentation YOOtheme
2. Consulter la documentation UIkit
3. Ouvrir une issue sur le repository

---

**Version:** 1.0.0
**Dernière mise à jour:** 2026-01-18
**Compatibilité:** YOOtheme Pro 2.0+, WordPress 5.0+
