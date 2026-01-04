# Phase 6 — Integration & Boundaries

## Purpose
Phase 6 connects the **Core Execution Engine** to real-world environments (Laravel, HTTP, CLI, Queue) **without contaminating the Core**. No business logic is added here; only translation, wiring, and boundary enforcement.

> **Golden Rule**: Core must remain framework-agnostic. No Laravel, no HTTP, no Queue, no Config imports in Core.

---

## Design Principles
- **Dependency Inversion**: Outer layers depend on Core contracts, never the opposite.
- **Explicit Boundaries**: Every integration point has a clear adapter.
- **Thin Adapters**: No logic, only translation.
- **Testability**: Integration can be tested without mocking Core internals.

---

## Structure Overview
```
Core/
  Execution/
  Pipeline/
  Job/
  ValueObject/
Infrastructure/
  Laravel/
    Adapters/
    Providers/
    Console/
    Http/
```

---

## Phase 6.1 — Integration Contracts

### Goal
Define how the outside world communicates with Core using **interfaces only**.

### Typical Contracts
- `JobDispatcher`
- `PipelineFactory`
- `ExecutorFactory`

### Example
```php
interface JobDispatcher
{
    public function dispatch(Job $job): JobReport;
}
```

### Rules
- Contracts live close to Core (but not inside Laravel).
- No framework-specific types.

---

## Phase 6.2 — Laravel Adapters

### Goal
Implement Phase 6.1 contracts using Laravel features.

### Responsibilities
- Resolve dependencies from the container
- Read configuration
- Dispatch jobs synchronously or asynchronously

### Example
```php
final class LaravelJobDispatcher implements JobDispatcher
{
    public function dispatch(Job $job): JobReport
    {
        // translate + delegate
    }
}
```

### Rules
- Adapters may use Facades, Config, Queue
- Adapters contain **no domain logic**

---

## Phase 6.3 — Configuration Mapping

### Problem
Core uses objects; Laravel uses arrays and env variables.

### Solution
Introduce factories or mappers that translate config arrays into Core objects.

### Example
```php
RetryPolicyFactory::fromConfig(array $config): RetryPolicy
```

### Rules
- Mapping is one-way: Config → Core
- Core never reads config directly

---

## Phase 6.4 — CLI Entry Point

### Goal
Run pipelines via CLI for debugging, ops, and batch execution.

### Example
```bash
php artisan pipeline:run import-users
```

### Characteristics
- No HTTP
- Deterministic
- Ideal for integration testing

---

## Phase 6.5 — HTTP Entry Point

### Goal
Expose pipelines via controllers without embedding logic.

### Flow
```
Request → DTO → Job → Dispatcher → JobReport → Response
```

### Rules
- Controllers are translators only
- No retries, no circuit breakers here

---

## Phase 6.6 — Queue / Async Boundary

### Goal
Allow asynchronous execution without moving logic into workers.

### Principle
> Queue is transport, not execution logic.

### Pattern
- Job serialized
- Core executes identically sync/async

---

## Out of Scope
- New Core features
- Business rules
- Policy changes

---

## Definition of Done
- Core has zero Laravel dependencies
- All integrations use adapters
- CLI + HTTP both work
- Tests pass without framework mocking

---

## Result
After Phase 6, the system becomes:
- Framework-independent
- Replaceable (Laravel ↔ CLI ↔ Worker)
- Safe to evolve

---

**Status**: Ready to start Phase 6.1 — Integration Contracts

