# 06. Evaluation and MLOps Pipeline Specification
**Project:** Sistem Informasi E-Commerce UMKM Berbasis Personalized Hybrid Recommendation System  
**Document ID:** SPEC-DOC-006  
**Target Audience:** Autonomous AI Coding Agents, ML Engineers, Data Scientists  
**Status:** Approved for Implementation  

---

## 1. Offline Evaluation Metrics & Mathematical Formulations

To assess recommendation models prior to production deployment, the pipeline uses a strict temporal train-test split: the final 7 days of interactions serve as the evaluation ground-truth set $\mathcal{T}_{\text{eval}}$, and all preceding data forms the training set $\mathcal{T}_{\text{train}}$.

### 1.1. Top-K Ranking Accuracy Metrics

#### Precision@K and Recall@K
Let $\text{Rec}_{u, K}$ be the ordered set of top-$K$ recommendations for user $u$, and $\text{Rel}_u$ be the set of ground-truth positive items interacted with by user $u$ in $\mathcal{T}_{\text{eval}}$:

$$\text{Precision}@K(u) = \frac{|\text{Rel}_u \cap \text{Rec}_{u, K}|}{K}$$

$$\text{Recall}@K(u) = \frac{|\text{Rel}_u \cap \text{Rec}_{u, K}|}{|\text{Rel}_u|}$$

$$\text{Precision}@K = \frac{1}{|U|} \sum_{u \in U} \text{Precision}@K(u), \quad \text{Recall}@K = \frac{1}{|U|} \sum_{u \in U} \text{Recall}@K(u)$$

#### Normalized Discounted Cumulative Gain (NDCG@K)
Evaluates ranking quality by penalizing relevant items placed lower in the recommendation list:

$$\text{DCG}@K(u) = \sum_{i=1}^K \frac{2^{\text{rel}_{u, i}} - 1}{\log_2(i + 1)}$$

$$\text{IDCG}@K(u) = \sum_{i=1}^{\min(K, |\text{Rel}_u|)} \frac{2^1 - 1}{\log_2(i + 1)}$$

$$\text{NDCG}@K(u) = \frac{\text{DCG}@K(u)}{\text{IDCG}@K(u)}, \quad \text{NDCG}@K = \frac{1}{|U|} \sum_{u \in U} \text{NDCG}@K(u)$$

Where $\text{rel}_{u, i} = 1$ if the $i$-th recommended item is in $\text{Rel}_u$, else $0$.

#### Mean Reciprocal Rank (MRR)
Measures the reciprocal position of the first relevant item in the recommendation list:

$$\text{MRR} = \frac{1}{|U|} \sum_{u \in U} \frac{1}{\text{rank}_u^*}$$

Where $\text{rank}_u^*$ is the 1-based index of the first hit; if no hit occurs in the top-$K$, $\frac{1}{\text{rank}_u^*} = 0$.

---

### 1.2. Beyond-Accuracy Diversity & Exploration Metrics

Optimizing solely for NDCG creates catastrophic filter bubbles where an UMKM customer is repeatedly shown products from a single narrow category.

#### Intra-List Diversity (ILD)
Calculates the average cosine distance among all recommended pairs within the list:

$$\text{ILD}(R) = \frac{2}{|R|(|R| - 1)} \sum_{i \in R} \sum_{j \in R, j > i} d_{\text{cosine}}(\mathbf{e}_i, \mathbf{e}_j)$$

Where $d_{\text{cosine}}(\mathbf{e}_i, \mathbf{e}_j) = 1 - \frac{\mathbf{e}_i \cdot \mathbf{e}_j}{\|\mathbf{e}_i\|_2 \|\mathbf{e}_j\|_2}$. Higher ILD indicates a healthier catalog discovery experience.

#### Catalog Coverage
Measures the percentage of the total active inventory exposed across all users:

$$\text{Coverage}@K = \frac{\left| \bigcup_{u \in U} \text{Rec}_{u, K} \right|}{|\mathcal{I}_{\text{active}}|}$$

Target: $\text{Coverage}@10 \ge 35\%$ for UMKM catalogs (preventing the "long tail" inventory from being hidden).

#### Novelty (Self-Information)
Quantifies whether the system recommends unexpected items rather than ubiquitous bestsellers:

$$\text{Novelty}(R) = \frac{1}{|R|} \sum_{i \in R} -\log_2 P(i), \quad \text{where } P(i) = \frac{\text{Interactions}(i)}{\sum_{j \in \mathcal{I}} \text{Interactions}(j)}$$

---

## 2. Python Metric Evaluation Suite

This script is located at `scripts/evaluate_offline.py` and runs automatically during model retraining:

```python
"""
Offline RecSys Metric Evaluation Suite
Computes Precision@K, Recall@K, NDCG@K, MRR, and Intra-List Diversity (ILD).
"""

from typing import List, Dict, Set
import numpy as np


class RecSysEvaluator:
    def __init__(self, item_embeddings: Dict[str, np.ndarray]):
        self.item_embeddings = item_embeddings

    def evaluate_user(
        self,
        recommended_ids: List[str],
        ground_truth_ids: Set[str],
        k: int = 10,
    ) -> Dict[str, float]:
        recs = recommended_ids[:k]
        if not ground_truth_ids:
            return {"precision": 0.0, "recall": 0.0, "ndcg": 0.0, "mrr": 0.0, "ild": 0.0}

        # 1. Precision & Recall
        hits = [1 if r in ground_truth_ids else 0 for r in recs]
        num_hits = sum(hits)
        precision = num_hits / float(k)
        recall = num_hits / float(len(ground_truth_ids))

        # 2. MRR
        mrr = 0.0
        for idx, hit in enumerate(hits):
            if hit == 1:
                mrr = 1.0 / (idx + 1)
                break

        # 3. NDCG@K
        dcg = sum((2**h - 1) / np.log2(idx + 2) for idx, h in enumerate(hits))
        ideal_hits = [1] * min(k, len(ground_truth_ids))
        idcg = sum((2**1 - 1) / np.log2(idx + 2) for idx in range(len(ideal_hits)))
        ndcg = (dcg / idcg) if idcg > 0 else 0.0

        # 4. Intra-List Diversity (ILD)
        ild = 0.0
        valid_vectors = [
            self.item_embeddings[r]
            for r in recs
            if r in self.item_embeddings
        ]
        num_vecs = len(valid_vectors)
        if num_vecs > 1:
            dist_sum = 0.0
            pair_count = 0
            for i in range(num_vecs):
                for j in range(i + 1, num_vecs):
                    # Cosine distance = 1 - cosine_sim
                    u, v = valid_vectors[i], valid_vectors[j]
                    norm_prod = np.linalg.norm(u) * np.linalg.norm(v)
                    cos_sim = np.dot(u, v) / (norm_prod + 1e-9)
                    dist_sum += 1.0 - cos_sim
                    pair_count += 1
            ild = dist_sum / float(pair_count) if pair_count > 0 else 0.0

        return {
            "precision": precision,
            "recall": recall,
            "ndcg": ndcg,
            "mrr": mrr,
            "ild": ild,
        }

    def evaluate_dataset(
        self,
        all_recommendations: Dict[str, List[str]],
        all_ground_truth: Dict[str, Set[str]],
        k: int = 10,
    ) -> Dict[str, float]:
        aggregated: Dict[str, List[float]] = {
            "precision": [], "recall": [], "ndcg": [], "mrr": [], "ild": []
        }
        for user_id, ground_truth in all_ground_truth.items():
            recs = all_recommendations.get(user_id, [])
            metrics = self.evaluate_user(recs, ground_truth, k=k)
            for m, val in metrics.items():
                aggregated[m].append(val)

        return {f"mean_{k}": float(np.mean(v)) for k, v in aggregated.items()}
```

---

## 3. Online Business KPIs & A/B Testing Framework

### 3.1. Online Telemetry KPIs
1. **Click-Through Rate (CTR)**:
   $$\text{CTR}_{\text{rec}} = \frac{\sum \text{Clicks on Rec Widget}}{\sum \text{Impressions of Rec Widget}}$$
2. **Conversion Rate (CVR)**:
   $$\text{CVR}_{\text{rec}} = \frac{\sum \text{Purchases with Attribution to Rec Widget}}{\sum \text{Clicks on Rec Widget}}$$
3. **Recommendation Revenue Share (RRS)**:
   $$\text{RRS} = \frac{\text{GMV of Rec-Attributed Orders}}{\text{Total Platform GMV}}$$
4. **Attribution Window**: A purchase is credited to a recommendation widget if the item was added to the cart within **24 hours** of clicking a recommendation card.

### 3.2. Deterministic Client-Side A/B Testing Protocol
Users are assigned to experimental variants deterministically via MD5 hash of their persistent identifier:

```python
import hashlib

def assign_ab_variant(user_or_session_id: str, experiment_salt: str = "exp_recs_2026_q4") -> str:
    """
    Deterministic bucket assignment:
    50% Control (Popularity + Category Trend)
    50% Treatment (Two-Stage Hybrid Engine)
    """
    hash_input = f"{user_or_session_id}:{experiment_salt}".encode("utf-8")
    hash_val = int(hashlib.md5(hash_input).hexdigest(), 16)
    bucket = hash_val % 100
    return "TREATMENT_HYBRID" if bucket < 50 else "CONTROL_POPULARITY"
```

---

## 4. Retraining Cadence & Production Model Lifecycle

```mermaid
flowchart TD
    T0[Hourly Fast Ingestion] -->|Trigger| J1[Aggregate event_logs into user_interactions]
    T1[Daily Retraining Cron: 02:00 UTC] -->|Trigger| J2[Load user_interactions & product_embeddings]
    J2 --> J3[Split Data: Train vs 7-Day Test Split]
    J3 --> J4[Fit Implicit ALS: factors=64, alpha=40, iters=15]
    J4 --> J5[Compute Offline Metrics: NDCG@10, Recall@10, ILD]
    J5 --> CHECK{NDCG@10 >= Baseline - 0.02?}
    CHECK -- NO --> REJECT[Abort Promotion, Alert to Logs]
    CHECK -- YES --> PROMOTE[Save Artifact to /models/als_latest.pkl]
    PROMOTE --> SYNC[Send Hot-Reload Signal to FastAPI Serving Processes]
    SYNC --> DONE([Model In Service])
```

### 4.1. Model Retraining Automation Script
Retraining is implemented in `packages/ml-engine/trainer.py`:
- Exports interaction matrix into SciPy CSR format.
- Uses OpenMP parallel threads (`OPENBLAS_NUM_THREADS=4`) to complete matrix factorization in under 180 seconds.
- Performs atomic file replacement of model weights so API processes can reload weights without downtime.

---

## 5. Resilience, Fault Tolerance & Circuit Breaking

To guarantee the $< 100\text{ms}$ latency budget, the serving layer implements a **Three-Tier Fallback Hierarchy**:

```
               [Incoming Recommendation Request]
                              │
                              ▼
                   ┌───────────────────────┐
                   │ Tier 1: Realtime      │
                   │ Hybrid Engine         │
                   └──────────┬────────────┘
                              │
               Timeout > 80ms or Exception?
                              │
               ┌──────────────┴──────────────┐
              YES                            NO
               │                              │
               ▼                              ▼
    ┌───────────────────────┐       Return Hybrid Top-10
    │ Tier 2: Redis         │       (Normal Serving Path)
    │ Pre-computed Cache    │
    └──────────┬────────────┘
               │
          Cache Miss?
               │
        ┌──────┴──────┐
       YES            NO
        │              │
        ▼              ▼
┌──────────────────┐  Return Cached IDs
│ Tier 3: Static   │
│ Category Top-N   │
│ Database Query   │
└──────────────────┘
```

### 5.3. Circuit Breaker Parameters
- **Sliding Window**: 100 requests.
- **Trip Condition**: $> 5\%$ of requests exceeding $90\text{ms}$ or throwing unhandled exceptions.
- **Action**: Circuit trips to `OPEN` for 30 seconds; all requests immediately bypass ML candidate generation and execute Tier 3 static SQL query ($< 4\text{ms}$ latency), protecting CPU availability.
