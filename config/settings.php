<?php

return [

    'site_name' => ['default' => 'GrihaFlow', 'cast' => 'string'],
    'public_site_name' => ['default' => '', 'cast' => 'string'],
    'public_homepage_title' => ['default' => '', 'cast' => 'string'],
    'public_homepage_description' => ['default' => '', 'cast' => 'string'],
    'public_og_image_path' => ['default' => '', 'cast' => 'string'],
    'branding_logo_path' => ['default' => '', 'cast' => 'string'],
    'branding_favicon_path' => ['default' => '', 'cast' => 'string'],
    // Regional settings are application-wide India-only values pinned in
    // config/regional.php (the single source of truth). The settings rows
    // below only materialize those same values; they must not be edited.
    'country_code' => ['default' => config('regional.country.code', 'IN'), 'cast' => 'string'],
    'locale' => ['default' => config('regional.locale.app', 'en'), 'cast' => 'string'],
    'currency' => ['default' => config('regional.currency.code', 'INR'), 'cast' => 'string'],
    'supported_currencies' => ['default' => null, 'cast' => 'array'],
    'timezone' => ['default' => config('regional.timezone', 'Asia/Kolkata'), 'cast' => 'string'],
    'lease_id_prefix' => ['default' => 'LSX', 'cast' => 'string'],
    'invoice_pdf_enabled' => ['default' => false, 'cast' => 'boolean'],
    'reminder_enabled' => ['default' => true, 'cast' => 'boolean'],
    'reminder_days_before' => ['default' => 3, 'cast' => 'integer'],
    'reminder_overdue_intervals' => ['default' => [1, 3, 7], 'cast' => 'array'],
    'reminder_message_templates' => ['default' => [], 'cast' => 'array'],
    'reminder_channels' => ['default' => ['log'], 'cast' => 'array'],
    'mail_config' => ['default' => [], 'cast' => 'encrypted:array'],
    'whatsapp_config' => ['default' => [], 'cast' => 'encrypted:array'],

];
