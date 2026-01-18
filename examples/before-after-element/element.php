<?php

namespace YOOtheme;

return [
    'transforms' => [
        'render' => function ($node) {
            // Générer un ID unique pour cet élément
            $node->id = 'before-after-' . uniqid();

            // Enqueue les assets
            $this->app->view->addStyle('before-after', __DIR__ . '/assets/css/before-after.css');
            $this->app->view->addScript('before-after', __DIR__ . '/assets/js/before-after.js', ['jquery'], ['defer' => true]);

            // Classes CSS
            $node->class = [];

            // Ajouter les classes de largeur
            if ($node->props['image_width']) {
                switch ($node->props['image_width']) {
                    case 'small':
                        $node->class[] = 'uk-width-1-2@s';
                        break;
                    case 'medium':
                        $node->class[] = 'uk-width-2-3@s';
                        break;
                    case 'large':
                        $node->class[] = 'uk-width-4-5@s';
                        break;
                    case 'full':
                        $node->class[] = 'uk-width-1-1';
                        break;
                }
            }

            // Ajouter les classes de bordures
            if ($node->props['border_radius']) {
                switch ($node->props['border_radius']) {
                    case 'small':
                        $node->class[] = 'uk-border-rounded';
                        break;
                    case 'medium':
                        $node->class[] = 'ba-border-radius-medium';
                        break;
                    case 'large':
                        $node->class[] = 'ba-border-radius-large';
                        break;
                    case 'circle':
                        $node->class[] = 'uk-border-circle';
                        break;
                }
            }

            return $node;
        }
    ],

    'updates' => [
        '1.0.0' => function ($node) {
            // Migration des anciennes versions si nécessaire
        }
    ]
];
