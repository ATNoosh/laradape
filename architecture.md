# System Architecture Overview

## 1. Vision
This project implements a **deterministic, test-driven execution engine** built around Jobs, Pipelines, and Executors, with strong emphasis on:
- Explicit contracts
- Decorator-based extensibility
- Failure isolation
- Observability and resilience patterns

The architecture is intentionally framework-agnostic and suitable for both CLI and backend execution scenarios.

---

## 2. Core Concepts

### Job
A `Job` is an immutable unit of work:
- Identified by `JobId`
- Delegates its logic to a `Pipeline`
- Has no execution logic itself

```text
Job
 ├── JobId (Value Object)
 └── Pipeline
```

### Pipeline
A `Pipeline` is a sequence of `PipelineStep`s executed in order.

Rules:
- Stops on first failure
- Returns a `PipelineResult`
- Never throws domain-level exceptions outward

```text
Pipeline
 ├── PipelineStep[]
 └── PipelineContext
```

### PipelineStep
- Atomic unit of logic
- Returns `PipelineResult`
- Must never mutate global state

---

## 3. Execution Layer

### Executor
An `Executor` is responsible for:
- Executing a `Job`
- Enforcing execution constraints (time, retries, circuit breaker, etc.)
- Producing a `JobReport`

```php
interface Executor {
    public function execute(Job $job): JobReport;
}
```

### JobReport
- Wraps `JobResult`
- Represents execution outcome (success, failure, timeout, etc.)

### JobResult
Simple immutable result object:
- `state: string`
- `message: ?string`

States are intentionally string-based to keep the domain flexible.

---

## 4. Decorator Architecture

The system heavily relies on **Decorator Pattern** to extend behavior without modifying core logic.

### Pipeline Decorators
- `TimingPipelineDecorator`
- Wrap pipelines transparently
- Must preserve **exact same PipelineResult instance**

```text
Pipeline
 → Decorator
   → ObservedPipeline
     → OriginalPipeline
```

### Executor Decorators

Implemented decorators:

#### TimingExecutorDecorator
- Measures execution time
- Notifies `ExecutorObserver`
- Must preserve `JobReport` identity

#### RetryExecutorDecorator
- Retries failed executions
- Respects max-attempts
- Never mutates successful results

#### CircuitBreakerExecutorDecorator
- Prevents cascading failures
- Supports CLOSED / OPEN states
- Cooldown-based recovery

```text
Executor
 → Retry
   → CircuitBreaker
     → Timing
       → DefaultExecutor
```

Decorator order is explicit and test-controlled.

---

## 5. Resilience Patterns Implemented

- ✅ Retry
- ✅ Circuit Breaker
- ✅ Timeboxing
- ✅ Failure short-circuiting
- ✅ Deterministic execution

All patterns are:
- Independently testable
- Composable
- Contract-driven

---

## 6. Testing Strategy

### Principles
- Contract tests > implementation tests
- No mocks for core domain
- Fake implementations preferred over mocking frameworks

### Structure
```text
tests/
 └── Core
     ├── Pipeline
     ├── Execution
     └── ValueObject
```

Each test validates:
- Contract preservation
- Instance identity where required
- Failure isolation

---

## 7. What Is Explicitly NOT Included (Yet)

- Persistence
- Async execution
- Distributed locks
- External queues
- Framework integrations

These are postponed intentionally to keep the core pure.

---

## 8. Current Status

✅ Phase 1–5 COMPLETE
- Stable execution core
- Fully green test suite
- Extensible decorator architecture

🚀 Ready for Phase 6

---

## 9. Phase 6 Direction (Preview)

Potential next steps:
- Execution Policies
- Composite Pipelines
- Execution Middleware
- Observability exports (metrics, tracing)
- Async / Queue abstraction

---

**This architecture is intentionally boring — and therefore reliable.**

