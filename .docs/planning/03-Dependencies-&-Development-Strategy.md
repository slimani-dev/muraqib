Version: 1.2 (Updated for Laravel 12 & Wayfinder)

Purpose: This document serves as the "Foundation Phase" reference. It defines the technical stack, required packages, and most importantly, the strategic dependency chain to ensure development momentum is maintained without abandonment.

## 1. The Anti-Abandonment Strategy (The "Commit Protocol")

To avoid the common pitfall of starting everything and finishing nothing, development will adhere to the **Modular Commit Protocol**:

1. **Linear Progression:** We do not jump to "cool features" (like the App Store) until the "boring foundations" (Portainer API Client) are rock solid.
2. **The "Done" Definition:** A module is only considered complete when:

    - The feature works end-to-end (Backend Logic -> Frontend Display).
    - It is documented (internal `README` and code comments).
    - **Crucial:** The specific UI page for it is polished.

3. **Isolation:** If we are working on "Portainer", we ignore "Proxmox". Context switching kills momentum.

## 2. The Dependency Chain (Order of Operations)

This logic dictates the Development Plan. We cannot build `C` without `B`.

- **Foundation (Tier 0):** **Authentication, Layout & Routing.**
    - _Why:_ Every page lives inside the Sidebar/TopBar shell. We need a working "skeleton" before we can show a single widget. `laravel/wayfinder` will handle the routing/discovery logic here.
- **Dependency A (Tier 1):** **The Settings Vault (Database).**
    - _Why:_ We cannot query Portainer or Proxmox without API keys. Hardcoding keys is technical debt. We must build the encrypted storage system first.
- **Dependency B (Tier 2):** **Portainer Integration (Core Infra).**
    - _Why:_ The "App Store" (Wizard) requires a target. If Muraqib can't talk to Portainer to _list_ containers, it certainly can't _deploy_ stacks.
- **Dependency C (Tier 3):** **Service Discovery (The Watcher).**
    - _Why:_ Now that we can list containers, we need to parse their labels (`muraqib.*`) to generate the dashboard.
- **Dependency D (Tier 4):** **The Stack Store (The Builder).**
    - _Why:_ This relies on **Dependency B** (to deploy) and **Dependency A** (to auto-fill global vars like `PUID`).
- **Dependency E (Tier 5):** **External Integrations (Proxmox/Cloudflare/Media).**
    - _Why:_ These are isolated modules that can be added one by one once the core system is stable.

## 3. Technical Dependencies (The Stack)

### 3.1 Backend (Laravel 12)

**Core Framework:**

- `laravel/framework`: v12.x
- `laravel/wayfinder`: For advanced routing/service discovery (User specified).
- `inertiajs/inertia-laravel`: To glue Laravel with Vue.
**Critical Packages (Must Install):**

- **HTTP Client:** `laravel/http` (Standard). _Essential for talking to Portainer/Proxmox APIs._
- **Settings/Secrets:** `spatie/laravel-settings`. _Essential for the "Settings Vault"._
- **Encryption:** Native `Illuminate\Support\Facades\Crypt`. _Essential for storing API keys securely._
- **Real-time:** `laravel/reverb` (WebSockets). _Essential for "Live CPU/RAM" gauges._
- **Async Tasks:** `laravel/horizon` (Redis). _Essential for "Polling" APIs without freezing the UI._

### 3.2 Frontend (Vue 3 + Inertia)

**Core:**

- `vue`: v3 (Composition API).
- `@inertiajs/vue3`: The routing bridge.
- `tailwindcss`, `postcss`, `autoprefixer`: Styling.
**UI Components & Visuals:**

- **Icons:** `@phosphor-icons/vue` (Highly recommended for the "Dashboard" look).
- **Charts:** `apexcharts` and `vue3-apexcharts`. _Essential for Proxmox/Network graphs._
- **Date/Time:** `date-fns`. _For "Uptime" and "Calendar" widgets._
- **UI Primitives:** `headlessui`.

## 4. Infrastructure Dependencies (Dev Environment)

To build Muraqib, your dev environment must mimic the production homelab.

1. **Local Docker Socket:** You need access to `/var/run/docker.sock` to test the "Watcher".
2. **Mock APIs (Optional but recommended):**

    - If you don't want to spam your real Proxmox during dev, we might need a simple JSON mocker for the `GET /api2/json/nodes` endpoint.

3. **Redis:** Required for Laravel Horizon/Queues.
