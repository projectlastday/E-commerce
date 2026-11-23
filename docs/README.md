# Technical Research & Architecture Specification Suite
**Project Title:** Analisis dan Perancangan Sistem Informasi E-Commerce UMKM Berbasis Personalized Recommendation Menggunakan Hybrid Recommendation System  
**System Designation:** UMKM-RecSys-Ecom (Modular Monolith)  
**Standard Version:** 1.0.0 (Production / AI-Agent Ready)  
**Primary Language:** Indonesian & English Technical Specification  

---

## 1. Executive Summary & Architectural Manifesto

This documentation suite provides a complete, mathematically rigorous, and production-ready technical blueprint for building a high-performance E-Commerce platform specifically engineered for Indonesian Small-and-Medium Enterprises (UMKM). 

Unlike hyper-scale marketplaces that rely on massive distributed compute clusters (Spark/Ray) and deep interaction histories, UMKM storefronts face **severe data sparsity ($< 0.05\%$ interaction density)**, **extreme cold-start rates ($> 70\%$ first-time/anonymous visitors)**, and **tight computational budgets (single-node commodity VPS)**.

To solve this, this architecture pairs a **Decoupled Modular Monolith** (Next.js 14 + FastAPI + PostgreSQL 16 + Redis 7) with a **Two-Stage Hybrid Recommendation Engine**:
1. **Candidate Generation**: Mixed parallel retrieval via pgvector HNSW semantic vector search ($K=50$), Implicit Alternating Least Squares (iALS) factor dot products ($K=50$), and time-decayed category trending ($K=20$).
2. **Scoring & Ranking**: Adaptive switching-weighted blending dynamically modulating Content-Based Filtering (CBF) and Collaborative Filtering (CF) weights based on the user's historical interaction depth.
3. **Diversification & Post-Processing**: Maximal Marginal Relevance (MMR, $\lambda=0.70$) to prevent filter bubbles, alongside real-time inventory and fatigue constraints.

---

## 2. Document Map & Reading Order

All documents in this directory are structured for direct consumption by autonomous AI coding agents, software architects, and machine learning engineers. There are zero ambiguous placeholders or "TODOs".

```
docs/
├── 01-domain-and-system-requirements.md   # Functional & Non-Functional Requirements, UMKM Constraints, Telemetry Taxonomy
├── 02-system-architecture.md              # Modular Monolith Topology, Tech Stack Selection, Component Data Flows, Latency Budget
├── 03-data-models-and-schema.md           # PostgreSQL DDL, pgvector HNSW Index Spec, JSON Schemas, SQL Aggregation Functions
├── 04-hybrid-recommendation-engine-spec.md# Burke Taxonomy, iALS Math, LightFM Math, CBF Math, MMR Diversity, Python Implementation
├── 05-api-and-service-contracts.md        # REST & OpenAPI 3.1 Contracts, Pydantic v2 Models, Error Formats (RFC 7807)
├── 06-evaluation-and-mlops-pipeline.md    # Offline Ranking Metrics (NDCG, Recall, ILD), Online KPIs, A/B Protocol, Retraining Lifecycle
├── 07-ai-implementation-blueprint.md      # Full Codebase Tree, 7-Phase Execution Plan, Copy-Paste AI Agent Prompts
└── README.md                              # (This document) Master Index & Architectural Synthesis
```

### Detailed Document Overview

| Document ID & File | Scope & Primary Focus | Primary Academic & Industry Citations | Target Development Phase |
| :--- | :--- | :--- | :---: |
| [**01. Requirements**](file:///Users/brian/Files/Web%20Project/e_com/docs/01-domain-and-system-requirements.md) | Domain challenges, FR/NFR specifications, explicit vs implicit signal weights, dwell time bounds, UU PDP compliance. | • Hu, Koren, & Volinsky (2008)<br>• Jannach et al. (2018)<br>• UU PDP No. 27/2022 | Phase 1 & 2 |
| [**02. Architecture**](file:///Users/brian/Files/Web%20Project/e_com/docs/02-system-architecture.md) | Decoupled Modular Monolith, FastAPI + Next.js stack, Redis buffering, zero-copy ML data access, latency budgets. | • Martin Fowler (MonolithFirst)<br>• FastAPI & AsyncIO Internal Specs | Phase 1 |
| [**03. Data Models**](file:///Users/brian/Files/Web%20Project/e_com/docs/03-data-models-and-schema.md) | Production PostgreSQL DDL, pgvector HNSW indexing, partitioned event logs, clickstream JSON draft-07 schemas. | • pgvector Documentation (v0.7.0)<br>• Malkov & Yashunin (2018) HNSW | Phase 1 & 3 |
| [**04. RecSys Engine**](file:///Users/brian/Files/Web%20Project/e_com/docs/04-hybrid-recommendation-engine-spec.md) | Robin Burke taxonomy, iALS closed-form updates, sentence-transformers, switching weights, MMR diversity, Python engine code. | • Burke (2002) Hybrid Taxonomy<br>• Hu et al. (2008) iALS<br>• Carbonell & Goldstein (1998) MMR | Phase 4 & 5 |
| [**05. API Contracts**](file:///Users/brian/Files/Web%20Project/e_com/docs/05-api-and-service-contracts.md) | REST endpoints for personalized feed, similar items, trending, telemetry ingestion, Pydantic v2 schemas. | • OpenAPI 3.1.0 Specification<br>• RFC 7807 Problem Details | Phase 2, 3, 5 |
| [**06. Evaluation & MLOps**](file:///Users/brian/Files/Web%20Project/e_com/docs/06-evaluation-and-mlops-pipeline.md) | NDCG@K, MAP@K, MRR, Intra-List Diversity (ILD), online CTR/CVR, deterministic A/B testing, model validation gates. | • Herlocker et al. (2004)<br>• Ziegler et al. (2005) ILD<br>• Kohavi et al. (Controlled Experiments) | Phase 7 |
| [**07. AI Blueprint**](file:///Users/brian/Files/Web%20Project/e_com/docs/07-ai-implementation-blueprint.md) | Full directory tree, 7-phase execution roadmap, verification milestones, and ready-to-run prompt templates for AI agents. | • Agile AI Pair Programming Patterns | All Phases |

---

## 3. Scientific Foundations & Theoretical Summary

### 3.1. Hybrid Recommender Taxonomy (Robin Burke, 2002)
The system synthesizes Burke's hybridization strategies into an optimized two-stage pipeline:
- **Mixed Retrieval**: Concurrently pools candidate items from independent paradigms (Collaborative Latent Vectors, Semantic Vector Embeddings, and Frequency Trends).
- **Cascade Refinement**: Large unranked candidate spaces ($\approx 10^3$ items) are rapidly narrowed down to $\approx 100$ items before applying computationally intensive diversity and business logic.
- **Switching Weights**: Smoothly shifts the ranking objective from pure semantic alignment for new visitors to deep collaborative affinity as interaction histories accumulate.

### 3.2. Implicit Feedback Collaborative Filtering (Hu, Koren, Volinsky, 2008)
Because transactional purchases are sparse, all passive actions (view, dwell, search click, cart add) contribute to the preference matrix with monotonic confidence:
$$c_{ui} = 1 + \alpha r_{ui}, \quad \text{where } \alpha = 40$$
Alternating Least Squares minimizes the confidence-weighted squared loss in $\mathcal{O}(f^2 |R| + f^3 M)$, solvable in $< 3\text{ minutes}$ on a 4-core standard CPU.

### 3.3. Semantic Content Representation & HNSW Indexing
Products are vectorized into 384-dimensional dense vectors using `paraphrase-multilingual-MiniLM-L12-v2`. PostgreSQL's `pgvector` extension executes approximate nearest neighbor (ANN) search via Hierarchical Navigable Small World (HNSW) graphs, yielding sub-5ms cosine retrieval at $> 98\%$ recall.

### 3.4. Beyond-Accuracy Diversification (Carbonell & Goldstein, 1998)
To prevent monotonic category clustering, candidate outputs are re-ranked using Maximal Marginal Relevance (MMR) with trade-off parameter $\lambda = 0.70$, balancing relevance against inter-item pairwise vector distance.

---

## 4. Quickstart Guide for Autonomous AI Coding Agents

When beginning implementation:
1. **Read Sequence**:
   - Begin with `docs/07-ai-implementation-blueprint.md` to understand directory layouts and the phase roadmap.
   - Reference `docs/03-data-models-and-schema.md` for exact table definitions and migrations.
   - Reference `docs/05-api-and-service-contracts.md` for request/response schemas.
2. **Phase 1 Initiation**:
   - Run the Phase 1 Prompt from `docs/07-ai-implementation-blueprint.md` (Section 3).
   - Set up `docker-compose.yml` with `pgvector/pgvector:pg16` and `redis:7-alpine`.
   - Initialize the FastAPI project structure and run the database migrations.
3. **Zero-Mock Policy**:
   - All database models, vector queries, and API endpoints must be fully implemented with real types, production error handling, and test coverage.
