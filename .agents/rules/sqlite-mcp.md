---
trigger: model_decision
description: Triggers when interacting with SQLite databases or SQLite MCP server tools. Enforces strict read-only queries and delegates data-modifying operations (INSERT, UPDATE, DELETE, ALTER) to the user.
---

# SQLite MCP Read-Only Rule

This rule strictly governs all interactions with the `sqlite` MCP server, ensuring the agent never attempts data-modifying queries and instead delegates write operations to the user.

## 1. Read-Only Queries (Agent May Use)

The agent is permitted to use the SQLite MCP server for **read-only, inspection-only** queries, including:

- `SELECT` — querying data
- `PRAGMA` — inspecting database settings and metadata
- `EXPLAIN` / `EXPLAIN QUERY PLAN` — analyzing query execution plans
- `schema` tool — listing tables, columns, and row counts
- `table_info` tool — viewing column types, constraints, and row counts
- `list_databases` tool — discovering available SQLite database files

When using the `query` tool, the agent **MUST** include `"readonly": true` in the arguments.

## 2. Data-Modifying Queries (MUST Delegate to User)

Queries that **insert, update, delete, or alter data or schema** must NEVER be attempted through the SQLite MCP. The MCP server will reject them automatically, so attempting them wastes time and context. These include:

| Category | Examples |
|---|---|
| **Data insertion** | `INSERT INTO ...` |
| **Data updates** | `UPDATE ... SET ...` |
| **Data deletion** | `DELETE FROM ...`, `TRUNCATE ...` |
| **Schema creation** | `CREATE TABLE ...`, `CREATE INDEX ...`, `CREATE VIEW ...` |
| **Schema alteration** | `ALTER TABLE ...`, `DROP TABLE ...`, `DROP INDEX ...` |
| **Data replacement** | `REPLACE INTO ...` |

When the agent determines a query falls into this category, it must:
- Display the exact SQL statement clearly in a code block.
- Explain what the query does, why it is needed, and any expected output.
- Instruct the user to run the query manually using the `sqlite3` CLI, their database tool, or Laravel's native tooling (migrations, seeders, Eloquent, tinker).
- Wait for user confirmation before proceeding with any dependent next steps.

## 3. Ambiguous Queries

If a query's data-modification nature is unclear, the agent must **default to delegation** — present it to the user and explain the ambiguity rather than risk attempting a write operation that will be rejected.
