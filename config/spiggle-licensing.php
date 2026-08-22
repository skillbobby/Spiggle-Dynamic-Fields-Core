<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Unified Spiggle Pro licensing (Filament)
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        'group' => env('SPIGGLE_LICENSE_NAV_GROUP', 'System'),
        'icon' => 'heroicon-o-key',
        'label' => env('SPIGGLE_LICENSE_NAV_LABEL', 'Spiggle Licenses'),
        'sort' => (int) env('SPIGGLE_LICENSE_NAV_SORT', 90),
    ],

];
