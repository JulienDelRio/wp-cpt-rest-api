# OpenAPI 3.0.3 Specification Endpoint

This plugin now includes a publicly accessible OpenAPI 3.0.3 specification endpoint that dynamically generates documentation for all available Custom Post Type REST API endpoints.

## Endpoint URL

```
GET /wp-json/{base_segment}/v1/openapi
```

Where `{base_segment}` is your configured base segment (default: `cpt`).

## Features

- **Publicly Accessible**: No authentication required
- **Dynamic Generation**: Reflects current plugin configuration
- **OpenAPI 3.0.3 Compliant**: Works with standard OpenAPI tools including Swagger Editor
- **Complete Documentation**: Includes all endpoints, schemas, and authentication

## What's Included

### Core Endpoints
- Namespace information (`/`)
- OpenAPI specification (`/openapi`)

### Custom Post Type Endpoints
- List posts (`GET /{cpt}`)
- Create post (`POST /{cpt}`)
- Get single post (`GET /{cpt}/{id}`)
- Update post (`PUT/PATCH /{cpt}/{id}`)

### Toolset Relationships (when enabled)
- List relationships (`GET /relations`)
- Get relationship instances (`GET /relations/{slug}`)
- Create relationship (`POST /relations/{slug}`)
- Delete relationship (`DELETE /relations/{slug}/{id}`)

### Authentication Schema
- Bearer token authentication scheme
- Complete security documentation

## Usage Examples

### View in Browser
```
https://yoursite.com/wp-json/cpt/v1/openapi
```

### Use with Swagger UI
1. Go to [Swagger Editor](https://editor.swagger.io/)
2. File → Import URL
3. Enter your OpenAPI endpoint URL

### Use with Postman
1. Import → Link
2. Enter your OpenAPI endpoint URL
3. Postman will create a complete collection

### Generate Client Code
Use tools like:
- [OpenAPI Generator](https://openapi-generator.tech/)
- [Swagger Codegen](https://swagger.io/tools/swagger-codegen/)

## Dynamic Behavior

The specification automatically updates based on:
- Active CPTs configured in admin settings
- Toolset relationships enabled/disabled status
- Current plugin configuration
- Available post types on the site

## Response Format

Returns a complete OpenAPI 3.0.3 specification in JSON format with:
- API metadata and server information
- All available paths and operations
- Request/response schemas
- Authentication requirements
- Error response formats