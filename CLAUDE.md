# CLAUDE.md

## Project summary

Eneba 2027 internship assignment (7-day deadline): a digital-key marketplace web app that looks and feels like eneba.com.
Specs are intentionally vague. We choose the scope and must be able to justify it, and a few features done well beat many minimal ones.
The author must be able to explain every line of code.

- Assignment: summarized in this file (the original PDF isn't in the repo)
- Design reference (eneba.com screenshots, desktop and mobile): `docs/design-reference/`
- Detailed plan: `PLAN.md` (scope, architecture, data model, API, build order, decisions with reasons). Read it before starting work.

## Required parts

1. **Store**: a catalog of all products, with filters (e.g. platform, region, price), sorting, and search by name.
2. **Homepage**: built from blocks (banner, best sellers, deals, …). Each block gets its content from its own backend service or data source. The frontend only displays what it receives.
3. **Search**: available from anywhere in the app. It should handle typos and partial names.
4. **Product page**: product details plus offers from several sellers, so the buyer can compare them.
5. **Checkout**: cart → pay (simulated, or a provider's test mode, with no real money) → receive the digital key.
6. **Auth**: register and sign in. Checkout and order history require sign-in.
7. **Eneba styling**: colors, typography, spacing and components follow the design reference on every screen.

Purchase flow: Homepage / Search → Product page → Cart → Checkout (sign-in required) → Payment → Order and key.

## Stack

Monorepo: `frontend/` and `backend/`. Details and reasons are in `PLAN.md`.

- **Frontend:** React + TypeScript (Vite), hosted on Vercel
- **Backend:** PHP + Symfony REST API, hosted on Render (Docker)
- **Database:** PostgreSQL on Neon (pg_trgm for search)

## Deliverables

- GitHub repository link
- Public URL of the deployed app
- Full AI chat logs (prompts and responses), in a `.md` file or as a link

## Working rules

- **Plan before implementing.** No code until the relevant part of `PLAN.md` is agreed.
- **Ask before major decisions** (architecture, data model, hosting, scope) **and before adding any new library or package.**
- **Explain PHP and Symfony concepts.** The author is new to PHP but knows C#/.NET, so compare to .NET where it helps (e.g. Symfony services/DI ↔ ASP.NET Core DI, Doctrine ↔ EF Core, controllers ↔ ASP.NET controllers, Composer ↔ NuGet).
- **Keep changes small.** Each change should make sense as its own commit.
- **Never commit or push without asking first.**
- **No secrets in code.** Use environment variables and git-ignored `.env.local` files, and commit only example files.
- **No hardcoded data in the frontend.** Products, homepage blocks, categories, offers, etc. all come from the backend API.
