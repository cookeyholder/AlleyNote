# Spec: CI Pipeline Acceleration

## ADDED Requirements

### Requirement: Fast Coverage Engine
The CI pipeline MUST use PCOV for PHPUnit code coverage generation instead of Xdebug to minimize test execution overhead.

### Requirement: Concurrency Control
All GitHub Actions workflows MUST specify concurrency groups with `cancel-in-progress: true` to prevent redundant runner execution on rapid commit pushes.

### Requirement: Path Filtering
Workflows MUST ignore non-code documentation changes (e.g. `**.md`, `docs/**`) to avoid unnecessary runner allocation.

### Requirement: Parallel E2E Workers
The Playwright E2E configuration MUST utilize 2 parallel workers in CI environments to accelerate end-to-end testing throughput.
