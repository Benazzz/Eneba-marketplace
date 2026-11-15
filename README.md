# Eneba-marketplace

A digital-key marketplace in the style of eneba.com (Eneba 2027 internship assignment).
React + TypeScript (Vite) frontend, PHP Symfony REST API, PostgreSQL. See [PLAN.md](PLAN.md) for scope, architecture and decisions.

## Live

| Part | URL | Host |
|---|---|---|
| Frontend | https://eneba-marketplace-three.vercel.app | Vercel |
| API | https://eneba-marketplace-api-yywm.onrender.com (health check: [`/api/health`](https://eneba-marketplace-api-yywm.onrender.com/api/health)) | Render (Docker, free tier) |
| Database | PostgreSQL 17 | Neon (free tier) |

**Cold start:** the API runs on Render's free tier, which sleeps after 15 minutes without traffic. The first request after that can take up to about a minute while it wakes up. After that, requests are fast.
