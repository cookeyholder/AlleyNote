# Spec: Integration Testing Expansion

## ADDED Requirements

### Requirement: Full Lifecycle HTTP API Integration
The API endpoints for Posts, Auth, Settings, Attachments, and Security MUST be covered by integration tests simulating real HTTP requests against SQLite in-memory database.

#### Scenario: Post lifecycle integration
- **WHEN** an authenticated user creates, updates, and deletes a post via API
- **THEN** the database state and HTTP responses MUST reflect each operation correctly

### Requirement: RBAC Security Authorization Integration
All protected administrative routes MUST be verified by integration tests under various role permissions (guest, regular user, admin, super_admin).

#### Scenario: Non-admin user access to protected routes
- **WHEN** a regular user accesses an admin-only endpoint
- **THEN** HTTP 403 Forbidden with proper permission error code MUST be returned
