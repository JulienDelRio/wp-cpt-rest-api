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
  "version": "1.0.0"
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

## Admin Configuration

1. Go to **Settings > CPT REST API** in your WordPress admin
2. Configure the API Base Segment (default: `cpt`)
3. Enable/disable specific Custom Post Types using the toggle switches
4. Create API keys for authentication
5. Save your settings

Only Custom Post Types that are **enabled** in the admin interface will be available through the API endpoints.