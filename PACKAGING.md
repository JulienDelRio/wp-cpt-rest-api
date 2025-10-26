# WordPress Plugin Packaging Guide

## Quick Package Creation

To create a distribution package for the WordPress Custom Post Types REST API plugin:

### Windows (PowerShell)
```powershell
.\package-plugin.ps1
```

### Linux/Mac (Bash)
```bash
./package-plugin.sh
```

## Package Details

**Current Version**: 1.0.0-RC1
**Package Name**: `wp-cpt-rest-api-1.0.0-RC1.zip`
**Package Size**: ~48 KB

**✅ WordPress-Compatible Structure**: The ZIP is created with the plugin folder at the root level, allowing direct installation via WordPress admin without manual extraction.

## What's Included

The distribution package contains:

### Core Files
- `wp-cpt-rest-api.php` - Main plugin file
- `readme.txt` - WordPress.org compatible readme
- `uninstall.php` - Clean uninstallation handler
- `LICENSE` - Apache 2.0 license

### Directories
- `admin/` - Admin interface classes
- `includes/` - Core plugin classes (loader, main class, API keys)
- `rest-api/` - REST API endpoint handlers
- `swagger/` - OpenAPI specification generator
- `assets/` - CSS, JavaScript, and images
- `languages/` - Internationalization files

### Documentation
- `API_ENDPOINTS.md` - API documentation
- `OPENAPI.md` - OpenAPI specification details

## What's Excluded

The following development files are automatically excluded:

- `.git/` directory
- `.vscode/` directory
- `tests/` directory
- `.gitignore` files
- `.DS_Store` files
- Build artifacts
- Development documentation

## Installation Instructions

### For WordPress Site Owners

1. **Download** the `wp-cpt-rest-api-1.0.0-RC1.zip` file

2. **Install via WordPress Admin**:
   - Go to **Plugins > Add New**
   - Click **Upload Plugin**
   - Choose the ZIP file
   - Click **Install Now**
   - Click **Activate Plugin**

3. **Install via FTP**:
   - Extract the ZIP file
   - Upload the `wp-cpt-rest-api` folder to `/wp-content/plugins/`
   - Activate via **Plugins** menu in WordPress admin

4. **Configure**:
   - Go to **Settings > CPT REST API**
   - Enable desired Custom Post Types
   - Create API keys
   - Configure settings as needed

### For WordPress.org Submission

The package is ready for WordPress.org plugin directory submission:

1. Ensure you're logged into WordPress.org
2. Go to: https://wordpress.org/plugins/developers/add/
3. Upload `wp-cpt-rest-api-1.0.0-RC1.zip`
4. Follow the WordPress.org submission process

## Version History

- **1.0.0-RC1** (Current) - First Release Candidate
  - Production-ready
  - 100% audit compliance (21/21 issues resolved)
  - All security issues resolved
  - Complete WordPress Coding Standards compliance

## Package Verification

To verify package integrity:

```powershell
# Windows PowerShell
$hash = Get-FileHash -Path "wp-cpt-rest-api-1.0.0-RC1.zip" -Algorithm SHA256
Write-Host "SHA256: $($hash.Hash)"
```

```bash
# Linux/Mac
shasum -a 256 wp-cpt-rest-api-1.0.0-RC1.zip
```

## Support

- **GitHub**: https://github.com/JulienDelRio/wp-cpt-rest-api
- **Author**: Julien DELRIO
- **Website**: https://juliendelrio.fr

## License

Apache License 2.0 - See LICENSE file for details
