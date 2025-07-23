# Custom Post Types REST API Endpoints

This plugin creates REST API endpoints for Custom Post Types that are enabled through the admin interface.

## Base URL Structure

```
https://yoursite.com/wp-json/{base_segment}/v1/
```

Where `{base_segment}` is configurable in the admin settings (default: `cpt`).

## Authentication

All endpoints require API key authentication using the Bearer token method:

```
Authorization: Bearer YOUR_API_KEY_HERE
```

## Available Endpoints

### 1. Namespace Info
**GET** `/wp-json/cpt/v1/`

Returns information about the API namespace.

**Response:**
```json
{
  "namespace": "cpt/v1",
  "description": "WordPress Custom Post Types REST API",
  "version": "0.1"
}
```

### 2. List CPT Posts
**GET** `/wp-json/cpt/v1/{post_type}`

Returns a paginated list of posts for the specified Custom Post Type.

**Parameters:**
- `per_page` (optional): Number of posts per page (default: 10, max: 100)
- `page` (optional): Page number (default: 1)

**Example:**
```
GET /wp-json/cpt/v1/product?per_page=5&page=1
```

**Response:**
```json
{
  "posts": [
    {
      "id": 123,
      "title": "Sample Product",
      "content": "Product description...",
      "excerpt": "Short description...",
      "slug": "sample-product",
      "status": "publish",
      "type": "product",
      "date": "2024-01-15 10:30:00",
      "modified": "2024-01-16 14:20:00",
      "author": "1",
      "featured_media": 456,
      "meta": {
        "custom_field_1": "value1",
        "custom_field_2": "value2"
      }
    }
  ],
  "pagination": {
    "total": 25,
    "pages": 5,
    "current_page": 1,
    "per_page": 5
  }
}
```

### 3. Create CPT Post
**POST** `/wp-json/cpt/v1/{post_type}`

Creates a new post for the specified Custom Post Type.

**Request Headers:**
```
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
```

**Request Body Parameters:**
- `title` (optional): Post title
- `content` (optional): Post content (HTML allowed)
- `excerpt` (optional): Post excerpt
- `status` (optional): Post status (`publish`, `draft`, `private`, `pending` - default: `publish`)
- `meta` (optional): Object containing custom meta fields
- **Meta fields can also be provided directly at root level**

**Example Request (Nested meta):**
```http
POST /wp-json/cpt/v1/etablissement
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json

{
  "title": "École Test",
  "content": "Description de l'établissement.",
  "status": "publish",
  "meta": {
    "wpcf-email-elu": "test@gmail.com",
    "wpcf-ordre": "700"
  },
  "extra_field": "ignored"
}
```

**Example Request (Root-level meta):**
```http
POST /wp-json/cpt/v1/etablissement
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json

{
  "title": "École Test",
  "content": "Description de l'établissement.",
  "status": "publish",
  "wpcf-email-elu": "test@gmail.com",
  "wpcf-ordre": "700",
  "extra_field": "ignored"
}
```

**Mixed Format (Both nested and root-level):**
```http
POST /wp-json/cpt/v1/etablissement
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json

{
  "title": "École Test",
  "content": "Description de l'établissement.",
  "status": "publish",
  "meta": {
    "wpcf-email-elu": "test@gmail.com"
  },
  "wpcf-ordre": "700",
  "extra_field": "ignored"
}
```

**Success Response (201 Created):**
```json
{
  "id": 123,
  "title": "École Test",
  "content": "Description de l'établissement.",
  "excerpt": "",
  "slug": "ecole-test",
  "status": "publish",
  "type": "etablissement",
  "date": "2024-01-15 10:30:00",
  "modified": "2024-01-15 10:30:00",
  "author": "1",
  "featured_media": 0,
  "meta": {
    "address": "123 Main Street",
    "phone": "+1234567890"
  }
}
```

### 4. Update CPT Post
**PUT/PATCH** `/wp-json/cpt/v1/{post_type}/{id}`

Updates an existing post for the specified Custom Post Type. Both PUT and PATCH methods are supported for full or partial updates.

**Request Headers:**
```
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
```

**Request Body Parameters:**
- `title` (optional): Post title
- `content` (optional): Post content (HTML allowed)
- `excerpt` (optional): Post excerpt
- `status` (optional): Post status (`publish`, `draft`, `private`, `pending`)
- `meta` (optional): Object containing custom meta fields
- **Meta fields can also be provided directly at root level**

**Example Request (Partial Update - PATCH):**
```http
PATCH /wp-json/cpt/v1/etablissement/123
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json

{
  "title": "École Test Updated",
  "wpcf-email-elu": "updated@gmail.com"
}
```

**Example Request (Full Update - PUT):**
```http
PUT /wp-json/cpt/v1/etablissement/123
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json

{
  "title": "École Test Updated",
  "content": "Updated description",
  "status": "publish",
  "meta": {
    "wpcf-email-elu": "updated@gmail.com",
    "wpcf-ordre": "800"
  }
}
```

**Success Response (200 OK):**
```json
{
  "id": 123,
  "title": "École Test Updated",
  "content": "Updated description",
  "excerpt": "",
  "slug": "ecole-test",
  "status": "publish",
  "type": "etablissement",
  "date": "2024-01-15 10:30:00",
  "modified": "2024-01-16 15:45:00",
  "author": "1",
  "featured_media": 0,
  "meta": {
    "wpcf-email-elu": "updated@gmail.com",
    "wpcf-ordre": "800"
  }
}
```

### 5. Get Single CPT Post
**GET** `/wp-json/cpt/v1/{post_type}/{id}`

Returns a specific post from the Custom Post Type.

**Example:**
```
GET /wp-json/cpt/v1/product/123
```

**Response:**
```json
{
  "id": 123,
  "title": "Sample Product",
  "content": "Product description...",
  "excerpt": "Short description...",
  "slug": "sample-product",
  "status": "publish",
  "type": "product",
  "date": "2024-01-15 10:30:00",
  "modified": "2024-01-16 14:20:00",
  "author": "1",
  "featured_media": 456,
  "meta": {
    "custom_field_1": "value1",
    "custom_field_2": "value2"
  }
}
```

## Toolset Relationships Endpoints

**Note:** These endpoints are only available when Toolset relationship support is enabled in the plugin settings and the Toolset plugin is active.

### 6. List All Toolset Relationships
**GET** `/wp-json/cpt/v1/relations`

Returns all available Toolset relationships.

**Response:**
```json
{
  "relationships": [
    {
      "slug": "product-category",
      "name": "Product Category",
      "parent_types": ["category"],
      "child_types": ["product"],
      "cardinality": {
        "parent_max": -1,
        "child_max": 1
      },
      "is_active": true
    }
  ],
  "count": 1
}
```

### 7. Get Relationship Instances
**GET** `/wp-json/cpt/v1/relations/{relation_slug}`

Returns all instances of a specific relationship.

**Example:**
```
GET /wp-json/cpt/v1/relations/product-category
```

**Response:**
```json
{
  "relation_slug": "product-category",
  "instances": [
    {
      "relationship_id": "MTIzOjQ1Njpwcm9kdWN0LWNhdGVnb3J5",
      "parent_id": 123,
      "child_id": 456,
      "relation_slug": "product-category"
    }
  ],
  "count": 1
}
```

### 8. Create Relationship Instance
**POST** `/wp-json/cpt/v1/relations/{relation_slug}`

Creates a new relationship instance between two posts.

**Request Headers:**
```
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
```

**Request Body Parameters:**
- `parent_id` (required): ID of the parent post
- `child_id` (required): ID of the child post

**Example Request:**
```http
POST /wp-json/cpt/v1/relations/product-category
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json

{
  "parent_id": 123,
  "child_id": 456
}
```

**Success Response (201 Created):**
```json
{
  "success": true,
  "relationship_id": "MTIzOjQ1Njpwcm9kdWN0LWNhdGVnb3J5",
  "parent_id": 123,
  "child_id": 456,
  "relation_slug": "product-category",
  "message": "Relationship created successfully."
}
```

### 9. Delete Relationship Instance
**DELETE** `/wp-json/cpt/v1/relations/{relation_slug}/{relationship_id}`

Deletes a specific relationship instance.

**Example:**
```
DELETE /wp-json/cpt/v1/relations/product-category/MTIzOjQ1Njpwcm9kdWN0LWNhdGVnb3J5
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "relationship_id": "MTIzOjQ1Njpwcm9kdWN0LWNhdGVnb3J5",
  "parent_id": 123,
  "child_id": 456,
  "relation_slug": "product-category",
  "message": "Relationship deleted successfully."
}
```

## Toolset Relationship Error Responses

### 503 Service Unavailable (Toolset not available)
```json
{
  "code": "toolset_not_available",
  "message": "Toolset plugin is not active or available.",
  "data": {
    "status": 503
  }
}
```

### 400 Bad Request (Invalid relationship ID)
```json
{
  "code": "invalid_relationship_id",
  "message": "Invalid relationship ID format.",
  "data": {
    "status": 400
  }
}
```

### 409 Conflict (Relationship already exists)
```json
{
  "code": "relationship_exists",
  "message": "Relationship already exists between these posts.",
  "data": {
    "status": 409
  }
}
```

### 404 Not Found (Relationship not found)
```json
{
  "code": "relationship_not_found",
  "message": "Relationship not found or could not be deleted.",
  "data": {
    "status": 404
  }
}
```

### 500 Internal Server Error (Relationship creation failed)
```json
{
  "code": "relationship_creation_failed",
  "message": "Failed to create relationship.",
  "data": {
    "status": 500
  }
}
```

## Error Responses

### 401 Unauthorized
```json
{
  "code": "rest_not_logged_in",
  "message": "You are not logged in and no valid API key was provided.",
  "data": {
    "status": 401
  }
}
```

### 403 Forbidden
```json
{
  "code": "rest_forbidden",
  "message": "This Custom Post Type is not available via the API.",
  "data": {
    "status": 403
  }
}
```

### 404 Not Found
```json
{
  "code": "rest_post_invalid_id",
  "message": "Invalid post ID.",
  "data": {
    "status": 404
  }
}
```

### 500 Internal Server Error (POST)
```json
{
  "code": "rest_cannot_create",
  "message": "The post cannot be created.",
  "data": {
    "status": 500
  }
}
```

### 500 Internal Server Error (PUT/PATCH)
```json
{
  "code": "rest_cannot_update",
  "message": "The post cannot be updated.",
  "data": {
    "status": 500
  }
}
```

### 403 Forbidden (Update)
```json
{
  "code": "rest_cannot_edit",
  "message": "Sorry, you are not allowed to edit this post.",
  "data": {
    "status": 403
  }
}
```

## Usage Examples

### Using cURL

```bash
# List products
curl -H "Authorization: Bearer YOUR_API_KEY" \
     "https://yoursite.com/wp-json/cpt/v1/product"

# Get specific product
curl -H "Authorization: Bearer YOUR_API_KEY" \
     "https://yoursite.com/wp-json/cpt/v1/product/123"

# Create new product (nested meta)
curl -X POST \
     -H "Authorization: Bearer YOUR_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{
       "title": "New Product",
       "content": "Product description here",
       "status": "publish",
       "meta": {
         "price": "29.99",
         "category": "electronics"
       }
     }' \
     "https://yoursite.com/wp-json/cpt/v1/product"

# Create new product (root-level meta)
curl -X POST \
     -H "Authorization: Bearer YOUR_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{
       "title": "New Product",
       "content": "Product description here",
       "status": "publish",
       "price": "29.99",
       "category": "electronics"
     }' \
     "https://yoursite.com/wp-json/cpt/v1/product"

# Update product (partial update - PATCH)
curl -X PATCH \
     -H "Authorization: Bearer YOUR_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{
       "title": "Updated Product Title",
       "price": "39.99"
     }' \
     "https://yoursite.com/wp-json/cpt/v1/product/123"

# Update product (full update - PUT)
curl -X PUT \
     -H "Authorization: Bearer YOUR_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{
       "title": "Completely Updated Product",
       "content": "New product description",
       "status": "publish",
       "meta": {
         "price": "49.99",
         "category": "updated-electronics"
       }
     }' \
     "https://yoursite.com/wp-json/cpt/v1/product/123"
```

### Using JavaScript (fetch)

```javascript
const apiKey = 'YOUR_API_KEY';
const baseUrl = 'https://yoursite.com/wp-json/cpt/v1';

// List products
fetch(`${baseUrl}/product`, {
  headers: {
    'Authorization': `Bearer ${apiKey}`
  }
})
.then(response => response.json())
.then(data => console.log(data));

// Get specific product
fetch(`${baseUrl}/product/123`, {
  headers: {
    'Authorization': `Bearer ${apiKey}`
  }
})
.then(response => response.json())
.then(data => console.log(data));

// Create new product (nested meta)
fetch(`${baseUrl}/product`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${apiKey}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    title: 'New Product',
    content: 'Product description here',
    status: 'publish',
    meta: {
      price: '29.99',
      category: 'electronics'
    }
  })
})
.then(response => response.json())
.then(data => console.log(data));

// Create new product (root-level meta)
fetch(`${baseUrl}/product`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${apiKey}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    title: 'New Product',
    content: 'Product description here',
    status: 'publish',
    price: '29.99',
    category: 'electronics'
  })
})
.then(response => response.json())
.then(data => console.log(data));

// Update product (partial update - PATCH)
fetch(`${baseUrl}/product/123`, {
  method: 'PATCH',
  headers: {
    'Authorization': `Bearer ${apiKey}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    title: 'Updated Product Title',
    price: '39.99'
  })
})
.then(response => response.json())
.then(data => console.log(data));

// Update product (full update - PUT)
fetch(`${baseUrl}/product/123`, {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${apiKey}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    title: 'Completely Updated Product',
    content: 'New product description',
    status: 'publish',
    meta: {
      price: '49.99',
      category: 'updated-electronics'
    }
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Toolset Relationships Examples

#### Using cURL

```bash
# List all relationships
curl -H "Authorization: Bearer YOUR_API_KEY" \
     "https://yoursite.com/wp-json/cpt/v1/relations"

# Get relationship instances
curl -H "Authorization: Bearer YOUR_API_KEY" \
     "https://yoursite.com/wp-json/cpt/v1/relations/product-category"

# Create relationship
curl -X POST \
     -H "Authorization: Bearer YOUR_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{
       "parent_id": 123,
       "child_id": 456
     }' \
     "https://yoursite.com/wp-json/cpt/v1/relations/product-category"

# Delete relationship
curl -X DELETE \
     -H "Authorization: Bearer YOUR_API_KEY" \
     "https://yoursite.com/wp-json/cpt/v1/relations/product-category/MTIzOjQ1Njpwcm9kdWN0LWNhdGVnb3J5"
```

#### Using JavaScript (fetch)

```javascript
const apiKey = 'YOUR_API_KEY';
const baseUrl = 'https://yoursite.com/wp-json/cpt/v1';

// List all relationships
fetch(`${baseUrl}/relations`, {
  headers: {
    'Authorization': `Bearer ${apiKey}`
  }
})
.then(response => response.json())
.then(data => console.log(data));

// Get relationship instances
fetch(`${baseUrl}/relations/product-category`, {
  headers: {
    'Authorization': `Bearer ${apiKey}`
  }
})
.then(response => response.json())
.then(data => console.log(data));

// Create relationship
fetch(`${baseUrl}/relations/product-category`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${apiKey}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    parent_id: 123,
    child_id: 456
  })
})
.then(response => response.json())
.then(data => console.log(data));

// Delete relationship
fetch(`${baseUrl}/relations/product-category/MTIzOjQ1Njpwcm9kdWN0LWNhdGVnb3J5`, {
  method: 'DELETE',
  headers: {
    'Authorization': `Bearer ${apiKey}`
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

## Admin Configuration

1. Go to **Settings > CPT REST API** in your WordPress admin
2. Configure the API Base Segment (default: `cpt`)
3. Enable/disable specific Custom Post Types using the toggle switches
4. **Enable Toolset relationship support** if you want to use relationship endpoints
5. Create API keys for authentication
6. Save your settings

Only Custom Post Types that are **enabled** in the admin interface will be available through the API endpoints.

## Notes

- All endpoints require valid API key authentication
- Only Custom Post Types that are enabled in the admin settings are accessible via the API
- Meta fields starting with underscore (_) are considered private and will be ignored
- The API supports both nested meta fields (in a `meta` object) and root-level meta fields
- When both nested and root-level meta fields are provided, they will be merged (root-level takes precedence for duplicate keys)
- Only registered meta fields or all meta fields (if none are specifically registered) will be updated
- Post status is limited to: `publish`, `draft`, `private`, `pending`
- Update operations (PUT/PATCH) only work on published posts for security reasons
- **Toolset relationship endpoints are only available when Toolset relationship support is enabled in the plugin settings and the Toolset plugin is active**
- **Relationship IDs are base64-encoded strings containing parent_id:child_id:relation_slug**
- **The plugin uses multiple fallback methods to interact with Toolset relationships for maximum compatibility**