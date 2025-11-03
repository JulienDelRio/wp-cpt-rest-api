# Development Guide

## Development Mode Configuration

The plugin includes a configurable development mode that controls how CSS and JavaScript assets are versioned.

### Setup for Local Development

1. **Copy the example configuration:**
   ```bash
   cp src/dev-config.example.php src/dev-config.php
   ```

2. **Enable development mode:**
   Edit `src/dev-config.php` and set:
   ```php
   define( 'WP_CPT_RESTAPI_DEV_MODE', true );
   ```

3. **Start developing:**
   - Changes to CSS/JS files will be immediately visible
   - No need to clear browser cache manually
   - Asset URLs will include file modification timestamp

### Production Deployment

**Option 1: Delete the config file**
```bash
rm src/dev-config.php
```

**Option 2: Disable development mode**
Edit `src/dev-config.php`:
```php
define( 'WP_CPT_RESTAPI_DEV_MODE', false );
```

**Note:** The `dev-config.php` file is automatically excluded from git (via `.gitignore`), so it won't be accidentally committed or included in distribution packages.

### How It Works

#### Development Mode (DEV_MODE = true)
- Asset version = file modification time (`filemtime()`)
- Example: `admin.js?ver=1735689234`
- Changes to files automatically invalidate browser cache
- Ideal for active development and testing

#### Production Mode (DEV_MODE = false, or file doesn't exist)
- Asset version = plugin version number
- Example: `admin.js?ver=1.0.1`
- Stable, predictable URLs
- Complies with WordPress.org standards
- Only changes when plugin version is bumped

### Troubleshooting

**Q: My JavaScript changes aren't showing up**
- A: Check if `dev-config.php` exists with `WP_CPT_RESTAPI_DEV_MODE` set to `true`
- A: Hard refresh your browser (Ctrl+Shift+R or Ctrl+F5)

**Q: Should I commit dev-config.php?**
- A: No! It's already in `.gitignore` and should remain local-only

**Q: Does this affect production sites?**
- A: No. Without `dev-config.php`, the plugin uses standard plugin version for assets

## Testing the Configuration

You can verify which mode is active by viewing the page source and checking the asset URLs:

- **Development mode active:** `?ver=1735689234` (timestamp)
- **Production mode active:** `?ver=1.0.1` (version number)
