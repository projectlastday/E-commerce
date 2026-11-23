# 05. API and Service Contracts Specification
**Project:** Sistem Informasi E-Commerce UMKM Berbasis Personalized Hybrid Recommendation System  
**Document ID:** SPEC-DOC-005  
**Target Audience:** Autonomous AI Coding Agents, Frontend Engineers, Integration Testers  
**Status:** Approved for Implementation  

---

## 1. API Architecture & Global Conventions

### 1.1. Transport & Formatting Protocols
- **Base URL**: `/api/v1`
- **Wire Protocol**: HTTPS / HTTP/2, TLS 1.3
- **Data Exchange**: Content-Type `application/json; charset=utf-8`
- **Date-Time Standard**: ISO 8601 extended format with UTC offset (`YYYY-MM-DDTHH:mm:ss.sssZ`)
- **Identification Format**: Canonical UUIDv4 strings for all resource IDs.

### 1.2. Error Contract (RFC 7807 Problem Details)
All non-$2xx$ responses return standard RFC 7807 compliant payloads:
```json
{
  "type": "https://api.ecom-umkm.id/errors/RESOURCE_OUT_OF_STOCK",
  "title": "Item Unavailable",
  "status": 409,
  "detail": "Product variant SKU-NAVY-L has 0 units in stock.",
  "instance": "/api/v1/cart/items",
  "timestamp": "2026-10-30T21:00:00.000Z",
  "invalid_params": []
}
```

---

## 2. Recommendation & Telemetry Endpoints Specification

### 2.1. Personalized Homepage Feed
`GET /api/v1/recommendations/personalized`

Retrieves a multi-stage hybrid ranked list of items tailored to the user or guest session.

#### Query Parameters:
| Parameter | Type | Required | Default | Description |
| :--- | :--- | :---: | :---: | :--- |
| `user_id` | UUID | No | `null` | Authenticated user identifier. |
| `session_id` | String | Yes | - | Client session token (UUID or nanoid). |
| `k` | Integer | No | `10` | Number of items requested ($1 \le k \le 50$). |
| `category_id` | UUID | No | `null` | Filter recommendations to specific category branch. |

#### Response `200 OK`:
```json
{
  "status": "success",
  "meta": {
    "latency_ms": 38.4,
    "model_version": "hybrid-ials-cbf-v1.4",
    "strategy_applied": "HYBRID_WARM",
    "total_returned": 10
  },
  "data": [
    {
      "product_id": "8a72b8d4-5390-410a-b283-9366e4fcda12",
      "name": "Kemeja Flannel Pria Casual Navy",
      "slug": "kemeja-flannel-pria-casual-navy",
      "category_name": "Fashion Pria",
      "base_price": 185000.0,
      "sale_price": 149000.0,
      "rating_avg": 4.85,
      "rating_count": 64,
      "thumbnail_url": "https://cdn.ecom-umkm.id/products/flannel-navy-thumb.webp",
      "recommendation_score": 0.8842,
      "explanation_tag": "Sesuai minat pakaian kasual Anda"
    }
  ]
}
```

---

### 2.2. Product Detail Page (PDP) "Similar Items"
`GET /api/v1/recommendations/similar-items`

Retrieves semantic nearest neighbors based on dense text embeddings in `product_embeddings`.

#### Query Parameters:
| Parameter | Type | Required | Default | Description |
| :--- | :--- | :---: | :---: | :--- |
| `item_id` | UUID | Yes | - | Reference product being viewed. |
| `k` | Integer | No | `6` | Number of similar items ($1 \le k \le 24$). |

#### Response `200 OK`:
```json
{
  "status": "success",
  "meta": {
    "reference_item_id": "8a72b8d4-5390-410a-b283-9366e4fcda12",
    "latency_ms": 14.2
  },
  "data": [
    {
      "product_id": "3f421e02-b035-4921-9878-3a830026e6ef",
      "name": "Kemeja Flannel Tartan Grey Slim Fit",
      "slug": "kemeja-flannel-tartan-grey-slim-fit",
      "base_price": 185000.0,
      "sale_price": null,
      "rating_avg": 4.70,
      "similarity_score": 0.9412,
      "thumbnail_url": "https://cdn.ecom-umkm.id/products/tartan-grey.webp"
    }
  ]
}
```

---

### 2.3. Cart Upsell / Frequently Bought Together
`GET /api/v1/recommendations/frequently-bought-together`

Calculates complementary products frequently co-purchased in historical orders.

#### Query Parameters:
| Parameter | Type | Required | Default | Description |
| :--- | :--- | :---: | :---: | :--- |
| `item_ids` | String (CSV) | Yes | - | Comma-separated product UUIDs in cart. |
| `k` | Integer | No | `4` | Number of items ($1 \le k \le 10$). |

#### Response `200 OK`:
```json
{
  "status": "success",
  "data": [
    {
      "product_id": "7ca64522-83ff-410a-b9c2-5e98bbcf4711",
      "name": "Kaos Kaki Katun Polos Hitam (3 Pasang)",
      "slug": "kaos-kaki-katun-polos-hitam",
      "base_price": 35000.0,
      "sale_price": 29000.0,
      "co_occurrence_lift": 3.82,
      "thumbnail_url": "https://cdn.ecom-umkm.id/products/kaos-kaki.webp"
    }
  ]
}
```

---

### 2.4. Trending & Top-Selling Fallback
`GET /api/v1/recommendations/trending`

Returns time-decayed trending items across the platform or filtered by category.

#### Query Parameters:
| Parameter | Type | Required | Default | Description |
| :--- | :--- | :---: | :---: | :--- |
| `category_id` | UUID | No | `null` | Optional category constraint. |
| `timeframe_days` | Integer | No | `7` | Decay rolling window ($1 \le d \le 30$). |
| `limit` | Integer | No | `10` | Output size ($1 \le limit \le 50$). |

---

### 2.5. Telemetry Interaction Event Ingestion
`POST /api/v1/events/interaction`

Asynchronously buffers user clickstream telemetry. Clients receive immediate `202 Accepted`.

#### Request Headers:
`Content-Type: application/json`

#### Request Body (Single Event):
```json
{
  "session_id": "sess_89f0a213_52bc_4d1a",
  "user_id": "402e3b26-10dc-4560-84cf-eb956fceebf4",
  "event_type": "view_item_deep",
  "entity_type": "product",
  "entity_id": "8a72b8d4-5390-410a-b283-9366e4fcda12",
  "timestamp": "2026-10-30T21:05:14.210Z",
  "metadata": {
    "dwell_time_seconds": 24.5,
    "scroll_depth_percentage": 82,
    "source_component": "similar_items_carousel"
  }
}
```

#### Request Body (Batch Telemetry Array):
```json
{
  "events": [
    {
      "session_id": "sess_89f0a213_52bc_4d1a",
      "user_id": null,
      "event_type": "click",
      "entity_type": "product",
      "entity_id": "3f421e02-b035-4921-9878-3a830026e6ef",
      "timestamp": "2026-10-30T21:05:40.100Z",
      "metadata": {}
    }
  ]
}
```

#### Response `202 Accepted`:
```json
{
  "status": "accepted",
  "queued_count": 1,
  "ingested_at": "2026-10-30T21:05:40.105Z"
}
```

---

## 3. Core E-Commerce API Endpoints

### 3.1. Authentication & Onboarding
- `POST /api/v1/auth/register`: Create customer account with email and password.
- `POST /api/v1/auth/login`: Authenticate and receive `access_token` (JWT, 15m expiry) and `refresh_token` (HTTP-only cookie, 7d expiry).
- `POST /api/v1/users/onboarding`:
  ```json
  {
    "preferred_category_ids": [
      "9efb41c0-21a4-4bf8-b98d-0a88dfb2c899",
      "402e3b26-10dc-4560-84cf-eb956fceebf4"
    ],
    "budget_tier": "MID"
  }
  ```

### 3.2. Catalog & Products
- `GET /api/v1/products`: List products with pagination (`page`, `page_size`), category filter, sorting (`price_asc`, `price_desc`, `popular`).
- `GET /api/v1/products/{id}`: Detailed product info including active variants and rating summary.
- `POST /api/v1/admin/products`: Merchant create product (triggers automatic vector embedding generation task).

### 3.3. Cart & Checkout Lifecycle
- `GET /api/v1/cart`: Retrieve current user/session cart.
- `POST /api/v1/cart/items`: Add variant to cart (`product_id`, `variant_id`, `quantity`).
- `POST /api/v1/orders/checkout`: Convert cart into immutable `orders` row with `PENDING_PAYMENT` state.
- `POST /api/v1/orders/{id}/pay`: Mock / Payment gateway webhook confirmation. Sets status to `PAID`, immediately dispatching `purchase` event signal to recommendation worker.

---

## 4. Pydantic v2 Production Data Contracts

All models are defined in `apps/api/schemas/contracts.py`:

```python
"""
Pydantic v2 Schemas for Strict Input/Output Validation
"""

from typing import List, Optional, Dict, Any, Union
from uuid import UUID
from datetime import datetime
from enum import Enum
from pydantic import BaseModel, Field, HttpUrl, ConfigDict


class EventTypeEnum(str, Enum):
    CLICK = "click"
    VIEW_ITEM = "view_item"
    VIEW_ITEM_DEEP = "view_item_deep"
    SEARCH = "search"
    SEARCH_CLICK = "search_click"
    ADD_TO_WISHLIST = "add_to_wishlist"
    ADD_TO_CART = "add_to_cart"
    REMOVE_FROM_CART = "remove_from_cart"
    BEGIN_CHECKOUT = "begin_checkout"
    PURCHASE = "purchase"


class EntityTypeEnum(str, Enum):
    PRODUCT = "product"
    CATEGORY = "category"
    SEARCH_QUERY = "search_query"


class InteractionEventSchema(BaseModel):
    model_config = ConfigDict(extra="forbid")

    session_id: str = Field(..., min_length=8, max_length=64)
    user_id: Optional[UUID] = None
    event_type: EventTypeEnum
    entity_type: EntityTypeEnum
    entity_id: Optional[UUID] = None
    timestamp: datetime = Field(default_factory=datetime.utcnow)
    metadata: Dict[str, Any] = Field(default_factory=dict)


class BatchInteractionEventSchema(BaseModel):
    events: List[InteractionEventSchema] = Field(..., min_items=1, max_items=100)


class RecommendedProductItem(BaseModel):
    product_id: UUID
    name: str
    slug: str
    category_name: str
    base_price: float
    sale_price: Optional[float] = None
    rating_avg: float
    rating_count: int
    thumbnail_url: Optional[str] = None
    recommendation_score: float
    explanation_tag: Optional[str] = None


class RecommendationResponseMeta(BaseModel):
    latency_ms: float
    model_version: str
    strategy_applied: str
    total_returned: int


class RecommendationResponse(BaseModel):
    status: str = "success"
    meta: RecommendationResponseMeta
    data: List[RecommendedProductItem]


class UserOnboardingRequest(BaseModel):
    preferred_category_ids: List[UUID] = Field(..., min_items=1, max_items=5)
    budget_tier: str = Field("MID", pattern="^(BUDGET|MID|PREMIUM)$")
```
