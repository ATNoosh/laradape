# Usage Guide

This guide explains how to run pipelines via HTTP and CLI.

## HTTP (API v1)
Endpoint:
- `POST /api/v1/pipelines/{name}/run`

Headers:
- `X-API-KEY: <key>` (required if `jobs.http.api_key` is set)
- `Idempotency-Key: <key>` (optional)

Body:
```json
{
  "job_id": "my-job-1"
}
```

Success response:
```json
{
  "data": {
    "job_id": "my-job-1",
    "status": "completed",
    "message": null,
    "meta": {}
  },
  "error": null,
  "meta": {}
}
```

## HTTP (Legacy, Deprecated)
Endpoint:
- `POST /pipelines/{name}/run`

Deprecation headers:
- `Deprecation: true`
- `Sunset: 2026-12-31`
- `Link: </api/v1/pipelines/{name}/run>; rel="successor-version"`

## Rate Limiting
Controlled by:
- `jobs.http.rate_limit` (format: `limit,minutes`)

429 response example:
```json
{
  "data": null,
  "error": {
    "code": "rate_limited",
    "message": "Too many requests",
    "details": []
  },
  "meta": {}
}
```

## Idempotency
When `Idempotency-Key` is provided, the first response is cached for
`jobs.http.idempotency_ttl_seconds` seconds and replayed for duplicate keys.

## CLI
Run a job by id:
```bash
php artisan job:run {jobId}
```

## Postman
Import:
- `docs/postman_collection.json`

Set variables:
- `baseUrl` (default: `http://localhost:8000`)
- `apiKey`

## OpenAPI / Swagger
Spec file:
- `docs/openapi.yaml`
