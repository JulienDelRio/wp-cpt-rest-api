# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WordPress Custom Post Types RestAPI is a WordPress plugin that extends the native REST API to provide comprehensive endpoints for Custom Post Types and their metadata. The plugin is currently at version 0.2 and requires WordPress 6.0+ and PHP 7.4+.

## Development Workflow

This is a WordPress plugin with no build process or dependency management. Development involves:

1. **Direct PHP editing**: Edit PHP files in the `src/` directory directly
2. **WordPress testing**: Test by installing the plugin in a WordPress development environment
3. **Plugin distribution**: The plugin is distributed as a ZIP file (`wp-cpt-rest-api.zip`)

### No Build Commands
- No package.json, composer.json, or build tools
- No test framework or automated testing setup
- No linting or code quality tools configured

## Architecture

### Core Components

- **Main Plugin File**: `src/wp-cpt-rest-api.php` - Entry point with activation/deactivation hooks
- **Core Class**: `src/includes/class-wp-cpt-restapi.php` - Main orchestrator using loader pattern
- **Admin Interface**: `src/admin/class-wp-cpt-restapi-admin.php` - WordPress admin settings and configuration
- **REST API**: `src/rest-api/class-wp-cpt-restapi-rest.php` - REST endpoint implementations
- **API Key Management**: `src/includes/class-wp-cpt-restapi-api-keys.php` - API authentication system
- **Hook Loader**: `src/includes/class-wp-cpt-restapi-loader.php` - WordPress hooks management
- **OpenAPI Generation**: `src/swagger/class-wp-cpt-restapi-openapi.php` - Dynamic OpenAPI 3.0.3 spec generation

### Key Features

1. **Custom REST Endpoints**: Creates `/{base_segment}/v1/{post_type}` endpoints for enabled CPTs
2. **API Key Authentication**: Bearer token authentication system
3. **Metadata Support**: Full CRUD operations for custom fields and post meta
4. **Toolset Relationships**: Optional support for Toolset plugin relationships
5. **OpenAPI Documentation**: Auto-generated API specifications at `/openapi` endpoint
6. **Admin Configuration**: WordPress admin interface for enabling CPTs and managing settings

### Plugin Options

- `cpt_rest_api_base_segment`: API base URL segment (default: "cpt")
- `cpt_rest_api_active_cpts`: Array of enabled Custom Post Types
- `cpt_rest_api_keys`: Array of generated API keys
- `cpt_rest_api_toolset_relationships`: Boolean for Toolset support
- `cpt_rest_api_include_nonpublic_cpts`: Array of non-public CPT visibility types to include (can contain: 'publicly_queryable', 'show_ui', 'private')

## Available Endpoints

### Core API Endpoints
- `GET /{base}/v1/` - Namespace information
- `GET /{base}/v1/openapi` - OpenAPI 3.0.3 specification (publicly accessible)
- `GET /{base}/v1/{cpt}` - List CPT posts (paginated)
- `POST /{base}/v1/{cpt}` - Create new CPT post
- `GET /{base}/v1/{cpt}/{id}` - Get single CPT post
- `PUT/PATCH /{base}/v1/{cpt}/{id}` - Update CPT post
- `DELETE /{base}/v1/{cpt}/{id}` - Delete CPT post

### Toolset Relationships (when enabled)
- `GET /{base}/v1/relations` - List all relationships
- `GET /{base}/v1/relations/{slug}` - Get relationship instances
- `POST /{base}/v1/relations/{slug}` - Create relationship
- `DELETE /{base}/v1/relations/{slug}/{id}` - Delete relationship

## WordPress Integration

### Admin Interface Location
Navigate to **Settings > CPT REST API** in WordPress admin to:
- Configure API base segment
- Enable/disable specific CPTs
- Manage API keys
- Enable Toolset relationship support
- Include non-public CPTs in selection

### Security Considerations
- All API endpoints require Bearer token authentication (except namespace info and `/openapi` which are publicly accessible)
- API key authentication occurs at the `rest_authentication_errors` filter level
- Permission callbacks validate requests follow WordPress REST API conventions
- **Access Model**: API keys provide binary access (valid key = full access to all enabled CPTs)
  - Keys can perform ALL operations (GET, POST, PUT/PATCH, DELETE)
  - Keys grant access to ALL enabled CPTs (configured in Settings > CPT REST API)
  - No granular permissions per key (read-only keys not supported)
  - This model is intentional for external API integration use cases
- Private meta fields (starting with `_`) are ignored
- Only enabled CPTs are accessible via API
- API keys are stored securely in WordPress options
- **Security Best Practice**: Generate separate keys for different services and revoke immediately if compromised

## Development Notes

### Meta Field Handling
The plugin supports both nested meta in a `meta` object and root-level meta fields in API requests. Root-level fields take precedence over nested ones for duplicate keys.

### Toolset Integration
The plugin includes multiple fallback methods for Toolset compatibility and only activates relationship endpoints when Toolset is available and enabled in settings.

### Documentation
- Comprehensive API documentation available in `API_ENDPOINTS.md`
- OpenAPI specification dynamically generated and accessible at runtime
- All endpoints documented with examples for cURL and JavaScript

### File Structure
```
wp-cpt-rest-api/
├── src/
│   ├── wp-cpt-rest-api.php (main plugin file)
│   ├── includes/ (core classes)
│   ├── admin/ (admin interface)
│   ├── rest-api/ (REST endpoint handlers)
│   ├── swagger/ (OpenAPI generation)
│   ├── assets/
│   │   ├── css/ (admin styling)
│   │   │   └── wp-cpt-restapi-admin.css
│   │   ├── js/ (admin JavaScript)
│   │   │   └── wp-cpt-restapi-admin.js
│   │   └── images/ (admin images)
│   ├── readme.txt (WordPress plugin readme)
│   ├── API_ENDPOINTS.md (comprehensive API documentation)
│   └── OPENAPI.md (OpenAPI specification docs)
├── docs/ (project documentation)
│   └── SPECS.md (project specification)
├── tasks/ (empty directory)
└── wp-cpt-rest-api.zip (distribution package)
```