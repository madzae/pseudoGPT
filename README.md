# PseudoGPT

A high-performance, hybrid-brain (pseudo) artificial intelligence that implements a Teacher-Student architecture. This project uses a local Pseudo-Transformer logic to provide sub-10ms response times for known facts while leveraging **Gemini** for complex generative reasoning.

---

## The "Pseudo-Transformer"

This system mimics the core pipeline of a Large Language Model (LLM) but executes it on a standard PHP/MySQL stack for maximum efficiency.

### 1. Tokenization & Input Normalization
Before the "Brain" processes a query, the raw input passes through a **Pre-Processing Pipeline**:
* **Stop-word Stripping:** High-frequency, low-entropy words (e.g., *the, is, a, what*) are removed to isolate **Intent Tokens**.
* **Case-folding & Sanitization:** Normalizing inputs to ensure semantic consistency across varied user phrasings.
* **Greeting Shield:** A deterministic fast-path that intercepts social tokens (*Hi, Hello*) to prevent database bloat and unnecessary API calls.

### 2. Pseudo-Attention (Weighted Semantic Matching)
Instead of a simple database search, the engine utilizes a **Weighted Confidence Scoring** system to determine "Intent":
* **Primary Token Match:** Direct hits on the core subject grant a **+60 baseline score**.
* **Contextual Overlap:** The engine scans a secondary `context` array. Each match increases the confidence score based on the `similar_text()` percentage.
* **Confidence Gating:** If the final match score is below **75%**, the system identifies a "Knowledge Gap" and automatically delegates the query to the Teacher Model.

### 3. Knowledge Distillation (The Cache)
The system operates on a **Teacher-Student** model:
* **The Teacher (Gemini 3 Flash):** Handles complex, out-of-distribution (OOD) queries and high-reasoning tasks.
* **The Student (MySQL/JSON):** Stores the "Distilled" facts. 
Once the Teacher provides an answer, the Student **learns** the mapping. Future queries for that token-set are served from the **Local Knowledge Graph**, bypassing the cloud entirely.

