# Référence UIkit pour YOOtheme

## Introduction
Ce document contient une référence des classes UIkit les plus utilisées dans les projets YOOtheme/WordPress.

---

## 📐 Layout & Grid

### Container
```html
<div class="uk-container">...</div>
<div class="uk-container uk-container-small">...</div>
<div class="uk-container uk-container-large">...</div>
<div class="uk-container uk-container-expand">...</div>
```

### Grid System
```html
<!-- Grid de base -->
<div class="uk-grid">
    <div class="uk-width-1-2">...</div>
    <div class="uk-width-1-2">...</div>
</div>

<!-- Grid avec gutter -->
<div class="uk-grid uk-grid-small">...</div>
<div class="uk-grid uk-grid-medium">...</div>
<div class="uk-grid uk-grid-large">...</div>
<div class="uk-grid uk-grid-collapse">...</div>

<!-- Largeurs -->
<div class="uk-width-1-1">100%</div>
<div class="uk-width-1-2">50%</div>
<div class="uk-width-1-3">33.33%</div>
<div class="uk-width-2-3">66.66%</div>
<div class="uk-width-1-4">25%</div>
<div class="uk-width-3-4">75%</div>
<div class="uk-width-1-5">20%</div>
<div class="uk-width-1-6">16.66%</div>
<div class="uk-width-auto">auto</div>
<div class="uk-width-expand">expand</div>

<!-- Responsive -->
<div class="uk-width-1-2@s">...</div>
<div class="uk-width-1-3@m">...</div>
<div class="uk-width-1-4@l">...</div>
<div class="uk-width-1-5@xl">...</div>
```

### Flex
```html
<div class="uk-flex">...</div>
<div class="uk-flex uk-flex-center">...</div>
<div class="uk-flex uk-flex-right">...</div>
<div class="uk-flex uk-flex-between">...</div>
<div class="uk-flex uk-flex-around">...</div>
<div class="uk-flex uk-flex-middle">...</div>
<div class="uk-flex uk-flex-bottom">...</div>
<div class="uk-flex uk-flex-column">...</div>
<div class="uk-flex uk-flex-wrap">...</div>
```

---

## 🎨 Typography

### Headings
```html
<h1 class="uk-heading-small">...</h1>
<h1 class="uk-heading-medium">...</h1>
<h1 class="uk-heading-large">...</h1>
<h1 class="uk-heading-xlarge">...</h1>
<h1 class="uk-heading-2xlarge">...</h1>

<h1 class="uk-heading-line"><span>...</span></h1>
<h1 class="uk-heading-bullet">...</h1>
<h1 class="uk-heading-divider">...</h1>
```

### Text
```html
<p class="uk-text-lead">...</p>
<p class="uk-text-meta">...</p>
<p class="uk-text-small">...</p>
<p class="uk-text-large">...</p>
<p class="uk-text-bold">...</p>
<p class="uk-text-uppercase">...</p>
<p class="uk-text-capitalize">...</p>
<p class="uk-text-lowercase">...</p>
```

### Text Alignment
```html
<p class="uk-text-left">...</p>
<p class="uk-text-center">...</p>
<p class="uk-text-right">...</p>
<p class="uk-text-justify">...</p>

<!-- Responsive -->
<p class="uk-text-left@s uk-text-center@m">...</p>
```

### Colors
```html
<p class="uk-text-muted">...</p>
<p class="uk-text-emphasis">...</p>
<p class="uk-text-primary">...</p>
<p class="uk-text-secondary">...</p>
<p class="uk-text-success">...</p>
<p class="uk-text-warning">...</p>
<p class="uk-text-danger">...</p>
```

---

## 🔘 Buttons

```html
<!-- Styles -->
<button class="uk-button uk-button-default">Default</button>
<button class="uk-button uk-button-primary">Primary</button>
<button class="uk-button uk-button-secondary">Secondary</button>
<button class="uk-button uk-button-danger">Danger</button>
<button class="uk-button uk-button-text">Text</button>
<button class="uk-button uk-button-link">Link</button>

<!-- Sizes -->
<button class="uk-button uk-button-small">Small</button>
<button class="uk-button uk-button-default">Default</button>
<button class="uk-button uk-button-large">Large</button>

<!-- States -->
<button class="uk-button uk-button-default" disabled>Disabled</button>

<!-- Width -->
<button class="uk-button uk-width-1-1">Full Width</button>

<!-- Group -->
<div class="uk-button-group">
    <button class="uk-button uk-button-default">Button</button>
    <button class="uk-button uk-button-default">Button</button>
</div>
```

---

## 🖼️ Images & Media

### Images
```html
<img src="..." alt="..." class="uk-border-rounded">
<img src="..." alt="..." class="uk-border-circle">
<img src="..." alt="..." class="uk-border-pill">

<!-- Responsive -->
<img src="..." alt="..." width="..." height="..." uk-img>
```

### Background
```html
<div class="uk-background-default">...</div>
<div class="uk-background-muted">...</div>
<div class="uk-background-primary">...</div>
<div class="uk-background-secondary">...</div>

<div class="uk-background-cover" uk-img data-src="image.jpg">...</div>
<div class="uk-background-contain" style="background-image: url(...)">...</div>

<!-- Blend modes -->
<div class="uk-background-blend-multiply">...</div>
<div class="uk-background-blend-screen">...</div>
<div class="uk-background-blend-overlay">...</div>
```

---

## 📦 Components

### Card
```html
<div class="uk-card uk-card-default uk-card-body">
    <h3 class="uk-card-title">Title</h3>
    <p>Content</p>
</div>

<div class="uk-card uk-card-primary uk-card-body">...</div>
<div class="uk-card uk-card-secondary uk-card-body">...</div>

<!-- Card avec hover -->
<div class="uk-card uk-card-default uk-card-hover uk-card-body">...</div>

<!-- Card sizes -->
<div class="uk-card uk-card-small">...</div>
<div class="uk-card uk-card-large">...</div>
```

### Section
```html
<div class="uk-section">
    <div class="uk-container">...</div>
</div>

<!-- Styles -->
<div class="uk-section uk-section-default">...</div>
<div class="uk-section uk-section-muted">...</div>
<div class="uk-section uk-section-primary">...</div>
<div class="uk-section uk-section-secondary">...</div>

<!-- Sizes -->
<div class="uk-section uk-section-small">...</div>
<div class="uk-section uk-section-large">...</div>
<div class="uk-section uk-section-xlarge">...</div>
```

### Article
```html
<article class="uk-article">
    <h1 class="uk-article-title">Title</h1>
    <p class="uk-article-meta">Meta</p>
    <p class="uk-text-lead">Lead</p>
    <p>Content</p>
</article>
```

### Alert
```html
<div class="uk-alert-primary" uk-alert>
    <a class="uk-alert-close" uk-close></a>
    <p>Message</p>
</div>

<div class="uk-alert-success" uk-alert>...</div>
<div class="uk-alert-warning" uk-alert>...</div>
<div class="uk-alert-danger" uk-alert>...</div>
```

### Badge & Label
```html
<span class="uk-badge">Badge</span>
<span class="uk-label">Label</span>

<span class="uk-label uk-label-success">Success</span>
<span class="uk-label uk-label-warning">Warning</span>
<span class="uk-label uk-label-danger">Danger</span>
```

---

## 🧭 Navigation

### Navbar
```html
<nav class="uk-navbar-container" uk-navbar>
    <div class="uk-navbar-left">
        <a class="uk-navbar-item uk-logo" href="">Logo</a>
        <ul class="uk-navbar-nav">
            <li class="uk-active"><a href="">Active</a></li>
            <li><a href="">Item</a></li>
        </ul>
    </div>
</nav>
```

### Nav
```html
<ul class="uk-nav uk-nav-default">
    <li class="uk-active"><a href="">Active</a></li>
    <li><a href="">Item</a></li>
    <li class="uk-nav-header">Header</li>
    <li class="uk-nav-divider"></li>
</ul>

<ul class="uk-nav uk-nav-primary">...</ul>
```

### Breadcrumb
```html
<ul class="uk-breadcrumb">
    <li><a href="">Home</a></li>
    <li><a href="">Category</a></li>
    <li><span>Page</span></li>
</ul>
```

### Pagination
```html
<ul class="uk-pagination">
    <li><a href=""><span uk-pagination-previous></span></a></li>
    <li><a href="">1</a></li>
    <li class="uk-active"><span>2</span></li>
    <li><a href="">3</a></li>
    <li><a href=""><span uk-pagination-next></span></a></li>
</ul>
```

---

## 📋 Forms

```html
<!-- Input -->
<input class="uk-input" type="text" placeholder="Input">
<input class="uk-input uk-form-width-small" type="text">
<input class="uk-input uk-form-width-medium" type="text">
<input class="uk-input uk-form-width-large" type="text">

<!-- Textarea -->
<textarea class="uk-textarea" rows="5"></textarea>

<!-- Select -->
<select class="uk-select">
    <option>Option</option>
</select>

<!-- Radio & Checkbox -->
<input class="uk-radio" type="radio" name="radio">
<input class="uk-checkbox" type="checkbox">

<!-- States -->
<input class="uk-input uk-form-success" type="text">
<input class="uk-input uk-form-danger" type="text">

<!-- Layout -->
<form class="uk-form-horizontal">
    <div class="uk-margin">
        <label class="uk-form-label">Label</label>
        <div class="uk-form-controls">
            <input class="uk-input" type="text">
        </div>
    </div>
</form>
```

---

## 🎯 Utility

### Margin & Padding
```html
<!-- Margin -->
<div class="uk-margin">...</div>
<div class="uk-margin-small">...</div>
<div class="uk-margin-medium">...</div>
<div class="uk-margin-large">...</div>
<div class="uk-margin-xlarge">...</div>

<div class="uk-margin-top">...</div>
<div class="uk-margin-bottom">...</div>
<div class="uk-margin-left">...</div>
<div class="uk-margin-right">...</div>

<div class="uk-margin-remove">...</div>
<div class="uk-margin-auto">...</div>

<!-- Padding -->
<div class="uk-padding">...</div>
<div class="uk-padding-small">...</div>
<div class="uk-padding-large">...</div>
<div class="uk-padding-remove">...</div>
```

### Visibility
```html
<div class="uk-visible@s">...</div>
<div class="uk-hidden@m">...</div>

<div class="uk-invisible">...</div>
<div class="uk-visible-toggle">
    <div class="uk-hidden-hover">Visible on hover</div>
</div>
```

### Position
```html
<div class="uk-position-top">...</div>
<div class="uk-position-bottom">...</div>
<div class="uk-position-left">...</div>
<div class="uk-position-right">...</div>
<div class="uk-position-center">...</div>

<div class="uk-position-top-left">...</div>
<div class="uk-position-top-center">...</div>
<div class="uk-position-top-right">...</div>
<div class="uk-position-bottom-left">...</div>
<div class="uk-position-bottom-center">...</div>
<div class="uk-position-bottom-right">...</div>

<div class="uk-position-cover">...</div>
```

### Overlay
```html
<div class="uk-position-relative">
    <img src="..." alt="">
    <div class="uk-overlay uk-overlay-primary uk-position-bottom">
        <p>Overlay</p>
    </div>
</div>

<div class="uk-overlay-default">...</div>
<div class="uk-overlay-primary">...</div>
```

---

## 🎭 JavaScript Components

### Modal
```html
<a href="#modal" uk-toggle>Open</a>
<div id="modal" uk-modal>
    <div class="uk-modal-dialog uk-modal-body">
        <h2 class="uk-modal-title">Headline</h2>
        <p>Lorem ipsum...</p>
        <button class="uk-modal-close-default" type="button" uk-close></button>
    </div>
</div>
```

### Offcanvas
```html
<a href="#offcanvas" uk-toggle>Open</a>
<div id="offcanvas" uk-offcanvas>
    <div class="uk-offcanvas-bar">
        <button class="uk-offcanvas-close" type="button" uk-close></button>
        <h3>Title</h3>
        <p>Content</p>
    </div>
</div>
```

### Dropdown
```html
<button type="button">Hover</button>
<div uk-dropdown>Dropdown</div>

<button type="button">Click</button>
<div uk-dropdown="mode: click">Dropdown</div>
```

### Lightbox
```html
<a href="image.jpg" data-caption="Caption" uk-lightbox>
    <img src="thumbnail.jpg" alt="">
</a>

<!-- Gallery -->
<div uk-lightbox>
    <a href="image1.jpg"><img src="thumb1.jpg" alt=""></a>
    <a href="image2.jpg"><img src="thumb2.jpg" alt=""></a>
</div>
```

### Slider
```html
<div class="uk-slider-container" uk-slider>
    <div class="uk-position-relative">
        <div class="uk-slider-container">
            <ul class="uk-slider-items uk-child-width-1-2 uk-child-width-1-3@m">
                <li>
                    <div class="uk-card uk-card-default">
                        <div class="uk-card-body">...</div>
                    </div>
                </li>
            </ul>
        </div>
        <a class="uk-position-center-left" href="#" uk-slidenav-previous uk-slider-item="previous"></a>
        <a class="uk-position-center-right" href="#" uk-slidenav-next uk-slider-item="next"></a>
    </div>
</div>
```

### Accordion
```html
<ul uk-accordion>
    <li class="uk-open">
        <a class="uk-accordion-title" href="#">Item 1</a>
        <div class="uk-accordion-content">
            <p>Content</p>
        </div>
    </li>
    <li>
        <a class="uk-accordion-title" href="#">Item 2</a>
        <div class="uk-accordion-content">
            <p>Content</p>
        </div>
    </li>
</ul>
```

### Tabs
```html
<ul uk-tab>
    <li class="uk-active"><a href="#">Active</a></li>
    <li><a href="#">Item</a></li>
    <li><a href="#">Item</a></li>
</ul>

<ul class="uk-switcher">
    <li>Content 1</li>
    <li>Content 2</li>
    <li>Content 3</li>
</ul>
```

---

## 🎬 Animations

### Scroll
```html
<div uk-scrollspy="cls: uk-animation-slide-bottom; repeat: true">...</div>
```

### Animations
```html
<div class="uk-animation-fade">...</div>
<div class="uk-animation-scale-up">...</div>
<div class="uk-animation-scale-down">...</div>
<div class="uk-animation-slide-top">...</div>
<div class="uk-animation-slide-bottom">...</div>
<div class="uk-animation-slide-left">...</div>
<div class="uk-animation-slide-right">...</div>
<div class="uk-animation-shake">...</div>
<div class="uk-animation-reverse">...</div>

<!-- Vitesse -->
<div class="uk-animation-fast">...</div>
<div class="uk-animation-slow">...</div>
```

### Parallax
```html
<div uk-parallax="y: 100">...</div>
<div uk-parallax="opacity: 0,1; y: 100,0">...</div>
```

---

## 📱 Responsive

### Breakpoints
- `@s`: >= 640px (small)
- `@m`: >= 960px (medium)
- `@l`: >= 1200px (large)
- `@xl`: >= 1600px (extra large)

### Exemples
```html
<!-- Visibilité responsive -->
<div class="uk-visible@s">Visible sur small et plus</div>
<div class="uk-hidden@m">Caché sur medium et plus</div>

<!-- Grid responsive -->
<div class="uk-width-1-2@s uk-width-1-3@m uk-width-1-4@l">...</div>

<!-- Text alignment responsive -->
<p class="uk-text-left@s uk-text-center@m">...</p>

<!-- Margin responsive -->
<div class="uk-margin-small@s uk-margin-medium@m">...</div>
```

---

## 🔗 Liens utiles

- [Documentation UIkit officielle](https://getuikit.com/docs/introduction)
- [YOOtheme Documentation](https://yootheme.com/support/yootheme-pro/wordpress)
- [WordPress Codex](https://codex.wordpress.org/)

---

## 💡 Tips & Best Practices

1. **Toujours utiliser uk-grid avec uk-grid-match** pour des hauteurs égales
2. **Préférer les classes responsive** (@s, @m, @l) pour le design adaptatif
3. **Utiliser uk-container** pour centrer le contenu avec des marges appropriées
4. **Combiner les classes utilitaires** pour éviter le CSS custom
5. **Tester sur mobile en priorité** (mobile-first)
6. **Utiliser les composants JS UIkit** plutôt que jQuery custom quand possible

---

**Version:** 1.0
**Dernière mise à jour:** 2026-01-18
