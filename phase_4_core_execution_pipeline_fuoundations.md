# Phase 4 – Core Execution & Pipeline Foundations

## Goal of Phase 4
The goal of Phase 4 is to establish a **stable, test-driven core** for the data import/export pipeline engine.
This phase focuses on **deterministic execution**, **clear contracts**, and **failure isolation** — without any infrastructure or framework dependencies.

---

## Core Concepts Introduced

### 1. Pipeline
A `Pipeline` represents an ordered sequence of steps that are executed synchronously.

Responsibilities:
- Execute steps in order
- Stop execution on first failure
- Convert unexpected exceptions into controlled failures

Implemented by:
- `Core\Pipeline\DefaultPipeline`

---

### 2. PipelineStep (Contract)
Each step must implement the `PipelineStep` contract.

Rules:
- Receives a `PipelineContext`
- Must return a `PipelineResult`
- Must NOT throw unhandled exceptions

```php
interface PipelineStep
{
    public function execute(PipelineContext $context): PipelineResult;
}
