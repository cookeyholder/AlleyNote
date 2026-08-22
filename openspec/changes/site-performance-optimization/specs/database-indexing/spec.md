## Purpose

Provides optimized SQLite compound indexes covering multi-column filter and sort queries for the public post feed and related associations.

## ADDED Requirements

### Requirement: Public Post Feed Covering Index
The database schema SHALL include a compound index covering `status`, `deleted_at`, `is_pinned`, and `published_at` columns on the `posts` table.

#### Scenario: Querying Public Post Feed
- **WHEN** querying posts with `WHERE status = 'published' AND deleted_at IS NULL ORDER BY is_pinned DESC, published_at DESC`
- **THEN** SQLite uses the composite index `idx_posts_feed` to satisfy filter and sorting without creating temporary B-Tree sort tables
