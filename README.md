# Spiggle Dynamic Fields Core

Open-source EAV custom fields for Laravel + Filament.

Package: `spiggle/dynamic-fields-core` **v1.2.0**  
Namespace: `Spiggle\DynamicFields`  
License: MIT  

**GitHub (public):** https://github.com/skillbobby/Spiggle-Dynamic-Fields-Core  
**Product site:** https://skillbobby.github.io/Spiggle-Dynamic-Fields-Core/

This is the **free Core** package. It is not Dynamic Fields Pro. Pro is a separate licensed add-on sold to license holders; Pro source is not in this repository.

## What you get in Core (free)

- EAV tables, models, `HasCustomFields`
- Field Manager for Core types: text, email, phone, url, number, plain textarea, select, radio, date, datetime, boolean, toggle
- Mapper that uses Filament `Textarea` for `textarea` (RichEditor is Pro)
- Pro types in the Type dropdown with a **PRO** badge and checkout notification
- Registry hooks so `spiggle/dynamic-fields-pro` can register drivers
- Export / import, standard user-field seeder, verify command

## What is Pro (paid)

Pro is **$29.99** (limited-time deal, normally **$49.99**). It is not published as a public GitHub repo.

- Lemon Squeezy license activation (Filament page, encrypted bind, fingerprint)
- Spatie Media Library file fields
- `file`, `tags`, `multi_select` form drivers
- RichEditor, `visible_when`, clone action, colored badge HTML

Get Pro from the [pricing section](https://skillbobby.github.io/Spiggle-Dynamic-Fields-Core/#pricing) on the product site, or open a [Buy Dynamic Fields Pro](https://github.com/skillbobby/Spiggle-Dynamic-Fields-Core/issues/new?title=Buy%20Dynamic%20Fields%20Pro) issue on this public repo. When a Lemon Squeezy checkout URL exists, set `LEMON_SQUEEZY_CHECKOUT_URL` on the host — do not invent a checkout domain.

## Install

### Packagist (when listed)

```bash
composer require spiggle/dynamic-fields-core
```

### GitHub VCS

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/skillbobby/Spiggle-Dynamic-Fields-Core"
    }
  ],
  "require": {
    "spiggle/dynamic-fields-core": "^1.2"
  }
}
```

### Path package (this starter)

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "packages/spiggle-dynamic-fields-core",
      "options": { "symlink": true }
    }
  ],
  "require": {
    "spiggle/dynamic-fields-core": "@dev"
  }
}
```

```php
use Spiggle\DynamicFields\Filament\DynamicFieldsPlugin;
use Spiggle\DynamicFields\Traits\HasCustomFields;

$panel->plugin(DynamicFieldsPlugin::make());
```

Docs: [guide/](https://skillbobby.github.io/Spiggle-Dynamic-Fields-Core/guide/). Related: [Spiggle Form Builder](https://skillbobby.github.io/Spiggle-Plugins/form-builder/).
