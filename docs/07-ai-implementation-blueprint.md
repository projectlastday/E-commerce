# 07. AI Implementation Blueprint & Execution Roadmap
**Project:** Sistem Informasi E-Commerce UMKM Berbasis Personalized Hybrid Recommendation System  
**Document ID:** SPEC-DOC-007  
**Target Audience:** Autonomous AI Coding Agents, Software Engineers, Technical Leads  
**Status:** Approved for Implementation  

---

## 1. Project Directory Structure (Laravel 11 + RecSys Sidecar)

```
e_com/
├── .env.example
├── .gitignore
├── README.md
├── docker-compose.yml
├── docs/
│   ├── 01-domain-and-system-requirements.md
│   ├── 02-system-architecture.md
│   ├── 03-data-models-and-schema.md
│   ├── 04-hybrid-recommendation-engine-spec.md
│   ├── 05-api-and-service-contracts.md
│   ├── 06-evaluation-and-mlops-pipeline.md
│   ├── 07-ai-implementation-blueprint.md
│   └── README.md
├── app/                             # Laravel 11 Core E-Commerce Backend
│   ├── Http/
│   │   ├── Controllers/             # Storefront & API Controllers
│   │   │   ├── CatalogController.php
│   │   │   ├── CartController.php
│   │   │   ├── CheckoutController.php
│   │   │   ├── OrderController.php
│   │   │   └── TelemetryController.php # Ingests clickstream events
│   │   └── Middleware/
│   │       └── TrackVisitorSession.php # Assigns visitor UUID & guest cookie
│   ├── Models/                      # Eloquent Models (User, Product, Order, etc.)
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Category.php
│   │   ├── Order.php
│   │   └── EventLog.php
│   ├── Filament/                    # Filament v3 UMKM Merchant Panel
│   │   └── Resources/               # ProductResource, OrderResource, Analytics
│   ├── Services/
│   │   ├── RecSysClient.php         # HTTP Client to Python RecSys Engine
│   │   ├── TelemetryService.php     # Dispatches telemetry to Redis Queue
│   │   └── PaymentService.php       # Midtrans / Xendit integration
│   └── Jobs/
│       ├── ProcessTelemetryEvent.php# Async queue job buffering to DB
│       └── AggregateInteractions.php# Computes implicit r_ui matrix
├── config/
│   ├── recsys.php                   # RecSys service URL, timeout, fallback rules
│   └── database.php                 # PostgreSQL (pgvector) connection
├── database/
│   ├── migrations/                  # Laravel Migrations for complete schema
│   └── seeders/                     # Indonesian UMKM product & category seeders
├── recsys/                          # Python FastAPI Hybrid Recommendation Engine
│   ├── requirements.txt
│   ├── pyproject.toml
│   ├── Dockerfile.recsys
│   ├── app/
│   │   ├── main.py                  # FastAPI entrypoint (/api/v1/recommendations)
│   │   ├── config.py                # Database & Redis settings
│   │   ├── algorithms/
│   │   │   ├── content_based.py     # pgvector HNSW & Text Embeddings
│   │   │   ├── collaborative.py     # Implicit ALS (Hu et al. 2008)
│   │   │   ├── hybrid_engine.py     # 2-Stage Retrieval, Adaptive Blend, MMR
│   │   │   └── cold_start.py        # Explore-exploit bandit & category boost
│   │   ├── pipelines/
│   │   │   ├── train_als.py         # Periodic matrix factorization training
│   │   │   └── generate_embeddings.py# Sentence-Transformers vectorizer
│   │   └── evaluation/
│   │       └── evaluator.py         # Offline ranking metrics (NDCG, Recall, ILD)
├── resources/                       # Frontend Storefront Views & Assets
│   ├── views/                       # Blade / Livewire Templates
│   │   ├── layouts/app.blade.php
│   │   ├── home.blade.php           # Personalized Carousel & Feed
│   │   ├── products/show.blade.php  # PDP + Similar Items Widget
│   │   └── cart.blade.php           # Cart + Upsell Recommendations
│   ├── js/
│   │   └── telemetry.js             # Dwell time, click & scroll tracker
│   └── css/
├── routes/
│   ├── web.php                      # Storefront Web Routes
│   └── api.php                      # Telemetry & Webhook API Routes
├── composer.json
└── artisan
│
└── scripts/
    ├── seed_database.py             # Realistic Indonesian UMKM catalog & synthetic events
    ├── run_retraining.sh            # Cron retraining orchestrator
    └── init_db.sh                   # Creates extensions & runs migrations
```

---

## 2. Phased Execution Roadmap

```mermaid
gantt
    title Phased Development Roadmap for AI Coding Agents
    dateFormat  X
    axisFormat %d
    section Infrastructure & Core
    Phase 1: DB, Docker, Auth, Schema       :active, p1, 0, 2
    Phase 2: Catalog, Cart, Order Machine   :p2, 2, 4
    section Telemetry & ML
    Phase 3: Event Ingestion & Redis Buffer :p3, 4, 6
    Phase 4: pgvector Embeddings & Similar  :p4, 6, 8
    Phase 5: Implicit ALS & Hybrid Engine   :p5, 8, 11
    section Frontend & Validation
    Phase 6: Next.js Frontend Integration   :p6, 11, 14
    Phase 7: Offline Evaluation & Testing   :p7, 14, 16
```

### Phase 1: Environment, Docker Compose, Database & Auth
- **Goal**: Establish the base container ecosystem, execute PostgreSQL extensions and DDL migrations, and build authentication with cold-start onboarding.
- **Inputs**: `docs/02-system-architecture.md`, `docs/03-data-models-and-schema.md`.
- **Deliverables**:
  - `docker-compose.yml` with `postgres` (pgvector enabled), `redis`, and service stubs.
  - SQLAlchemy 2.0 Async declarative models for `users`, `user_profiles`, `categories`, `products`.
  - Alembic migrations for initial schema and `vector` extension.
  - JWT authentication endpoints (`/auth/register`, `/auth/login`, `/users/onboarding`).
- **Verification Gate**:
  - `docker compose up -d` brings up PostgreSQL with vector extension confirmed (`SELECT '[]'::vector;` returns OK).
  - Pytest suite passes for registration and onboarding token issuance.

---

### Phase 2: Core E-Commerce Catalog & Order Lifecycle
- **Goal**: Full CRUD for products, hierarchical categories, shopping cart, and order state transition machine.
- **Inputs**: `docs/01-domain-and-system-requirements.md` (FR-CAT-01, FR-ORD-01, FR-ORD-02).
- **Deliverables**:
  - Product catalog endpoints with pagination and category filtering.
  - Variant stock decrement and reservation logic.
  - Cart addition and checkout state transition to `PAID`.
- **Verification Gate**:
  - Automated integration test simulating customer adding variant to cart, checking out, and marking order as paid.

---

### Phase 3: Telemetry Ingestion & Asynchronous Buffer
- **Goal**: High-throughput clickstream event logging pipeline that does not block core API requests.
- **Inputs**: `docs/03-data-models-and-schema.md` (Section 3), `docs/05-api-and-service-contracts.md` (Section 2.5).
- **Deliverables**:
  - `POST /api/v1/events/interaction` endpoint with Pydantic v2 validation.
  - Redis List buffering (`LPUSH queue:events:raw`).
  - ARQ background worker flushing buffered events into partitioned `event_logs` table every 5 seconds.
  - Periodic aggregation task calculating time-decayed $r_{ui}$ scores into `user_interactions`.
- **Verification Gate**:
  - Locust load test firing 500 concurrent event requests; API response time $< 8\text{ms}$ with zero dropped events in PostgreSQL.

---

### Phase 4: Product Embeddings & pgvector Semantic Similarity (CBF)
- **Goal**: Compute dense text representations of products and implement real-time nearest neighbor similarity.
- **Inputs**: `docs/04-hybrid-recommendation-engine-spec.md` (Section 2).
- **Deliverables**:
  - Text serialization utility concatenating title, category path, and description.
  - Embedding pipeline using `sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2`.
  - Async listener updating `product_embeddings` on product create/update.
  - `GET /api/v1/recommendations/similar-items` endpoint powered by pgvector cosine distance (`<=>`).
- **Verification Gate**:
  - Querying similar items for "Kemeja Flannel Navy" returns related shirts and excludes cooking utensils; latency $< 20\text{ms}$.

---

### Phase 5: Collaborative Filtering (ALS) & Hybrid Orchestrator
- **Goal**: Train implicit matrix factorization and combine with semantic search via switching weights and MMR.
- **Inputs**: `docs/04-hybrid-recommendation-engine-spec.md` (Sections 3, 4, 6).
- **Deliverables**:
  - `packages/ml-engine/trainer.py` training `implicit.als.AlternatingLeastSquares` on `user_interactions`.
  - `apps/api/src/recsys/engine.py` implementing `HybridRecommenderEngine`.
  - Dynamic user vector synthesis from session history in Redis.
  - MMR diversification logic with $\lambda = 0.70$.
  - `GET /api/v1/recommendations/personalized` serving hybrid results.
- **Verification Gate**:
  - Offline evaluation test achieving NDCG@10 $> 0.28$ on synthetic validation set.
  - Serving latency p95 $< 90\text{ms}$ under concurrent load.

---

### Phase 6: Next.js Frontend Integration & Telemetry Client
- **Goal**: Full e-commerce storefront with recommendation widgets and client-side telemetry tracking.
- **Inputs**: `docs/02-system-architecture.md`, `apps/web/`.
- **Deliverables**:
  - Next.js 14 App Router layout with dynamic recommendation carousels.
  - Dwell time and scroll depth tracking component (`DwellTracker.tsx`).
  - Automatic `view_item` event dispatching when product enters viewport for $> 1.0\text{s}$.
  - Guest session cookie management with seamless user onboarding.
- **Verification Gate**:
  - Browsing 3 products in the frontend visibly adapts the personalized recommendations carousel on next page refresh.

---

### Phase 7: Evaluation Suite, Admin Analytics & MLOps Retraining
- **Goal**: Automated offline evaluation, A/B testing bucketing, and merchant analytics.
- **Inputs**: `docs/06-evaluation-and-mlops-pipeline.md`.
- **Deliverables**:
  - Evaluation CLI tool computing Precision, Recall, NDCG, MRR, ILD.
  - Merchant dashboard widget displaying Recommendation CTR and attributed revenue.
  - Automated bash retraining script configured for cron execution.
- **Verification Gate**:
  - Running `python packages/ml-engine/evaluator.py` outputs full JSON metric report without runtime exceptions.

---

## 3. Concrete AI Agent Prompts

When directing subsequent AI coding agents to implement individual phases, copy and inject the exact prompts below:

### Agent Prompt for Phase 1 (Environment, DB & Auth)
```markdown
You are an autonomous senior backend engineer implementing Phase 1 of the E-Commerce UMKM Hybrid RecSys project.
Read and adhere strictly to:
- docs/01-domain-and-system-requirements.md
- docs/02-system-architecture.md
- docs/03-data-models-and-schema.md (Sections 1, 2.1, 2.2, 2.3)

Your Tasks:
1. Create `docker-compose.yml` with PostgreSQL 16 (with pgvector/pgvector:pg16 image) and Redis 7 (alpine).
2. Create `apps/api/` with FastAPI, SQLAlchemy 2.0 Async, asyncpg, and Alembic.
3. Write Alembic migration to enable extensions ('uuid-ossp', 'pg_trgm', 'vector') and create tables: `users`, `user_profiles`, `categories`, `products`, `product_variants`, `product_embeddings`.
4. Implement JWT authentication in `apps/api/src/core/auth/` (register, login, me) and cold-start onboarding in `apps/api/src/core/users/`.
5. Write pytest unit tests verifying registration, password hashing, and onboarding vector initialization.

Do not use placeholder comments or mock stubs; write production-ready asynchronous code with strict typing.
```

### Agent Prompt for Phase 3 (Telemetry & Ingestion Pipeline)
```markdown
You are an autonomous systems engineer implementing Phase 3 (Telemetry & Event Ingestion).
Read and adhere strictly to:
- docs/03-data-models-and-schema.md (Sections 2.5, 3, 4)
- docs/05-api-and-service-contracts.md (Section 2.5)

Your Tasks:
1. Implement Pydantic v2 schemas for all clickstream events in `apps/api/src/telemetry/schemas.py`.
2. Build the high-throughput ingestion endpoint `POST /api/v1/events/interaction` in `apps/api/src/telemetry/router.py`, pushing validated events directly to Redis List `queue:events:raw`.
3. Implement the ARQ worker in `apps/api/src/workers/ingestion.py` that batches up to 500 events or every 5 seconds, flushing them via PostgreSQL COPY or bulk insert into `event_logs`.
4. Implement the hourly aggregation function that calculates time-decayed implicit scores and upserts into `user_interactions`.
5. Add noise filters: ignore views with dwell_time < 3.0s, cap daily raw score contribution per user-item pair at 15.0.
```

### Agent Prompt for Phase 5 (Hybrid RecSys Engine)
```markdown
You are an autonomous machine learning engineer implementing Phase 5 (Hybrid Recommendation Engine).
Read and adhere strictly to:
- docs/04-hybrid-recommendation-engine-spec.md (All sections)
- docs/05-api-and-service-contracts.md (Section 2.1, 2.2)

Your Tasks:
1. Implement `HybridRecommenderEngine` in `apps/api/src/recsys/engine.py` conforming to the specifications in Section 6 of doc 04.
2. Integrate pgvector HNSW candidate retrieval and implicit ALS factor scoring.
3. Implement min-max normalization, dynamic switching weights based on interaction depth, and MMR diversification (lambda=0.70).
4. Implement the API endpoints in `apps/api/src/recsys/router.py`:
   - `GET /api/v1/recommendations/personalized`
   - `GET /api/v1/recommendations/similar-items`
   - `GET /api/v1/recommendations/trending`
5. Ensure hard circuit breaker falls back to category trending if latency exceeds 90ms.
```
