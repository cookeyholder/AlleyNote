## Purpose

Optimizes frontend initial page weight through lazy loading heavy dependencies and enabling HTTP transfer compression on Nginx.

## ADDED Requirements

### Requirement: On-Demand Lazy Loading of Admin Libraries
The frontend application SHALL NOT load CKEditor 5 and Chart.js synchronously on initial page load in `index.html`. These libraries SHALL be dynamically loaded on-demand when navigating to pages that require them.

#### Scenario: Public Home Page Initial Load
- **WHEN** an unauthenticated visitor loads the home page
- **THEN** neither CKEditor nor Chart.js scripts are loaded in the DOM

#### Scenario: Admin Editor or Statistics Page Load
- **WHEN** a user navigates to the post editor or statistics page
- **THEN** the corresponding library is loaded dynamically before rendering the editor or chart

### Requirement: Nginx Web Server Compression
The Nginx web server configuration SHALL enable Gzip compression for JSON API responses and static assets (CSS, JS, SVG, HTML).

#### Scenario: Client Requesting Compressed Asset
- **WHEN** a client sends a request with `Accept-Encoding: gzip`
- **THEN** the server returns compressed content with `Content-Encoding: gzip`
