# Phase 2 – AI Instructions: Solution Scope & MVP Definition

**Prerequisites:**
- Phase 0 – Core Rules & Guardrails are active
- Phase 1 – Problem & Market Definition passed with **GO** for:
  **Option 1: Laravel‑First Data Job Engine (Not Generic ETL)**

This phase defines **what will be built**, **what will NOT be built**, and **where the hard boundaries are**.

---

## 🎯 Objective of Phase 2

- Convert the validated problem into a **concrete, minimal, buildable solution**
- Prevent scope creep
- Lock a realistic MVP that can be implemented by one senior engineer

No code yet. No frameworks yet.

---

## 🧠 Mandatory AI Mindset

The AI must behave as:
- A ruthless scope controller
- An architect who hates bloated MVPs
- A tech lead optimizing for learning, not ego

If something is not required to validate the product, it must be excluded.

---

## 1️⃣ Product Definition Rules

### 1.1 Product Statement (Strict)
The AI must define the product in **one sentence** using this template:

> "This product allows **[specific user]** to **[specific data job]** in **[specific environment]**, with **[key constraint solved]**."

If the sentence becomes vague → rewrite.

---

### 1.2 Core User Journey (Mandatory)
The AI must define:
- Start condition
- Happy path
- Failure path

No edge cases yet.

---

## 2️⃣ MVP Scope Definition

### 2.1 MUST Have (Non‑Negotiable)
Features without which the product is useless.

Examples:
- Define a data job
- Execute a job
- Track progress
- Handle failure safely

---

### 2.2 SHOULD Have (Phase 2.5 or later)
Important but not MVP‑critical.

Examples:
- UI polish
- Advanced retry strategies
- Multiple storage backends

---

### 2.3 MUST NOT Have (Explicitly Forbidden)
Features that will **not** be built in MVP.

Examples:
- Generic ETL connectors
- Visual drag‑and‑drop builders
- Multi‑tenant SaaS

This list is mandatory.

---

## 3️⃣ Core Concept Modeling

The AI must define **concepts**, not implementation.

Mandatory concepts:
- Job
- Step
- Pipeline
- Execution
- State
- Result

No framework classes allowed here.

---

## 4️⃣ Execution Model (Abstract)

The AI must describe:
- How jobs run
- How steps transition
- How failures are handled

Without mentioning queues, Laravel, Redis, or workers.

---

## 5️⃣ Constraints & Non‑Goals

The AI must explicitly state:
- What the system is NOT trying to solve
- Which user types are excluded

Ambiguity is not allowed.

---

## 6️⃣ Output Format (Strict)

Every Phase 2 output must follow this structure:

1. Product One‑Liner
2. Target User
3. Core User Journey
4. MVP Scope (Must / Should / Must Not)
5. Core Concepts
6. Execution Model (Abstract)
7. Explicit Non‑Goals

Deviation is forbidden.

---

## ✅ Definition of Success for Phase 2

Phase 2 is complete when:
- MVP scope is painfully small but useful
- No feature feels "nice‑to‑have"
- The system can be explained to a senior engineer in 5 minutes

Only after this phase may Phase 3 (Architecture & Boundaries) begin.

---

**If scope inflation happens here, the product will fail later.**

