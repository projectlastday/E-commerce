# 03. Data Models and Database Schema Specification (MySQL & Laravel 11)
**Project:** Sistem Informasi E-Commerce UMKM Berbasis Personalized Hybrid Recommendation System  
**Document ID:** SPEC-DOC-003  
**Target Audience:** Autonomous AI Coding Agents, Database Administrators, Backend Engineers  
**Status:** Approved for Implementation (MySQL 8.0+ / 8.4+ & Laravel 11)  

---

## 1. Database Architecture & Vector Strategy (MySQL + In-Memory FAISS)

The persistence layer uses **MySQL 8.0+ / 8.4+ (InnoDB engine, utf8mb4 collation)** managed via **Laravel 11 Eloquent Migrations**.

### 1.1. Vector Similarity Strategy with MySQL
Unlike PostgreSQL which relies on `pgvector`, MySQL is optimized for ACID relational transactions. For the Hybrid Recommendation Engine:
1. **Relational Storage (MySQL)**: Product metadata, categories, users, orders, and event logs are stored in standard MySQL tables. The dense product vectors (384-dimensional text embeddings) are persisted in the `product_embeddings` table as `JSON` (or `MEDIUMBLOB` float32 byte array / native `VECTOR` if MySQL 8.4+).
2. **High-Speed Vector Search (In-Memory FAISS / NumPy in Python RecSys)**:
   - For Indonesian UMKM catalog sizes ($500 - 50,000$ SKUs), running vector nearest-neighbor search in-memory via **FAISS** (`faiss.IndexFlatIP` or `IndexHNSWFlat`) or NumPy cosine matrix operations consumes only **$< 20\text{MB}$ RAM** and executes in **$< 2\text{ms}$**.
   - The Python RecSys engine loads and caches the vector index on startup, automatically syncing updates when new products are added or updated in MySQL via webhooks or Redis pub/sub.
   - This architectural separation completely protects MySQL from CPU-intensive vector matrix math, keeping the e-commerce database responsive under high concurrency.

---

## 2. Complete Relational Database DDL (MySQL 8.0+ / InnoDB)

### 2.1. Authentication, Users & Cold-Start Profiles
```sql
-- Table: users
CREATE TABLE users (
    id CHAR(36) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('CUSTOMER', 'MERCHANT_ADMIN', 'SUPERADMIN') NOT NULL DEFAULT 'CUSTOMER',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: user_profiles (contains explicit onboarding preferences & real-time preference vectors)
CREATE TABLE user_profiles (
    user_id CHAR(36) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(32) NULL,
    budget_tier ENUM('BUDGET', 'MID', 'PREMIUM') DEFAULT 'MID',
    preferred_category_ids JSON NULL, -- Array of category UUIDs: ["uuid1", "uuid2"]
    preference_embedding JSON NULL,   -- 384-d dense vector array for real-time user interest
    onboarding_completed TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_user_profiles_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.2. Categories, Products, and Variants
```sql
-- Table: categories (hierarchical adjacency list model)
CREATE TABLE categories (
    id CHAR(36) NOT NULL,
    parent_id CHAR(36) NULL,
    name VARCHAR(128) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    description TEXT NULL,
    image_url VARCHAR(512) NULL,
    level INT NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    KEY idx_categories_parent_id (parent_id),
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: products
CREATE TABLE products (
    id CHAR(36) NOT NULL,
    category_id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(280) NOT NULL,
    sku VARCHAR(64) NOT NULL,
    description TEXT NOT NULL,
    base_price DECIMAL(12, 2) NOT NULL,
    sale_price DECIMAL(12, 2) NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    status ENUM('ACTIVE', 'DRAFT', 'ARCHIVED', 'OUT_OF_STOCK') NOT NULL DEFAULT 'ACTIVE',
    rating_avg DECIMAL(3, 2) NOT NULL DEFAULT 0.00,
    rating_count INT NOT NULL DEFAULT 0,
    attributes JSON NULL,       -- Custom key-value pairs (e.g. {"material": "batik", "origin": "Solo"})
    image_urls JSON NULL,       -- Array of image URLs: ["https://...", "https://..."]
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    UNIQUE KEY uq_products_sku (sku),
    KEY idx_products_category (category_id),
    KEY idx_products_status_stock (status, stock_quantity),
    FULLTEXT KEY ft_products_name_desc (name, description),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: product_variants (for SKU-level sizing, colors, flavors)
CREATE TABLE product_variants (
    id CHAR(36) NOT NULL,
    product_id CHAR(36) NOT NULL,
    sku VARCHAR(64) NOT NULL,
    title VARCHAR(128) NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    attributes JSON NULL,       -- Variant attributes (e.g. {"size": "XL", "color": "Navy"})
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_variants_sku (sku),
    KEY idx_variants_product (product_id),
    CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.3. Vector Storage Specification (MySQL Table for Embeddings)
```sql
-- Table: product_embeddings (Stores dense vector embeddings generated by Sentence-Transformers)
CREATE TABLE product_embeddings (
    product_id CHAR(36) NOT NULL,
    embedding JSON NOT NULL,     -- 384-element float array [0.023, -0.129, ...]
    content_hash VARCHAR(64) NOT NULL, -- SHA256 of concatenated text (name + category + description)
    model_version VARCHAR(32) NOT NULL DEFAULT 'minilm-l12-v2',
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (product_id),
    CONSTRAINT fk_embeddings_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.4. Orders, Cart, and Reviews
```sql
-- Table: orders
CREATE TABLE orders (
    id CHAR(36) NOT NULL,
    order_number VARCHAR(32) NOT NULL,
    user_id CHAR(36) NOT NULL,
    status ENUM('PENDING_PAYMENT', 'PAID', 'PROCESSING', 'SHIPPED', 'DELIVERED', 'COMPLETED', 'CANCELLED', 'REFUNDED') NOT NULL DEFAULT 'PENDING_PAYMENT',
    total_amount DECIMAL(12, 2) NOT NULL,
    shipping_cost DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    shipping_address JSON NOT NULL,
    payment_method VARCHAR(64) NOT NULL,
    payment_reference VARCHAR(128) NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_number (order_number),
    KEY idx_orders_user_status (user_id, status),
    KEY idx_orders_created_at (created_at),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: order_items
CREATE TABLE order_items (
    id CHAR(36) NOT NULL,
    order_id CHAR(36) NOT NULL,
    product_id CHAR(36) NOT NULL,
    variant_id CHAR(36) NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(12, 2) NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    KEY idx_order_items_product (product_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT,
    CONSTRAINT fk_order_items_variant FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: reviews (Explicit Feedback)
CREATE TABLE reviews (
    id CHAR(36) NOT NULL,
    product_id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    order_id CHAR(36) NOT NULL,
    rating TINYINT NOT NULL, -- 1 to 5
    review_text TEXT NULL,
    media_urls JSON NULL,
    is_verified_purchase TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_order_product (user_id, order_id, product_id),
    KEY idx_reviews_product_rating (product_id, rating),
    CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_reviews_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.5. Telemetry Event Logs & User Interactions Matrix
```sql
-- Table: event_logs (Clickstream telemetry events)
CREATE TABLE event_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id VARCHAR(64) NOT NULL,
    user_id CHAR(36) NULL,
    event_type VARCHAR(32) NOT NULL,
    entity_type VARCHAR(32) NOT NULL,
    entity_id CHAR(36) NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_event_logs_user_event (user_id, event_type),
    KEY idx_event_logs_session (session_id),
    KEY idx_event_logs_entity (entity_type, entity_id),
    KEY idx_event_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aggregated Interaction Matrix Table for Fast Implicit ALS Extraction
CREATE TABLE user_interactions (
    user_id CHAR(36) NOT NULL,
    product_id CHAR(36) NOT NULL,
    raw_score DOUBLE NOT NULL DEFAULT 0.0,
    confidence_score DOUBLE NOT NULL DEFAULT 1.0,
    interaction_count INT NOT NULL DEFAULT 1,
    last_interacted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, product_id),
    KEY idx_user_interactions_product (product_id),
    KEY idx_user_interactions_updated (last_interacted_at),
    CONSTRAINT fk_user_interactions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_interactions_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: recommendation_logs (For offline counterfactual evaluation & A/B testing)
CREATE TABLE recommendation_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id CHAR(36) NULL,
    session_id VARCHAR(64) NOT NULL,
    recommendation_type VARCHAR(32) NOT NULL,
    model_version VARCHAR(32) NOT NULL,
    recommended_product_ids JSON NOT NULL, -- Array of UUIDs
    clicked_product_id CHAR(36) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rec_logs_session (session_id),
    KEY idx_rec_logs_clicked (clicked_product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
```

---

## 3. Clickstream Event JSON Schemas (Draft-07)

Every client interaction sent to `POST /api/v1/events/interaction` must conform strictly to these schemas before entering the Redis buffer.

### 3.1. Base Envelope Schema
```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "ClientInteractionEnvelope",
  "type": "object",
  "required": ["session_id", "event_type", "entity_type", "entity_id", "timestamp", "metadata"],
  "properties": {
    "session_id": { "type": "string", "minLength": 16, "maxLength": 64 },
    "user_id": { "type": ["string", "null"], "format": "uuid" },
    "event_type": {
      "type": "string",
      "enum": [
        "click", "view_item", "view_item_deep", "search", 
        "search_click", "add_to_wishlist", "add_to_cart", 
        "remove_from_cart", "begin_checkout", "purchase"
      ]
    },
    "entity_type": { "type": "string", "enum": ["product", "category", "search_query"] },
    "entity_id": { "type": "string", "format": "uuid" },
    "timestamp": { "type": "string", "format": "date-time" },
    "metadata": { "type": "object" }
  }
}
```

### 3.2. Detailed Event-Specific Metadata Payloads

#### `view_item` / `view_item_deep`
```json
{
  "dwell_time_seconds": 18.5,
  "scroll_depth_percentage": 75,
  "referrer_page": "homepage_rec_feed",
  "viewport_dimensions": "390x844"
}
```

#### `search`
```json
{
  "query_text": "kaos polos navy combed 30s",
  "result_count": 14,
  "filters_applied": {
    "category": "fashion-pria",
    "max_price": 100000
  }
}
```

#### `add_to_cart` / `remove_from_cart`
```json
{
  "variant_id": "b1a7d652-3a9d-4c3e-89a5-fcb8d1b11029",
  "quantity": 2,
  "unit_price": 75000.0,
  "source_widget": "similar_items"
}
```

#### `purchase`
```json
{
  "order_id": "9efb41c0-21a4-4bf8-b98d-0a88dfb2c899",
  "order_number": "ORD-20261030-0042",
  "items": [
    { "product_id": "e2e8e97a-9cf2-4a0b-81f1-3ec709db118a", "quantity": 2, "price": 75000.0 }
  ],
  "total_revenue": 150000.0,
  "payment_channel": "QRIS"
}
```

---

## 4. Implicit Feedback Confidence Scoring Formulation & SQL Aggregator

### 4.1. Mathematical Formulation
Following **Hu, Koren, & Volinsky (2008)**, the raw score $r_{ui}$ is transformed into a continuous confidence metric $c_{ui}$ and binary preference $p_{ui}$:

$$p_{ui} = \begin{cases} 1 & \text{if } r_{ui} > 0 \\ 0 & \text{if } r_{ui} = 0 \end{cases}$$

$$c_{ui} = 1 + \alpha \cdot r_{ui}$$

Where:
- $\alpha = 40$ (standard linear confidence scaling factor).
- $r_{ui} = \min\left(15.0, \sum_{e} w_e \cdot \exp\left(-\frac{\ln(2) \cdot (t_{\text{current}} - t_e)}{14 \times 86400}\right)\right)$.

### 4.2. Atomic Materialization Query (Periodic Cron / Worker)
The background worker executes the following SQL query every hour to synchronize raw events from `event_logs` into the high-performance training table `user_interactions`:

```sql
INSERT INTO user_interactions (user_id, product_id, raw_score, confidence_score, interaction_count, last_interacted_at)
SELECT 
    el.user_id,
    el.entity_id AS product_id,
    LEAST(15.0, SUM(
        CASE el.event_type
            WHEN 'click' THEN 1.0
            WHEN 'view_item' THEN 1.0
            WHEN 'view_item_deep' THEN 2.0
            WHEN 'search_click' THEN 1.5
            WHEN 'add_to_wishlist' THEN 3.0
            WHEN 'add_to_cart' THEN 3.5
            WHEN 'begin_checkout' THEN 4.0
            WHEN 'purchase' THEN 5.0
            WHEN 'remove_from_cart' THEN -1.5
            ELSE 0.0
        END * EXP(-0.693147 * (EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - el.created_at)) / 1209600.0))
    )) AS computed_raw_score,
    (1.0 + 40.0 * LEAST(15.0, SUM(
        CASE el.event_type
            WHEN 'click' THEN 1.0
            WHEN 'view_item' THEN 1.0
            WHEN 'view_item_deep' THEN 2.0
            WHEN 'search_click' THEN 1.5
            WHEN 'add_to_wishlist' THEN 3.0
            WHEN 'add_to_cart' THEN 3.5
            WHEN 'begin_checkout' THEN 4.0
            WHEN 'purchase' THEN 5.0
            WHEN 'remove_from_cart' THEN -1.5
            ELSE 0.0
        END * EXP(-0.693147 * (EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - el.created_at)) / 1209600.0))
    ))) AS computed_confidence_score,
    COUNT(el.id) AS interaction_count,
    MAX(el.created_at) AS last_interacted_at
FROM event_logs el
WHERE el.user_id IS NOT NULL 
  AND el.entity_type = 'product'
  AND el.created_at >= (CURRENT_TIMESTAMP - INTERVAL '60 days')
GROUP BY el.user_id, el.entity_id
ON CONFLICT (user_id, product_id) DO UPDATE SET
    raw_score = EXCLUDED.raw_score,
    confidence_score = EXCLUDED.confidence_score,
    interaction_count = EXCLUDED.interaction_count,
    last_interacted_at = EXCLUDED.last_interacted_at;
```
