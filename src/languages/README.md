# Languages Directory

This directory is used for plugin translation files.

## Purpose

The `languages` directory stores translation files (`.po`, `.mo`, `.pot`) for internationalization (i18n) of the Custom Post Types RestAPI plugin.

## File Types

- **`.pot` (Portable Object Template)**: Template file containing all translatable strings
- **`.po` (Portable Object)**: Human-readable translation file for specific languages
- **`.mo` (Machine Object)**: Compiled binary translation file used by WordPress

## Translation Files

Translation files should follow this naming convention:
- `custom-post-types-restapi-{locale}.po`
- `custom-post-types-restapi-{locale}.mo`

Examples:
- `custom-post-types-restapi-fr_FR.po` / `custom-post-types-restapi-fr_FR.mo` (French)
- `custom-post-types-restapi-es_ES.po` / `custom-post-types-restapi-es_ES.mo` (Spanish)
- `custom-post-types-restapi-de_DE.po` / `custom-post-types-restapi-de_DE.mo` (German)

## How Translations Work

1. The plugin text domain `custom-post-types-restapi` is loaded in the main plugin file
2. WordPress looks for translation files in this directory
3. All strings wrapped in `__()`, `_e()`, `esc_html__()`, etc. can be translated
4. The WordPress.org translation system can automatically generate translations

## Creating Translations

### Using WordPress.org
When the plugin is published on WordPress.org, translators can contribute translations through the WordPress.org translation system at:
`https://translate.wordpress.org/projects/wp-plugins/custom-post-types-restapi`

### Manual Translation
1. Generate the `.pot` file using WP-CLI or Poedit
2. Create `.po` files for your target language(s)
3. Compile to `.mo` files
4. Place both `.po` and `.mo` files in this directory

## Text Domain

The plugin uses the text domain: `custom-post-types-restapi`

This is defined in the plugin header and loaded via:
```php
load_plugin_textdomain( 'custom-post-types-restapi', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
```

## Resources

- [WordPress Plugin Handbook - Internationalization](https://developer.wordpress.org/plugins/internationalization/)
- [WordPress Polyglots Team](https://make.wordpress.org/polyglots/)
- [Poedit - Translation Editor](https://poedit.net/)
- [WP-CLI i18n Commands](https://developer.wordpress.org/cli/commands/i18n/)
