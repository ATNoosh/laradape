# API v1 (HTTP)

## POST `/api/v1/pipelines/{name}/run`

Run a pipeline by name.

### Headers
Required:
- `X-API-KEY: <key>` (if `jobs.http.api_key` is set)

Optional:
- `Idempotency-Key: <key>` (replays the first response for duplicate keys)

### Request Body
```json
{
  "job_id": "string"
}
```

### Success Response (200)
```json
{
  "data": {
    "job_id": "string",
    "status": "completed",
    "message": null,
    "meta": {}
  },
  "error": null,
  "meta": {}
}
```

### Error Response (409)
```json
{
  "data": null,
  "error": {
    "code": "job_conflict",
    "message": "string",
    "details": {
      "job_id": "string",
      "status": "failed|skipped",
      "message": "string",
      "meta": {}
    }
  },
  "meta": {}
}
```

### Error Response (404)
```json
{
  "data": null,
  "error": {
    "code": "pipeline_not_found",
    "message": "Unknown pipeline [name]",
    "details": []
  },
  "meta": {}
}
```

### Error Response (422)
```json
{
  "data": null,
  "error": {
    "code": "validation_error",
    "message": "Invalid request data",
    "details": {
      "job_id": [
        "The job id field is required."
      ]
    }
  },
  "meta": {}
}
```

### Error Response (401)
```json
{
  "data": null,
  "error": {
    "code": "unauthorized",
    "message": "Invalid API key",
    "details": []
  },
  "meta": {}
}
```

### Error Response (429)
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

### Deprecation Headers (legacy route)
If you use the legacy route `/pipelines/{name}/run`, the response includes:
- `Deprecation: true`
- `Sunset: 2026-12-31`
- `Link: </api/v1/pipelines/{name}/run>; rel="successor-version"`

### Rate Limit
Controlled by `jobs.http.rate_limit` (format: `limit,minutes`).

### Idempotency
When `Idempotency-Key` is provided, the first response is cached for
`jobs.http.idempotency_ttl_seconds` and replayed for duplicate keys.
