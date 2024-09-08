<?php

return [
    'market' => [
        'segment' => [
            'open' => env('MARKET_SEGMENT_OPEN', 'middle-income'),
            'economic' => env('MARKET_SEGMENT_ECONOMIC', 'economic'),
            'socialized' => env('MARKET_SEGMENT_SOCIALIZED', 'socialized'),
        ],
        'ceiling' => [
            'horizontal' => [
                'economic' => env('HORIZONTAL_ECONOMIC_MARKET_CEILING', 2500000),
                'socialized' => env('HORIZONTAL_SOCIALIZED_MARKET_CEILING', 850000),
            ],
            'vertical' => [
                'economic' => env('VERTICAL_ECONOMIC_MARKET_CEILING', 2500000),
                'socialized' => env('VERTICAL_SOCIALIZED_MARKET_CEILING', 1800000),
            ],
        ],
        'disposable-income-requirement-multiplier' => [
            'open' => env('OPEN_MARKET_DISPOSABLE_MULTIPLIER', 0.30),
            'economic' => env('ECONOMIC_MARKET_DISPOSABLE_MULTIPLIER', 0.35),
            'socialized' => env('SOCIALIZED_MARKET_DISPOSABLE_MULTIPLIER', 0.35),
        ],
        'loanable-value-multiplier' => [
            'open' => env('OPEN_MARKET_LOANABLE_MULTIPLIER', 0.90),
            'economic' => env('ECONOMIC_MARKET_LOANABLE_MULTIPLIER', 0.95),
            'socialized' => env('SOCIALIZED_MARKET_LOANABLE_MULTIPLIER', 1.00),
        ],
    ],
];
