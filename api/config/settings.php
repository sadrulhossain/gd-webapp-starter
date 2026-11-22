<?php

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
|
| The only place settings are declared: key => definition. A definition has
|   type    string|text|email|boolean|integer|json|image|secret
|   default value used until the setting is edited (never stored just to hold it)
|   rules   Laravel validation rules for the value (PATCH sends only changed keys)
|   nested  rules for sub-keys of a json value, e.g. 'hours.*.closed'
|   public  true = included in GET /public/settings (secrets can never be public)
| Types: `image` settings are changed only through their upload endpoint; `secret`
| settings are stored encrypted, write-only (the API says whether one is set, never its value).
|
*/

$open = ['open' => '09:00', 'close' => '17:00', 'closed' => false];
$closed = ['open' => null, 'close' => null, 'closed' => true];
$days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

return [

    'settings' => [
        'site_name' => ['type' => 'string', 'default' => 'My Business', 'public' => true, 'rules' => ['required', 'string', 'max:120']],
        'tagline' => ['type' => 'string', 'default' => 'Welcome', 'public' => true, 'rules' => ['nullable', 'string', 'max:255']],
        'logo' => ['type' => 'image', 'default' => null, 'public' => true],
        'favicon' => ['type' => 'image', 'default' => null, 'public' => true],
        'currency' => ['type' => 'string', 'default' => 'USD', 'public' => true, 'rules' => ['nullable', 'string', 'max:10']],
        'currency_symbol' => ['type' => 'string', 'default' => '$', 'public' => true, 'rules' => ['nullable', 'string', 'max:10']],
        'address' => ['type' => 'text', 'default' => null, 'public' => true, 'rules' => ['nullable', 'string', 'max:500']],
        'phone' => ['type' => 'string', 'default' => null, 'public' => true, 'rules' => ['nullable', 'string', 'max:40']],
        'email' => ['type' => 'email', 'default' => null, 'public' => true, 'rules' => ['nullable', 'email', 'max:255']],
        'hours' => [
            'type' => 'json',
            'default' => array_combine($days, [$open, $open, $open, $open, $open, $closed, $closed]),
            'public' => true,
            'rules' => ['nullable', 'array:'.implode(',', $days)],
            'nested' => [
                'hours.*' => ['array:open,close,closed'],
                'hours.*.closed' => ['required', 'boolean'],
                'hours.*.open' => ['nullable', 'date_format:H:i'],
                'hours.*.close' => ['nullable', 'date_format:H:i'],
            ],
        ],
        'social_links' => [
            'type' => 'json',
            'default' => [],
            'public' => true,
            'rules' => ['nullable', 'array:facebook,instagram,x,linkedin,youtube,tiktok,whatsapp'],
            'nested' => ['social_links.*' => ['nullable', 'url', 'max:255']],
        ],
    ],

];
