# 01. Domain and System Requirements Specification
**Project:** Sistem Informasi E-Commerce UMKM Berbasis Personalized Hybrid Recommendation System  
**Document ID:** SPEC-DOC-001  
**Target Audience:** Autonomous AI Coding Agents, Software Architects, ML Engineers  
**Status:** Approved for Implementation  

---

## 1. Domain Overview & UMKM Ecosystem Context

### 1.1. Problem Definition in Small-and-Medium Enterprise (UMKM) E-Commerce
Usaha Mikro, Kecil, dan Menengah (UMKM) in Indonesia represent over 99% of national business units, yet their transition to independent, high-converting digital storefronts faces severe competitive and technical friction. Unlike hyper-scale multi-merchant marketplaces (e.g., Tokopedia, Shopee, Amazon), independent UMKM e-commerce systems operate under asymmetric constraints:
1. **Severe Data Sparsity**: User-item interaction matrices exhibit density typically lower than $0.05\%$ ($< 5$ interactions per 10,000 user-item pairs), rendering standard matrix factorization techniques prone to catastrophic overfitting.
2. **Cold-Start Dominance**: Over $70\%$ of website visitors are anonymous or first-time sessions acquired through social media campaigns (Instagram, TikTok, WhatsApp). Furthermore, catalog items regularly cycle in micro-batches (20 to 100 units per SKU).
3. **Hardware & Operational Budget Constraints**: Systems must run stably on commodity single-node cloud environments (e.g., 2–4 vCPUs, 4–8 GB RAM, costing $<\$30-\$50/\text{month}$) without requiring dedicated Kubernetes clusters or multi-node GPU clusters.
4. **Dynamic Inventory Volatility**: Products with zero physical stock must be immediately de-prioritized or removed from real-time recommendation lists to prevent checkout friction and cart abandonment.

### 1.2. Primary Source Citations
- **Hu, Y., Koren, Y., & Volinsky, C. (2008).** *Collaborative Filtering for Implicit Feedback Datasets.* IEEE International Conference on Data Mining (ICDM 2008). DOI: [10.1109/ICDM.2008.22](https://doi.org/10.1109/ICDM.2008.22).
- **Jannach, D., Lerche, L., & Jugovac, M. (2018).** *Adaptation and Evaluation of Recommendations in E-Commerce: Challenges and Directions.* ACM Transactions on Interactive Intelligent Systems (TiiS).
- **Burke, R. (2002).** *Hybrid Recommender Systems: Survey and Experiments.* User Modeling and User-Adapted Interaction, 12(4), 331–370.
- **Undang-Undang Republik Indonesia No. 27 Tahun 2022** tentang *Pelindungan Data Pribadi (UU PDP)*.

---

## 2. UMKM Characteristic Profile & Technical Trade-offs

| Characteristic Dimension | Marketplace Scale (Amazon/Shopee) | UMKM Storefront Scale (This System) | Architectural Implication |
| :--- | :--- | :--- | :--- |
| **Catalog Size ($M$)** | $10^7 - 10^9$ SKUs | $10^2 - 5 \times 10^4$ SKUs | In-memory indexing and fast ANN via pgvector HNSW are feasible within $< 1\text{GB}$ RAM. |
| **Active Users ($N$)** | $10^7 - 10^8$ MAU | $10^3 - 10^5$ MAU | User profile representations can be updated near-realtime in Redis without distributed stream processors (e.g., Kafka). |
| **Interaction Density** | $0.5\% - 2.0\%$ | $< 0.05\%$ | Pure Collaborative Filtering fails; must rely heavily on Content-Based semantics and hybrid fallbacks. |
| **Anonymous Traffic** | $\sim 20\% - 30\%$ | $70\% - 85\%$ | First-class session-based and cookie-tracked telemetry is mandatory for immediate personalization. |
| **Compute Budget** | Distributed Spark, Trino, Ray clusters | Single-node Docker / Compose (FastAPI + Postgres) | All ML models must feature low training complexity ($\mathcal{O}(K \cdot |R|)$) executable on CPU under $< 5\text{ minutes}$. |

---

## 3. Functional Requirements (FR) Specification

The system is partitioned into two core subsystems:
1. **Core E-Commerce Subsystem (CES)**: Handles authentication, product catalog, cart, order lifecycle, payments, reviews, and admin dashboard.
2. **Recommendation & Telemetry Subsystem (RTS)**: Captures client-side and server-side events, updates user latent states, performs candidate generation, ranking, and delivers personalized payloads.

### 3.1. Core E-Commerce Subsystem (CES)

#### FR-CAT-01: Product Catalog & Hierarchical Categorization
- **Description**: The system shall manage structured product data, hierarchical categories (up to 3 levels: Root -> Subcategory -> Leaf), SKU variants (e.g., size, color, weight), dynamic stock quantities, and rich metadata.
- **Inputs**: Name, slug, SKU, category_id, description (Markdown/HTML), base_price, sale_price, stock_quantity, attributes (JSONB: e.g. `{"color": "navy", "material": "cotton_combed_30s"}`), images (URLs).
- **Outputs**: Normalized product entities with variant options and stock availability indicator.
- **Business Rules**:
  - If `stock_quantity <= 0`, product status changes to `OUT_OF_STOCK` and is automatically masked from Top-N recommendation candidate sets.
  - Price updates must invalidate the product vector metadata cache in Redis.

#### FR-USR-01: User Management & Cold-Start Preference Onboarding
- **Description**: Users may register via email/password or OAuth2 (Google). Upon first registration or first guest entry, the system shall provide an optional 3-step rapid preference selector (e.g., favorite categories, preferred price range).
- **Inputs**: User credentials, preferred category IDs, budget tier.
- **Outputs**: JWT access token, refresh token, initialized `user_profiles` vector.
- **Acceptance Criteria**: If user skips onboarding, system falls back to session-based interaction tracking without blocking checkout.

#### FR-ORD-01: Cart & Checkout Management
- **Description**: Persistent cart for authenticated users; cookie/session-backed cart for guest users with seamless merge upon login.
- **Inputs**: `product_id`, `variant_id`, `quantity`.
- **Outputs**: Cart breakdown, price calculation, shipping weight sum, discount deductions.
- **Business Rules**: Stock is soft-locked during checkout checkout-intent creation (TTL: 15 minutes). Unpaid orders trigger stock release.

#### FR-ORD-02: Order State Machine & Fulfillment
- **States**: `PENDING_PAYMENT` -> `PAID` -> `PROCESSING` -> `SHIPPED` -> `DELIVERED` -> `COMPLETED` | `CANCELLED` | `REFUNDED`.
- **Triggers**: State transition to `PAID` or `COMPLETED` emits a high-priority implicit feedback signal (`event_type: purchase`) with weight $w = 5.0$.

#### FR-REV-01: Ratings and Explicit Feedback
- **Description**: Customers who purchased a product and reached `DELIVERED` or `COMPLETED` order status may submit an explicit review.
- **Inputs**: `rating` (Integer: 1 to 5), `review_text` (String, optional), `media_urls` (Array of Strings).
- **Outputs**: Calculated average rating, review count per product.
- **Acceptance Criteria**: Submitting a 4 or 5-star review boosts user-item preference affinity; 1 or 2-star review acts as an explicit negative mask in future recommendation queries.

#### FR-ADM-01: Merchant/Admin Management
- **Description**: Dedicated dashboard for inventory management, bulk upload, sales reports, and recommendation performance observability (CTR, Rec-driven revenue).

---

### 3.2. Recommendation & Telemetry Subsystem (RTS)

#### FR-REC-01: Homepage Personalized Recommendation Feed
- **Endpoint**: `GET /api/v1/recommendations/personalized?user_id={id}&limit=12`
- **Behavior**:
  - For **authenticated users with history**: Deliver hybrid Top-N recommendations balancing user past implicit preferences (CF) and semantic similarity to recently viewed items (CBF).
  - For **cold-start / anonymous users**: Deliver category-boosted popularity items blended with multi-armed bandit exploration.
- **Acceptance Criteria**: Latency must stay below $90\text{ms}$ at 95th percentile under concurrent load.

#### FR-REC-02: Product Detail Page (PDP) "Similar Items" Widget
- **Endpoint**: `GET /api/v1/recommendations/similar-items?item_id={id}&limit=6`
- **Behavior**: Retrieve the top $K$ nearest neighbors based on vector cosine similarity of product metadata embeddings (Title, Category, Tags, Description) stored in PostgreSQL `pgvector`.
- **Constraint**: Must exclude the query `item_id` itself and any items with `stock_quantity <= 0`.

#### FR-REC-03: Cart Upsell / "Frequently Bought Together" Widget
- **Endpoint**: `GET /api/v1/recommendations/frequently-bought-together?item_ids={id1,id2}&limit=4`
- **Behavior**: Uses item co-occurrence association mining (Lift / Jaccard similarity across historical order items) or item-item latent factors to suggest complementary purchases.

#### FR-REC-04: Category & Sitewide Trending Widget
- **Endpoint**: `GET /api/v1/recommendations/trending?category_id={id}&timeframe_days=7&limit=10`
- **Behavior**: Decay-weighted popularity score calculated over rolling 7-day interactions:
  $$\text{Score}(i) = \sum_{e \in E_i} w(e) \cdot \exp\left(-\frac{\Delta t_e}{\tau}\right)$$

#### FR-EVT-01: High-Throughput Event Ingestion
- **Endpoint**: `POST /api/v1/events/interaction`
- **Payload**: Client session token, user identifier (if authenticated), event type, target entity ID, metadata (dwell time, scroll percentage, referrer).
- **Behavior**: Asynchronous ingestion via Redis Queue buffer to isolate database write latency from client response.

---

## 4. Non-Functional Requirements (NFR) Specification

### 4.1. Latency Budget Allocation (Total p95 Budget: $\le 100\text{ms}$)
The recommendation serving path must execute within a deterministic time window to prevent degrading the Core E-Commerce frontend Time-To-Interactive (TTI).

```
[Client Request]
       │
       ▼ (DNS + Network TLS handshake: ~20ms)
[API Gateway / Reverse Proxy (Nginx / Caddy)]
       │ (5ms)
       ▼
[FastAPI Serving Layer]
       │
       ├─► [Redis Cache Check: User Vector / Pre-ranked List] ─── (Hit: ~3ms -> Return)
       │         │ (Miss: ~3ms)
       │         ▼
       ├─► [Candidate Retrieval (pgvector HNSW / CF Top-K)] ───── (~25ms)
       │         │
       │         ▼
       ├─► [Hybrid Scoring & Weight Blending] ─────────────────── (~15ms)
       │         │
       │         ▼
       ├─► [Business Filters (Deduplication, Out of Stock)] ───── (~10ms)
       │         │
       │         ▼
       └─► [JSON Serialization & Response Delivery] ──────────── (~5ms)
                                                      Total Internal: ~58ms
                                                      Total End-to-End: ~78ms (< 100ms Budget)
```

### 4.2. Throughput & Scalability Targets
- **Target Concurrent Load**: 100 requests per second (RPS) sustained, 250 RPS burst on standard promotional flash sales (e.g., Harbolnas, Payday).
- **Hardware Footprint**: Standard VPS (4 vCPUs, 8 GB RAM, NVMe SSD). Memory allocation:
  - PostgreSQL 16 + pgvector: $2.5\text{ GB}$ shared buffers / work mem.
  - Redis 7: $1.0\text{ GB}$ maxmemory (LRU eviction).
  - FastAPI Core + RecSys: $2.0\text{ GB}$ (4 worker processes).
  - Background Retraining Task (ARQ/Celery): $1.5\text{ GB}$ peak during batch factor updates.
  - OS & Buffer: $1.0\text{ GB}$.

### 4.3. Data Retention & Partitioning
- Clickstream event logs (`event_logs`) must be partitioned by month (`RANGE (created_at)`).
- Hot event storage in PostgreSQL: 90 days. Raw events $> 90$ days are rolled into aggregated daily interaction matrices or archived to cold S3/MinIO parquet files.

### 4.4. Security, Privacy & UU PDP Indonesia Compliance
- **Data Subject Rights**: System must support programmatic deletion (Right to be Forgotten) of user interaction histories within 72 hours of request.
- **Pseudonymization**: Anonymous sessions are identified via high-entropy UUIDv4 stored in HTTP-only, Secure, SameSite=Strict cookies.
- **Zero Raw PII Exposure to ML**: Model training inputs use hashed `user_id` and numeric indexes. No plain-text phone numbers, real names, or delivery addresses are piped to recommendation feature matrices.

---

## 5. Explicit vs. Implicit Feedback Taxonomy & Signal Capture

In an UMKM e-commerce environment, explicit feedback (ratings/reviews) accounts for less than $1.5\%$ of all data points. Therefore, the system is designed fundamentally around **implicit feedback telemetry**.

### 5.1. Signal Taxonomy & Relative Confidence Weighting ($r_{ui}$)

| Signal Name | Event Type (`event_type`) | Inferred User Intent | Raw Confidence Weight ($w_e$) | Threshold / Qualification Criteria |
| :--- | :--- | :--- | :---: | :--- |
| **Impression (Skip)** | `item_impression` | Passive exposure | $0.0$ | Rendered in viewport $> 1.0\text{s}$, but no click. |
| **Product Click** | `click` | Initial interest | $1.0$ | Target item clicked from catalog/feed. |
| **PDP View (Short)** | `view_item` | Shallow interest | $1.0$ | Dwell time $3.0\text{s} \le t < 15.0\text{s}$. |
| **PDP View (Deep)** | `view_item_deep` | High interest / reading | $2.0$ | Dwell time $t \ge 15.0\text{s}$ AND scroll depth $\ge 50\%$. |
| **Search Click** | `search_click` | Targeted search intent | $1.5$ | Clicked from query results. |
| **Wishlist Add** | `add_to_wishlist` | Explicit save for later | $3.0$ | Clicked bookmark / wishlist icon. |
| **Add to Cart** | `add_to_cart` | High transactional intent| $3.5$ | Product variant added to active shopping cart. |
| **Checkout Initiate**| `begin_checkout` | Purchase commitment | $4.0$ | Navigated to checkout view. |
| **Purchase Complete**| `purchase` | Strongest positive signal | $5.0$ | Payment confirmed (`order_status = 'PAID'`). |
| **Cart Remove** | `remove_from_cart` | Negative / regret signal | $-1.5$ | Explicit item deletion from cart. |
| **Negative Review** | `negative_review` | Strong dissatisfaction | $-4.0$ | Verified purchase review $\le 2$ stars. |

### 5.2. Mathematical Interaction Value Formulation
The aggregated implicit interaction value $r_{ui}$ between user $u$ and item $i$ over an observation window $T$ is computed with exponential time-decay:

$$r_{ui} = \sum_{e \in E(u, i)} w_e \cdot \exp\left(-\frac{\ln(2) \cdot (t_{current} - t_e)}{t_{half}}\right)$$

Where:
- $E(u, i)$ is the set of all interaction events between user $u$ and item $i$.
- $w_e$ is the raw signal weight from the table above.
- $t_e$ is the timestamp of the event.
- $t_{half}$ is the half-life decay parameter set to **14 days** ($1,209,600\text{ seconds}$) for fashion/lifestyle UMKM, or **7 days** for fast-moving consumer goods (FMCG).

### 5.3. Noise Filtering & Sessionization Rules
1. **Bot & Crawling Rejection**: Any session exceeding 60 page views per minute or lacking valid browser canvas/user-agent signatures is rejected from the training ingest pipeline.
2. **Bounce Filter**: Any `view_item` event with a dwell time $< 3.0\text{ seconds}$ is classified as an accidental click and assigned $w_e = 0.0$.
3. **Session Inactivity Boundary**: A session is formally terminated after 30 consecutive minutes of inactivity. Subsequent interactions initialize a new `session_id`.
4. **Saturation Capping**: To prevent single-session power users or repetitive clickers from skewing the interaction matrix, the maximum daily interaction score contribution for any specific $(u, i)$ pair is capped at $r_{ui}^{max} = 15.0$.

---

## 6. System Requirements Traceability Matrix

| Requirement ID | Module / Component | Associated API Endpoint | Database Entities |
| :--- | :--- | :--- | :--- |
| **FR-CAT-01** | Catalog Service | `GET /api/v1/products`, `POST /api/v1/products` | `products`, `categories`, `product_variants` |
| **FR-USR-01** | Auth & Identity Service | `POST /api/v1/auth/login`, `POST /api/v1/users/onboarding` | `users`, `user_profiles` |
| **FR-ORD-01** | Cart & Checkout Service| `POST /api/v1/cart/items`, `POST /api/v1/orders/checkout` | `carts`, `cart_items`, `orders` |
| **FR-REV-01** | Review Service | `POST /api/v1/reviews` | `reviews`, `orders` |
| **FR-REC-01** | Hybrid Rec Engine | `GET /api/v1/recommendations/personalized` | `product_embeddings`, `user_interactions`, Redis |
| **FR-REC-02** | Vector Similarity Engine| `GET /api/v1/recommendations/similar-items` | `product_embeddings` (pgvector HNSW) |
| **FR-EVT-01** | Event Ingestion Worker | `POST /api/v1/events/interaction` | Redis Queue, `event_logs`, `user_interactions` |
