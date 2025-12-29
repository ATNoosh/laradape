# Phase 3 – Architecture & Boundaries

**Prerequisites**
- Phase 0 – Core Rules & Guardrails (active)
- Phase 1 – Problem & Market Definition (GO)
- Phase 2 – Solution Scope & MVP Definition (locked)

This phase defines **how the system is structured**, **where boundaries exist**, and **what depends on what**.

No feature expansion is allowed in this phase.

---

## 🎯 Objective of Phase 3

- Define a clean, defensible architecture
- Make Laravel an implementation detail
- Ensure the Core is testable without any framework
- Prevent future architectural lock-in

---

## 🧠 Architectural Principle (Non-Negotiable)

> **Dependencies always point inward.**

Infrastructure depends on Application.
Application depends on Core.
Core depends on nothing.

---

## 🧱 High-Level Architecture

```
┌──────────────────────────┐
│        Interfaces        │  (CLI / HTTP / UI)
└────────────┬─────────────┘
             ↓
┌──────────────────────────┐
│     Application Layer    │  (Use Cases)
└────────────┬─────────────┘
             ↓
┌──────────────────────────┐
│        Core Layer        │  (Domain Logic)
└────────────┬─────────────┘
             ↓
┌──────────────────────────┐
│   Infrastructure Layer   │  (Laravel, DB, Queue)
└──────────────────────────┘
```

---

## 1️⃣ Core Layer (Pure PHP)

### Responsibilities
- Define domain concepts
- Enforce execution rules
- Control state transitions

### Allowed
- Plain PHP classes
- Interfaces
- Value objects

### Forbidden
- Laravel helpers
- Facades
- DB access
- Queues
- Events
- Exceptions as control flow

### Core Modules

```
/Core
  /Job
    Job.php
  /Step
    Step.php
  /Pipeline
    Pipeline.php
  /Execution
    Execution.php
    ExecutionState.php
    ExecutionResult.php
```

### Key Rules
- Steps are deterministic
- Failures are explicit
- State transitions are controlled

---

## 2️⃣ Application Layer (Use Cases)

### Responsibilities
- Orchestrate Core objects
- Coordinate executions
- Decide *when* and *how* jobs run

### Allowed
- Use-case classes
- Ports (interfaces) for persistence & execution

### Forbidden
- Business rules
- Laravel-specific code

### Example Use Cases
- StartJobExecution
- ResumeJobExecution
- AbortExecution

---

## 3️⃣ Infrastructure Layer (Adapters)

### Responsibilities
- Implement ports defined in Application layer
- Integrate with Laravel

### Allowed
- Laravel Queue
- Horizon
- Eloquent
- Redis

### Forbidden
- Business decisions
- State logic

### Adapter Examples

```
/Infrastructure
  /Laravel
    /Queue
    /Persistence
    /CLI
    /HTTP
```

Infrastructure is replaceable by design.

---

## 4️⃣ Execution Boundary (Critical)

### Rule
The Core **never knows**:
- If execution is async or sync
- If execution uses queue or CLI
- If execution is retried

### Executor Port

```
interface JobExecutor {
    public function execute(Execution $execution): ExecutionResult;
}
```

LaravelQueueExecutor is just one implementation.

---

## 5️⃣ Persistence Boundary

### Rule
The Core does not persist anything.

### Persistence Port

```
interface ExecutionRepository {
    public function save(Execution $execution): void;
    public function load(string $executionId): Execution;
}
```

DB choice is irrelevant to the Core.

---

## 6️⃣ Error & Failure Strategy

### Rules
- No silent failures
- No retry by default
- Failures must be inspectable

### Failure Types
- StepFailure
- ExecutionFailure

Exceptions are not part of business flow.

---

## 7️⃣ Testing Strategy (Defined Now)

### Core Tests
- Pure unit tests
- No mocks of Laravel

### Application Tests
- Use fake adapters

### Infrastructure Tests
- Minimal
- Focus on wiring

---

## 🚫 Architectural Anti-Patterns (Explicit)

- Fat Jobs
- Logic inside Laravel Jobs
- Core depending on Queue
- DB-driven state machines
- "Smart" retries

---

## ✅ Phase 3 Completion Criteria

Phase 3 is complete when:
- Core can run in plain PHP
- Laravel can be removed without breaking logic
- Every dependency direction is explicit

---

**Only after this phase may Phase 4 (Core Implementation) begin.**
