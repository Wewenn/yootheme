# Guide YOOtheme pour WordPress

## Introduction
YOOtheme Pro est un page builder pour WordPress qui utilise le framework UIkit. Ce guide vous aide à comprendre comment les deux fonctionnent ensemble.

---

## 🏗️ Architecture YOOtheme

### Structure de base
```
wp-content/
├── themes/
│   └── yootheme/          # Thème YOOtheme
└── plugins/
    └── yootheme/          # Plugin YOOtheme Pro
```

### Child Theme YOOtheme
```
wp-content/themes/yootheme-child/
├── functions.php          # Personnalisations PHP
├── style.css             # Styles personnalisés
├── builder/              # Éléments custom du builder
│   └── my-element/
│       ├── element.json
│       ├── element.php
│       └── templates/
│           └── template.php
└── templates/            # Templates custom
    ├── header.php
    └── footer.php
```

---

## 🎨 Utilisation des classes UIkit dans YOOtheme

### Dans le Builder YOOtheme
YOOtheme utilise nativement UIkit. Vous pouvez ajouter des classes UIkit via :

1. **L'onglet "Advanced"** de chaque élément
   - Section "HTML Element"
   - Champ "CSS Class"

2. **Le mode Custom HTML**
   - Ajouter un élément "HTML"
   - Écrire le code HTML avec les classes UIkit

### Exemple : Créer une card custom
```html
<div class="uk-card uk-card-default uk-card-hover uk-card-body">
    <h3 class="uk-card-title">Mon titre</h3>
    <p>Mon contenu avec <span class="uk-text-primary">du texte coloré</span></p>
    <a href="#" class="uk-button uk-button-primary">En savoir plus</a>
</div>
```

---

## 🔧 Hooks WordPress utiles pour YOOtheme

### Ajouter des scripts/styles
```php
// Dans functions.php du child theme
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('custom-style', get_stylesheet_directory_uri() . '/css/custom.css');
    wp_enqueue_script('custom-script', get_stylesheet_directory_uri() . '/js/custom.js', ['jquery'], null, true);
});
```

### Modifier le comportement YOOtheme
```php
// Ajouter des classes custom au body
add_filter('body_class', function($classes) {
    $classes[] = 'my-custom-class';
    return $classes;
});

// Hook YOOtheme spécifique
add_filter('theme/yootheme/config', function($config) {
    // Modifier la config YOOtheme
    return $config;
});
```

---

## 📦 Créer un élément custom YOOtheme

### Structure de base (element.json)
```json
{
    "name": "my-element",
    "title": "Mon Élément",
    "icon": "${url:icon.svg}",
    "element": true,
    "fields": {
        "title": {
            "label": "Titre",
            "type": "text"
        },
        "content": {
            "label": "Contenu",
            "type": "editor"
        },
        "style": {
            "label": "Style",
            "type": "select",
            "options": {
                "Default": "default",
                "Primary": "primary",
                "Secondary": "secondary"
            }
        }
    },
    "fieldset": {
        "default": {
            "fields": ["title", "content", "style"]
        }
    }
}
```

### Template (templates/template.php)
```php
<?php
// Variables disponibles : $props, $children, $attrs

$el = $this->el('div', [
    'class' => [
        'uk-card',
        'uk-card-' . $props['style'],
        'uk-card-body'
    ]
]);
?>

<?= $el($props, $attrs) ?>

    <?php if ($props['title']) : ?>
        <h3 class="uk-card-title"><?= $props['title'] ?></h3>
    <?php endif ?>

    <?php if ($props['content']) : ?>
        <div class="uk-margin"><?= $props['content'] ?></div>
    <?php endif ?>

</div>
```

---

## 🎯 Patterns courants

### Section hero avec overlay
```html
<div class="uk-section uk-section-large uk-background-cover uk-position-relative"
     style="background-image: url('image.jpg');"
     uk-parallax="bgy: -200">

    <div class="uk-overlay uk-overlay-primary uk-position-cover"></div>

    <div class="uk-container uk-position-relative" style="z-index: 1;">
        <h1 class="uk-heading-large uk-text-center uk-light">Titre Hero</h1>
        <p class="uk-text-lead uk-text-center uk-light">Sous-titre</p>
        <div class="uk-text-center">
            <a href="#" class="uk-button uk-button-primary uk-button-large">Call to Action</a>
        </div>
    </div>
</div>
```

### Grid de cards responsive
```html
<div class="uk-section">
    <div class="uk-container">
        <div class="uk-grid uk-grid-match uk-child-width-1-1 uk-child-width-1-2@s uk-child-width-1-3@m" uk-grid>

            <div>
                <div class="uk-card uk-card-default uk-card-hover">
                    <div class="uk-card-media-top">
                        <img src="image1.jpg" alt="">
                    </div>
                    <div class="uk-card-body">
                        <h3 class="uk-card-title">Card 1</h3>
                        <p>Description</p>
                    </div>
                </div>
            </div>

            <!-- Répéter pour les autres cards -->

        </div>
    </div>
</div>
```

### Formulaire stylisé
```html
<form class="uk-form-stacked" action="" method="post">

    <div class="uk-margin">
        <label class="uk-form-label" for="name">Nom</label>
        <div class="uk-form-controls">
            <input class="uk-input" id="name" type="text" placeholder="Votre nom" required>
        </div>
    </div>

    <div class="uk-margin">
        <label class="uk-form-label" for="email">Email</label>
        <div class="uk-form-controls">
            <input class="uk-input" id="email" type="email" placeholder="votre@email.com" required>
        </div>
    </div>

    <div class="uk-margin">
        <label class="uk-form-label" for="message">Message</label>
        <div class="uk-form-controls">
            <textarea class="uk-textarea" id="message" rows="5" required></textarea>
        </div>
    </div>

    <div class="uk-margin">
        <button class="uk-button uk-button-primary" type="submit">Envoyer</button>
    </div>

</form>
```

---

## 🔌 Intégration WordPress

### Afficher le contenu WordPress avec UIkit

#### Loop WordPress
```php
<?php if (have_posts()) : ?>
    <div class="uk-grid uk-child-width-1-2@s uk-child-width-1-3@m" uk-grid>
        <?php while (have_posts()) : the_post(); ?>
            <div>
                <div class="uk-card uk-card-default">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="uk-card-media-top">
                            <?php the_post_thumbnail('medium'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="uk-card-body">
                        <h3 class="uk-card-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <p class="uk-text-meta">
                            <?php echo get_the_date(); ?>
                        </p>
                        <?php the_excerpt(); ?>
                        <a href="<?php the_permalink(); ?>" class="uk-button uk-button-text">Lire plus</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>
```

#### Menu WordPress
```php
<?php
wp_nav_menu([
    'theme_location' => 'primary',
    'container' => false,
    'menu_class' => 'uk-navbar-nav',
    'fallback_cb' => false
]);
?>
```

### Shortcodes personnalisés
```php
// Dans functions.php
function custom_button_shortcode($atts, $content = null) {
    $atts = shortcode_atts([
        'style' => 'primary',
        'size' => '',
        'url' => '#'
    ], $atts);

    $classes = ['uk-button', 'uk-button-' . $atts['style']];
    if ($atts['size']) {
        $classes[] = 'uk-button-' . $atts['size'];
    }

    return sprintf(
        '<a href="%s" class="%s">%s</a>',
        esc_url($atts['url']),
        esc_attr(implode(' ', $classes)),
        $content
    );
}
add_shortcode('custom_button', 'custom_button_shortcode');

// Utilisation : [custom_button style="primary" url="/contact"]Contactez-nous[/custom_button]
```

---

## 🎨 Custom CSS dans YOOtheme

### Via le customizer
1. YOOtheme → Customizer → Custom CSS
2. Ajouter votre CSS personnalisé

```css
/* Exemple : Personnaliser les cards */
.uk-card-custom {
    border-radius: 15px;
    transition: transform 0.3s ease;
}

.uk-card-custom:hover {
    transform: translateY(-5px);
}

/* Responsive custom */
@media (min-width: 960px) {
    .uk-heading-custom {
        font-size: 3rem;
    }
}
```

---

## 📱 JavaScript custom

### Initialiser des composants UIkit
```javascript
// Dans votre fichier JS custom
UIkit.modal('#my-modal').show();
UIkit.notification('Message de notification', {status: 'success'});

// Événements UIkit
UIkit.util.on('#my-modal', 'show', function() {
    console.log('Modal ouverte');
});
```

### Intégration Ajax
```javascript
// Charger plus de posts
jQuery('.load-more').on('click', function(e) {
    e.preventDefault();

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'load_more_posts',
            page: currentPage
        },
        success: function(response) {
            jQuery('.posts-container').append(response);
            UIkit.update(); // Rafraîchir les composants UIkit
        }
    });
});
```

---

## 🚀 Optimisations

### Performance
1. **Lazy loading des images** : Utiliser `uk-img` ou `loading="lazy"`
2. **Minification** : Activer la minification dans YOOtheme
3. **Cache** : Utiliser un plugin de cache (WP Rocket, W3 Total Cache)
4. **CDN** : Configurer un CDN pour les assets

### SEO
1. Utiliser les bons headings (H1, H2, H3)
2. Ajouter des alt aux images
3. Optimiser les meta descriptions
4. Utiliser Yoast SEO ou Rank Math

---

## 🛠️ Débogage

### Mode debug WordPress
```php
// Dans wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Console du navigateur
```javascript
// Vérifier la version UIkit
console.log(UIkit.version);

// Lister les composants UIkit sur la page
UIkit.components;
```

---

## 📚 Ressources

- [YOOtheme Pro Documentation](https://yootheme.com/support/yootheme-pro)
- [UIkit Documentation](https://getuikit.com/docs/)
- [WordPress Codex](https://codex.wordpress.org/)
- [WordPress Developer Resources](https://developer.wordpress.org/)

---

**Version:** 1.0
**Dernière mise à jour:** 2026-01-18
