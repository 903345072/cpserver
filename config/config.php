<?php
return [
    'annotations' => [
        'scan' => [
            'paths' => [
                BASE_PATH . '/app',
            ],
            'ignore_annotations' => [
                'mixin',
            ],
            'class_map' => [
            ],
        ],
    ],
    'aspects' => [
        // 这里写入对应的 Aspect
        app\aspect\mustRealAspect::class,
    ]
];