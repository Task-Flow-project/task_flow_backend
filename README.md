# TaskFlow — Backend API

TaskFlow is a real-time collaborative Kanban task board (Trello/Notion-style) built as a full production-style SaaS. This repository is the **backend**: a standalone Laravel 13 REST API.

Teams create **workspaces**, invite members with roles, organize work on **boards** made of **columns** and **cards**, collaborate live, and get gamified with streaks and achievements. Free and Pro plans are enforced server-side and billed through Stripe.

## Features

- **Auth** — email/password with OTP email verification, Google OAuth, Sanctum token + httpOnly cookie hybrid auth
- **Workspaces & teams** — roles (owner/admin/member), email invitations, ownership transfer
- **Kanban boards** — boards → columns → cards, drag-and-drop ordering via a float gap-strategy `position`
- **Card details** — labels, assignees, due dates, checklists, comments with `@mention`, file attachments
- **Real-time collaboration** — board mutations and notifications pushed live over WebSockets (Laravel Reverb)
- **Gamification** — per-user activity log, daily streaks (with grace-day rule), unlockable achievements
- **Notifications** — assignment, mention, invite, achievement unlock, due-soon reminders (scheduled)
- **Billing** — Free/Pro plans with server-enforced limits (workspaces, boards, members, attachment storage, activity history), real Stripe subscriptions via Laravel Cashier
- **Search** — scoped to the caller's own workspaces, never cross-tenant
- **Profile & security** — avatar upload, password change, active session management, account deletion

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13, PHP 8.4 |
| Auth | Laravel Sanctum, Laravel Socialite (Google) |
| Database | MySQL 8.4 |
| Cache / Queue | Redis |
| Real-time | Laravel Reverb (self-hosted WebSocket server, Pusher protocol) |
| Billing | Laravel Cashier + Stripe |
| Mail | SMTP |
| Local environment | Docker (Laravel Sail) |

## Architecture

The codebase is organized by **domain** rather than by technical layer:

```
app/Domains/{Domain}/
  Controllers/   HTTP entry points
  Actions/       reusable business logic (e.g. LogActivityAction, MoveCardAction)
  Requests/      Form Request validation
  Resources/     JSON response shaping
  Policies/      authorization rules, registered in AppServiceProvider
```

Domains: `Auth`, `User`, `Workspace`, `Invitation`, `Membership`, `Board`, `Column`, `Card`, `Label`, `Comment`, `Checklist`, `Attachment`, `Achievement`, `Activity`, `Notification`, `Search`, `Subscription`.

Cross-cutting pieces live outside `Domains/`:
- `app/Events/` — broadcast events (`ShouldBroadcastNow`) for real-time updates
- `app/Models/` — Eloquent models (UUID primary keys throughout)
- `routes/channels.php` — WebSocket channel authorization
- `app/Domains/Subscription/Support/PlanLimits.php` — the single source of truth for Free/Pro limits

## Getting Started

### Prerequisites

- Docker Desktop (with WSL2 integration on Windows)
- A [Stripe](https://stripe.com) account in **test mode** (free, no card required) if you want billing to work end-to-end
- The [Stripe CLI](https://stripe.com/docs/stripe-cli) if you want to receive webhooks locally

> The project requires **PHP ≥ 8.4.1**. It will not run on an older system PHP — always run it through Docker.

### Setup

```bash
git clone https://github.com/Task-Flow-project/task_flow_backend.git
cd task_flow_backend
cp .env.example .env
```

Fill in `.env` with, at minimum:

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=taskflow
DB_USERNAME=sail
DB_PASSWORD=password

REDIS_HOST=redis

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your-gmail-app-password   # not your regular password — see Google Account > App Passwords

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost/api/auth/google/callback

REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_PRICE_PRO=price_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

`REVERB_APP_ID/KEY/SECRET` can be any random strings you generate yourself (e.g. `openssl rand -hex 16`) — they're only shared between this app and its own Reverb server, not a third-party service.

Then:

```bash
docker compose up -d --build
docker compose exec laravel.test composer install
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan storage:link
docker compose exec laravel.test php artisan migrate --seed
```

The API is now available at `http://localhost/api`, and the Reverb WebSocket server at `ws://localhost:8080`.

### Receiving Stripe webhooks locally

```bash
stripe listen --forward-to localhost/api/stripe/webhook --api-key sk_test_...
```

This prints a `whsec_...` value — put it in `.env` as `STRIPE_WEBHOOK_SECRET` and re-run `php artisan config:clear`.

## Running Tests

```bash
docker compose exec laravel.test php artisan test
```

The suite includes feature tests for authorization boundaries, plan-limit enforcement, the achievement/streak engine, real-time broadcast events (`Event::fake()`), and a live end-to-end billing test against Stripe test mode (using Stripe's documented `pm_card_visa` test payment method — no real card involved).

## API Reference

Full API documentation, with every endpoint and request/response examples: **[taskflow.docs.buildwithfern.com/task-flow](https://taskflow.docs.buildwithfern.com/task-flow)**

Base URL: `http://localhost/api`. Authentication: `Authorization: Bearer <token>` (obtained from `/register` → `/verify-otp` or `/login`), or the `taskflow_token` cookie for same-origin requests.

Auth-sensitive routes (`/register`, `/login`, `/verify-otp`, `/resend-otp`) are rate-limited to 6 requests/minute per IP.
