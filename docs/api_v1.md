# API v1 (HTTP)

## POST `/api/v1/pipelines/{name}/run`

Run a pipeline by name.

### Request Body
```json
{
  "job_id": "string"
}
```

### Success Response (200)
```json
{
  "job_id": "string",
  "status": "completed",
  "message": null,
  "meta": {}
}
```

### Error Response (409)
```json
{
  "code": "job_conflict",
  "message": "string",
  "details": {
    "job_id": "string",
    "status": "failed|skipped",
    "message": "string",
    "meta": {}
  }
}
```

### Error Response (404)
```json
{
  "code": "pipeline_not_found",
  "message": "Unknown pipeline [name]",
  "details": []
}
```

### Error Response (422)
```json
{
  "code": "validation_error",
  "message": "Invalid request data",
  "details": {
    "job_id": [
      "The job id field is required."
    ]
  }
}
```
