# Execution Plans

Working directory for agent execution plans.

- `active/` — plans currently being executed (create on demand)
- `completed/` — finished plans kept for reference (create on demand)

Long-lived design/modernization plans live in `docs/plans/`. Execution plans here are task-scoped, disposable checklists an agent follows for a single change; keep one file per task, move it to `completed/` when done.
