<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'fields' => 'custom_fields',
        'options' => 'custom_field_options',
        'values' => 'custom_field_values',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    | Memoize field definitions per target model class.
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'prefix' => 'spiggle_dynamic_fields',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Serialization
    |--------------------------------------------------------------------------
    | When true, models using HasCustomFields append a `custom_fields` key
    | to their JSON / array output.
    */
    'append_to_json' => true,

    /*
    |--------------------------------------------------------------------------
    | Model Discovery
    |--------------------------------------------------------------------------
    | Paths scanned (relative to base_path) for Eloquent models that can
    | receive custom fields in the Field Manager GUI.
    */
    'model_discovery' => [
        'paths' => [
            'app/Models',
        ],
        'exclude' => [
            // Fully-qualified class names to hide from the target model list
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Navigation
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        'group' => 'System',
        'icon' => 'heroicon-o-adjustments-horizontal',
        'sort' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Role-Based Access
    |--------------------------------------------------------------------------
    | Permission names checked when Shield / Spatie Permission is present.
    | If the permission system is unavailable, access falls back to
    | authenticated panel users (see CustomFieldResource).
    */
    'permissions' => [
        'manage' => 'manage_custom_fields',
        'view' => 'view_custom_fields',
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Types
    |--------------------------------------------------------------------------
    | Core mapper implements the free types. Pro types remain listed so the
    | Field Manager can show a PRO badge and intercept selection.
    */
    'field_types' => [
        'text' => 'Text',
        'textarea' => 'Textarea',
        'select' => 'Select',
        'multi_select' => 'Multi-select',
        'tags' => 'Tags',
        'radio' => 'Radio',
        'date' => 'Date',
        'datetime' => 'Date & Time',
        'boolean' => 'Boolean',
        'toggle' => 'Toggle',
        'number' => 'Number',
        'email' => 'Email',
        'phone' => 'Phone',
        'url' => 'URL',
        'file' => 'File',
    ],

    /*
    |--------------------------------------------------------------------------
    | Form State Key
    |--------------------------------------------------------------------------
    | Nested form attribute used for custom field values on host resources.
    */
    'form_state_key' => 'custom_fields',

    /*
    |--------------------------------------------------------------------------
    | Pro upsell
    |--------------------------------------------------------------------------
    | Checkout URL shown when a Core install selects a Pro-only type.
    | Lemon Squeezy activation lives in spiggle/dynamic-fields-pro.
    */
    'upsell' => [
        'checkout_url' => env(
            'DYNAMIC_FIELDS_CHECKOUT_URL_PRO',
            env('DYNAMIC_FIELDS_CHECKOUT_URL', 'https://kodesmart.lemonsqueezy.com/checkout/buy/da102aee-e7fa-41b1-8f8d-bf552776edb1?enabled=2037699')
        ),
        'checkout_allowed_hosts' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('DYNAMIC_FIELDS_CHECKOUT_ALLOWED_HOSTS', 'lemonsqueezy.com'))
        ))),
    ],

    'tags' => [
        'colors' => [
            'primary',
            'success',
            'warning',
            'danger',
            'info',
            'gray',
            'blue',
            'indigo',
            'purple',
            'pink',
            'rose',
            'red',
            'orange',
            'amber',
            'yellow',
            'lime',
            'green',
            'emerald',
            'teal',
            'cyan',
            'sky',
        ],
    ],

];
