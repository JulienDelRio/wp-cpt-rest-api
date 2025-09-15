# WordPress Custom Post Types REST API - Project Specification

## Project Overview

**Project Name:** WordPress Custom Post Types REST API
**Version:** 0.2
**License:** Apache 2.0
**Author:** Julien DELRIO
**WordPress Compatibility:** 6.0+
**PHP Requirement:** 7.4+

### Project Purpose

A robust WordPress plugin that extends the native REST API functionalities to provide comprehensive endpoints for Custom Post Types (CPTs) and their associated metadata. The plugin creates a secure, configurable API layer that allows external applications to perform CRUD operations on Custom Post Types with full metadata support.

## Core Requirements

### Functional Requirements

1. **Custom Post Type API Exposure**
   - Enable/disable CPTs via admin interface with granular control
   - Support for public, publicly queryable, admin-only, and private CPTs
   - Dynamic endpoint registration based on active CPT configuration
   - Real-time validation of CPT availability and access control

2. **API Authentication & Security**
   - Bearer token authentication using generated API keys
   - Secure API key generation with cryptographic randomness
   - Key management system with creation, listing, and deletion capabilities

3. **CRUD Operations**
   - GET: List posts with pagination and filtering
   - GET: Retrieve individual posts by ID
   - POST: Create new posts with metadata
   - PUT/PATCH: Update existing posts (full and partial updates)
   - DELETE : Delete individual posts by ID
   - Comprehensive metadata handling for all operations
   - Keep behavior and best practices of default Worpdress API

4. **Metadata Management**
   - Support for nested meta in `meta` object
   - Root-level meta fields for flexible input formats
   - Automatic sanitization and validation of meta values
   - Private meta field protection (fields starting with `_`)

5. **Toolset Integration (Optional)**
   - Dynamic Toolset relationships endpoint activation
   - Multiple detection methods for Toolset plugin availability
   - Comprehensive relationship CRUD operations
   - Fallback mechanisms for different Toolset API versions

6. **OpenAPI Documentation**
   - Dynamic OpenAPI 3.0.3 specification generation
   - Real-time schema updates based on active CPTs
   - Comprehensive endpoint documentation with examples
   - Self-documenting API with accessible specification endpoint

### Technical Requirements

1. **WordPress Integration**
   - WordPress 6.0+ compatibility
   - PHP 7.4+ requirement
   - Hook-based architecture using WordPress APIs
   - No external dependencies or build process

2. **Performance & Scalability**
   - Efficient database queries with proper indexing
   - Pagination support (max 100 posts per request)
   - Lazy loading of relationships and metadata
   - Minimal memory footprint

3. **Security**
   - Input sanitization and validation at all entry points
   - SQL injection prevention using prepared statements
   - XSS protection through proper output escaping
   - Capability checks for admin operations

## Architecture Design

### Plugin Structure

```
wp-cpt-rest-api/
├── src/
│   ├── wp-cpt-rest-api.php           # Main plugin entry point
│   ├── includes/                      # Core functionality
│   │   ├── class-wp-cpt-restapi.php         # Main orchestrator class
│   │   ├── class-wp-cpt-restapi-loader.php  # Hook management system
│   │   └── class-wp-cpt-restapi-api-keys.php # API key management
│   ├── admin/                         # Admin interface
│   │   └── class-wp-cpt-restapi-admin.php    # Settings and configuration
│   ├── rest-api/                      # REST endpoint handlers
│   │   └── class-wp-cpt-restapi-rest.php     # API implementation
│   ├── swagger/                       # OpenAPI documentation
│   │   └── class-wp-cpt-restapi-openapi.php  # Spec generation
│   ├── assets/                        # Frontend assets
│   │   ├── css/                       # Admin styling
│   │   └── js/                        # Admin JavaScript
│   └── readme.txt                     # WordPress plugin readme
├── docs/                              # Documentation
└── tasks/                             # Empty tasks directory
```

### Core Classes and Responsibilities

#### 1. WP_CPT_RestAPI (Main Class)
- **File:** `src/includes/class-wp-cpt-restapi.php`
- **Purpose:** Central orchestrator and dependency coordinator
- **Responsibilities:**
  - Load and initialize all plugin components
  - Manage class dependencies and instantiation
  - Register WordPress hooks through the loader system
  - Coordinate admin and REST API functionality

#### 2. WP_CPT_RestAPI_Loader (Hook Manager)
- **File:** `src/includes/class-wp-cpt-restapi-loader.php`
- **Purpose:** WordPress hook registration and management
- **Responsibilities:**
  - Centralized hook registration system
  - Action and filter management
  - Plugin lifecycle coordination

#### 3. WP_CPT_RestAPI_Admin (Admin Interface)
- **File:** `src/admin/class-wp-cpt-restapi-admin.php`
- **Purpose:** WordPress admin interface and configuration
- **Responsibilities:**
  - Settings page creation and management
  - CPT selection interface with visibility filtering
  - API key management interface
  - AJAX handlers for dynamic operations
  - Configuration validation and sanitization

#### 4. WP_CPT_RestAPI_REST (API Implementation)
- **File:** `src/rest-api/class-wp-cpt-restapi-rest.php`
- **Purpose:** REST API endpoint implementation and handling
- **Responsibilities:**
  - REST namespace and endpoint registration
  - API authentication and authorization
  - CRUD operation implementation
  - Request validation and response formatting
  - Toolset relationship management

#### 5. WP_CPT_RestAPI_API_Keys (Authentication)
- **File:** `src/includes/class-wp-cpt-restapi-api-keys.php`
- **Purpose:** API key generation and validation
- **Responsibilities:**
  - Cryptographically secure key generation
  - Key storage and retrieval from WordPress options
  - Key validation for authentication
  - Key lifecycle management (create, read, delete)

#### 6. WP_CPT_RestAPI_OpenAPI (Documentation)
- **File:** `src/swagger/class-wp-cpt-restapi-openapi.php`
- **Purpose:** Dynamic OpenAPI specification generation
- **Responsibilities:**
  - OpenAPI 3.0.3 schema generation
  - Dynamic endpoint documentation based on active CPTs
  - Component schema definitions
  - Real-time specification updates

### Data Flow Architecture

#### 1. Plugin Initialization
```
WordPress Load → Main Plugin File → WP_CPT_RestAPI Class →
Load Dependencies → Register Hooks → Initialize Components
```

#### 2. Admin Interface Flow
```
Admin Request → Admin Class → Validate Permissions →
Process Settings → Update Options → AJAX Responses
```

#### 3. API Request Flow
```
REST Request → Authentication Check → Route Validation →
CPT Access Validation → Business Logic → Response Formatting
```

#### 4. API Authentication Flow
```
Bearer Token → Extract Key → Validate Against Stored Keys →
Grant/Deny Access → Continue to Endpoint Handler
```

## API Specification

### Base Configuration
- **Default Namespace:** `cpt/v1`
- **Configurable Base Segment:** Admin-configurable (1-120 chars, a-z, 0-9, -)
- **Authentication:** Bearer token (API keys)
- **Response Format:** JSON

### Core Endpoints

#### 1. Namespace Information
```
GET /{base}/v1/
Public: Yes
Purpose: API namespace information and version
```

#### 2. OpenAPI Specification
```
GET /{base}/v1/openapi
Public: Yes
Purpose: Dynamic OpenAPI 3.0.3 specification
Content-Type: application/json
```

#### 3. Custom Post Type Operations

##### List Posts
```
GET /{base}/v1/{post_type}
Authentication: Required
Parameters:
  - per_page: integer (1-100, default: 10)
  - page: integer (min: 1, default: 1)
Response: PostList with pagination
```

##### Create Post
```
POST /{base}/v1/{post_type}
Authentication: Required
Body: PostInput (title, content, excerpt, status, meta)
Response: 201 Created with Post object
```

##### Get Single Post
```
GET /{base}/v1/{post_type}/{id}
Authentication: Required
Parameters:
  - id: integer (post ID)
Response: Post object
```

##### Update Post
```
PUT/PATCH /{base}/v1/{post_type}/{id}
Authentication: Required
Parameters:
  - id: integer (post ID)
Body: PostInput (partial updates supported)
Response: Post object
```

#### 4. Toolset Relationships (Optional)

##### List Relationships
```
GET /{base}/v1/relations
Authentication: Required
Response: Array of relationship definitions
```

##### Get Relationship Instances
```
GET /{base}/v1/relations/{relation_slug}
Authentication: Required
Response: Array of relationship instances
```

##### Create Relationship
```
POST /{base}/v1/relations/{relation_slug}
Authentication: Required
Body: {parent_id, child_id}
Response: 201 Created with relationship data
```

##### Delete Relationship
```
DELETE /{base}/v1/relations/{relation_slug}/{relationship_id}
Authentication: Required
Response: 200 OK with deletion confirmation
```

### Data Models

#### Post Object
```json
{
  "id": integer,
  "title": string,
  "content": string,
  "excerpt": string,
  "slug": string,
  "status": enum ["publish", "draft", "private", "pending"],
  "type": string,
  "date": datetime,
  "modified": datetime,
  "author": string,
  "featured_media": integer,
  "meta": object
}
```

#### PostInput Object
```json
{
  "title": string (optional),
  "content": string (optional),
  "excerpt": string (optional),
  "status": enum (optional, default: "publish"),
  "meta": object (optional),
  // Additional root-level meta fields allowed
}
```

#### Pagination Object
```json
{
  "total": integer,
  "pages": integer,
  "current_page": integer,
  "per_page": integer
}
```

## Configuration System

### WordPress Options

#### Core Settings
- `cpt_rest_api_base_segment`: API base URL segment (default: "cpt")
- `cpt_rest_api_active_cpts`: Array of enabled Custom Post Type names
- `cpt_rest_api_keys`: Array of generated API keys with metadata
- `cpt_rest_api_toolset_relationships`: Boolean for Toolset support
- `cpt_rest_api_include_nonpublic_cpts`: Array of non-public CPT visibility types

#### CPT Visibility Configuration
- **Public CPTs:** Always available for selection
- **Publicly Queryable:** Optional inclusion in admin selection
- **Admin Only (Show UI):** Optional inclusion for admin-visible CPTs
- **Private:** Optional inclusion for completely private CPTs

### Admin Interface Features

#### Settings Management
1. **API Base Segment Configuration**
   - Real-time URL preview
   - Validation with regex pattern enforcement
   - Character limit enforcement (1-120 characters)

2. **CPT Selection Interface**
   - Table-based CPT listing with metadata
   - Visibility indicators (Public, Queryable, Admin, Private)
   - Individual enable/disable toggles
   - Bulk reset functionality

3. **API Key Management**
   - Secure key generation with unique labels
   - Key listing with creation timestamps
   - Copy-to-clipboard functionality
   - Individual key deletion with confirmation

4. **Toolset Integration Controls**
   - Enable/disable Toolset relationship support
   - Automatic Toolset plugin detection
   - Relationship endpoint activation

## Implementation Details

### Security Implementation

#### API Key Security
- **Generation:** 32-character cryptographically secure random strings
- **Format:** Lowercase letters, digits, and hyphens (a-z, 0-9, -)
- **Storage:** WordPress options table with secure handling
- **Validation:** Constant-time comparison to prevent timing attacks

#### Input Validation
- **Sanitization:** All inputs sanitized using WordPress functions
- **Validation:** Type checking and format validation at API boundaries
- **Escaping:** Output escaping for XSS prevention
- **Meta Fields:** Private field protection and recursive sanitization

#### Access Control
- **Authentication:** Bearer token requirement for all protected endpoints
- **Authorization:** CPT availability validation per request
- **Capabilities:** WordPress capability checks for admin operations

### Performance Optimization

#### Database Efficiency
- **Query Optimization:** Use of WP_Query with optimized parameters
- **Pagination:** Built-in pagination to limit result sets
- **Meta Queries:** Efficient metadata retrieval and updates
- **Caching:** Leverage WordPress object caching where applicable

#### Memory Management
- **Lazy Loading:** On-demand loading of relationships and metadata
- **Resource Limits:** Maximum 100 posts per API request
- **Cleanup:** Proper resource cleanup and garbage collection

### Error Handling

#### HTTP Status Codes
- **200:** Successful GET, PUT, PATCH, DELETE operations
- **201:** Successful POST (resource creation)
- **400:** Bad Request (validation errors)
- **401:** Unauthorized (missing/invalid API key)
- **403:** Forbidden (CPT not enabled, insufficient permissions)
- **404:** Not Found (resource doesn't exist)
- **409:** Conflict (duplicate relationship creation)
- **500:** Internal Server Error (system errors)
- **503:** Service Unavailable (Toolset not available)

#### Error Response Format
```json
{
  "code": "error_code_string",
  "message": "Human-readable error message",
  "data": {
    "status": http_status_code
  }
}
```

### Toolset Integration Strategy

#### Detection Methods
1. **Class Detection:** Check for `Types_Main`, `Toolset_Common_Bootstrap`
2. **Constant Detection:** Check for `TYPES_VERSION`
3. **Function Detection:** Check for `wpcf_init`
4. **Multiple Fallbacks:** Graceful degradation with multiple detection methods

#### API Compatibility
1. **Modern API:** Use `Toolset_Relationship_Definition_Repository`
2. **Legacy API:** Fallback to `wpcf_pr_get_belongs()`
3. **Database Direct:** Direct database queries as last resort
4. **Error Handling:** Graceful handling of API changes

## Deployment Requirements

### Environment Prerequisites
- **WordPress:** Version 6.0 or higher
- **PHP:** Version 7.4 or higher
- **MySQL:** Compatible with WordPress requirements
- **Web Server:** Apache/Nginx with mod_rewrite/URL rewriting

### Installation Process
1. Upload plugin files to `/wp-content/plugins/wp-cpt-rest-api/`
2. Activate plugin through WordPress admin
3. Configure settings at Settings > CPT REST API
4. Generate API keys for authentication
5. Enable desired Custom Post Types
6. Test API endpoints with generated keys

### No Build Process
- **Direct PHP Development:** No compilation or transpilation required
- **No Dependencies:** No Composer, npm, or external package management
- **No Bundling:** Direct file editing and WordPress deployment
- **Version Control:** Direct source code management

## Testing Strategy

### Manual Testing Requirements
1. **Plugin Activation/Deactivation:** Verify clean activation and deactivation
2. **Settings Validation:** Test all admin interface validations and AJAX operations
3. **API Functionality:** Comprehensive testing of all CRUD operations
4. **Authentication:** Verify API key generation, validation, and security
5. **CPT Integration:** Test with various CPT configurations and visibility settings
6. **Toolset Integration:** Test relationship operations when Toolset is available
7. **Error Scenarios:** Verify proper error handling and HTTP status codes

### Security Testing
1. **Authentication Bypass:** Attempt to access protected endpoints without keys
2. **Input Validation:** Test with malicious inputs and edge cases
3. **SQL Injection:** Verify prepared statement usage
4. **XSS Prevention:** Test output escaping effectiveness
5. **Capability Escalation:** Verify admin operation restrictions

### Performance Testing
1. **Load Testing:** Test API performance with high request volumes
2. **Memory Usage:** Monitor memory consumption during operations
3. **Database Queries:** Analyze query efficiency and optimization
4. **Response Times:** Measure endpoint response times under load

## Documentation Requirements

### User Documentation
- **Installation Guide:** Step-by-step setup instructions
- **Configuration Guide:** Admin interface usage and best practices
- **API Documentation:** Comprehensive endpoint documentation with examples
- **Troubleshooting Guide:** Common issues and solutions

### Developer Documentation
- **Architecture Overview:** System design and component interaction
- **Hook Reference:** Available WordPress hooks and filters
- **Customization Guide:** Extension and customization examples
- **Code Standards:** PHP and WordPress coding standards compliance

### API Documentation
- **OpenAPI Specification:** Auto-generated, accessible at `/openapi` endpoint
- **Interactive Documentation:** Self-documenting API with real-time updates
- **Example Requests:** cURL and JavaScript examples for all endpoints
- **Authentication Guide:** API key usage and security best practices

## Maintenance and Evolution

### Version Management
- **Semantic Versioning:** Follow semantic versioning principles
- **Backward Compatibility:** Maintain API compatibility across minor versions
- **Migration Strategies:** Smooth upgrade paths for configuration changes
- **Deprecation Policy:** Clear communication of deprecated features

### Future Enhancements
- **Additional Authentication Methods:** OAuth, JWT support consideration
- **Advanced Filtering:** Complex query parameter support
- **Webhook Support:** Event-driven notifications for CPT changes
- **Rate Limiting:** API usage throttling and quota management
- **Caching Layer:** Advanced caching for improved performance

### Monitoring and Analytics
- **Error Logging:** Comprehensive error tracking and logging
- **Usage Analytics:** API usage pattern analysis
- **Performance Monitoring:** Response time and resource usage tracking
- **Security Auditing:** Authentication attempt logging and analysis

This specification provides a complete blueprint for rebuilding the WordPress Custom Post Types REST API plugin from scratch, ensuring all functionality, architecture, security, and integration requirements are met.