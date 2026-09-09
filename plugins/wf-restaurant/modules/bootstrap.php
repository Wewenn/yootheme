<?php

defined( 'ABSPATH' ) || exit;

use YOOtheme\Builder;
use YOOtheme\Path;

return [
	'extend' => [
		Builder::class => static function ( Builder $builder ) {
			$builder->addTypePath( Path::get( './element/*/element.json', __DIR__ ) );
		},
	],
];
