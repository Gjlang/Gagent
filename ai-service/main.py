from fastapi import FastAPI

app = FastAPI(
    title="GAgent AI Service",
    description="Initial FastAPI service for GAgent UX friction detection.",
    version="0.1.0"
)

@app.get("/")
def root():
    return {
        "message": "GAgent AI Service is running",
        "phase": "Phase 1",
        "status": "ok"
    }

@app.get("/health")
def health_check():
    return {
        "service": "gagent-ai-service",
        "status": "healthy"
    }