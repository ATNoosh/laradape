# Phase 0 – Core Rules & Guardrails

**AI Operating Constitution for the Project**

This document defines the mandatory rules the AI must follow throughout analysis, design, and implementation of the project. These rules are always active and override any ad‑hoc instruction unless explicitly updated in a later phase.

---

## 🧠 Core Principles

### 1. Business First, Technology Second
- Always analyze the **business problem** before suggesting any technology
- No technical decision is valid without a clear business justification
- If a simpler solution satisfies the requirement, complex solutions must be rejected

---

### 2. Problem → Constraints → Solution
For every decision, the AI must explicitly reason in this order:
1. What is the exact problem?
2. What are the constraints? (time, cost, team, legacy systems, data)
3. What is the simplest solution that can evolve over time?

Jumping directly to implementation is forbidden.

---

### 3. Core Must Be Framework‑Agnostic
- The system core must:
  - Be independent of Laravel, Django, Go, Rust, or any specific framework
  - Contain only pure domain logic and business rules
- Frameworks are **adapters**, never the core

---

### 4. Explicit Boundaries Between Layers
- The AI must clearly separate:
  - Domain logic
  - Application/use‑case logic
  - Infrastructure (DB, queue, framework, external services)
- Cross‑layer coupling without justification is forbidden

---

### 5. Design for Change, Not Perfection
- Assume requirements will change
- Prefer extensibility over optimization
- Avoid premature optimization

---

### 6. Decisions Must Be Reversible
- Prefer architectural choices that can be changed later with minimal cost
- If a decision is hard to reverse, the AI must explicitly warn about it

---

### 7. Incremental Value Delivery
- The system must be designed to deliver value in small, usable increments
- Large, all‑at‑once designs are discouraged

---

## 🛡️ Guardrails (Non‑Negotiable Rules)

### G1. No Over‑Engineering
- Avoid:
  - Unnecessary microservices
  - Over‑abstracted patterns
  - Complex infrastructure without proven need

---

### G2. No Hidden Assumptions
- All assumptions must be explicitly stated
- If information is missing, the AI must:
  - Declare the uncertainty
  - Propose safe default assumptions

---

### G3. No Framework Lock‑In
- Do not rely on framework‑specific magic in the core logic
- Framework features may be used only at the edges

---

### G4. No "AI Guessing"
- If the AI is unsure:
  - It must say so clearly
  - It must not fabricate requirements, scale, or constraints

---

### G5. Clear Trade‑Offs Required
- For every important decision, the AI must explain:
  - Why this option was chosen
  - What was rejected and why

---

### G6. Human‑Readable Output
- All outputs must be:
  - Clear
  - Structured
  - Understandable by a senior engineer
- Unexplained jargon is forbidden

---

## ✅ Definition of Success for Phase 0

Phase 0 is considered complete when:
- The AI consistently follows these rules
- No implementation details are introduced without architectural context
- All future phases explicitly build on top of this constitution

---

**This document is the foundation of all future design and implementation phases.**

