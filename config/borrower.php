<?php

return [
    'default_regional' => env('DEFAULT_REGIONAL_BORROWER', false),
    'lending_institutions' => [
        'hdmf' => [
            'name' => 'Home Development Mutual Fund',
            'alias' => 'Pag-IBIG',
            'type' => 'government financial institution',
            'borrowing_age' => [
                'minimum' => 18,
                'maximum' => 60,
                'offset' => 0,
            ],
            'maximum_term' => 30,
            'maximum_paying_age' => 70
        ],
        'rcbc' => [
            'name' => 'Rizal Commercial Banking Corporation',
            'alias' => 'RCBC',
            'type' => 'universal bank',
            'borrowing_age' => [
                'minimum' => 18,
                'maximum' => 60,
                'offset' => -1,
            ],
            'maximum_term' => 20,
            'maximum_paying_age' => 65
        ],
        'cbc' => [
            'name' => 'China Banking Corporation',
            'alias' => 'CBC',
            'type' => 'universal bank',
            'borrowing_age' => [
                'minimum' => 18,
                'maximum' => 60,
                'offset' => -1,
            ],
            'maximum_term' => 20,
            'maximum_paying_age' => 65
        ],
    ],
    'default_lending_institution' => env('DEFAULT_LENDING_INSTITUTION', 'hdmf'),
    'default_seller_code' => env('DEFAULT_SELLER_CODE', 'AA537'),
    'default_disposable_income_multiplier' => env('DEFAULT_DISPOSABLE_INCOME_MULTIPLIER', 0.35),
];
