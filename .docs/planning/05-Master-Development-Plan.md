
Strategy: Linear Execution. One Module at a time. No context switching.

Stack: Laravel 12, Vue 3, Wayfinder.

## Phase 1: The Foundation (Tier 0 & 1)

Goal: A working "Shell" and a secure place to store secrets.

Constraint: Do not build any dashboard widgets yet.

### 1.1 Project Initialization

- [x] `laravel new muraqib --git` (Laravel 12).
- [x] Install `inertiajs/inertia-laravel` and Vue 3 stack.
- [x] Install `laravel/wayfinder`.
- [x] Configure Tailwind CSS with the "Muraqib" color palette (Slate 900/Indigo 500).
- [x] **Commit:** "Initial scaffolding"

### 1.2 The Layout "Shell"

- [x] Create `MainLayout.vue`: Sidebar (Collapsible), TopBar (Sticky), Slot (Content).
- [x] Implement `Wayfinder` logic for navigation menu generation.
- [x] Create the "Dashboard" and "Settings" placeholder pages.
- [x] **Commit:** "UI Shell complete"

### 1.3 The Settings Vault (Backend)

- [x] Install `spatie/laravel-settings`.
- [x] Create Migration: `settings` table.
- [x] Create `SettingsService`:
    - Method: `set(key, value)` -> Encrypts value if key ends in `_key` or `_token`.
    - Method: `get(key)` -> Decrypts automatically.
- [x] **Commit:** "Settings Vault backend logic"

### 1.4 The Settings UI (Frontend)

- [x] Create `Settings/Index.vue`.
- [x] Build the "API Keys Vault" form (Cloudflare, Proxmox, Portainer inputs).
- [x] Wire up form submission to `SettingsController@update`.
- [x] **Verify:** Enter a dummy key, save, check DB to ensure it is encrypted.
- [x] **Commit:** "Settings UI functional"

## Phase 2: Portainer Core (Tier 2)

Goal: Muraqib can talk to Docker.

Constraint: Do not work on "Auto-discovery" yet. Just raw API communication.

### 2.1 The Portainer Client

- [ ] Create `App\Services\Portainer\PortainerClient`.
- [ ] Implement Auth: Fetch `PORTAINER_API_KEY` from Settings Vault.
- [ ] Method: `getEndpoints()` -> Returns list of Docker environments.
- [ ] Method: `getContainers(endpointId)` -> Returns raw JSON list.
- [ ] **Tinker Test:** Run `app(PortainerClient::class)->getContainers(1)` in terminal. Ensure JSON is returned.
- [ ] UI Test in the settings page
- [ ] **Commit:** "Portainer Client API implementation"

### 2.2 The Container Manager UI

- [ ] Create `Infrastructure/Containers.vue`.
- [ ] Fetch container list via Inertia prop.
- [ ] Render a simple Table: Name, Image, State (Running/Stopped).
- [ ] Add "Restart" button -> Calls `PortainerClient@restartContainer`.
- [ ] **Verify:** Click restart in Muraqib -> Watch container restart in real Portainer.
- [ ] **Commit:** "Basic Container Management UI"

## Phase 3: The Watcher (Tier 3)

Goal: Auto-generate the dashboard from labels.

Constraint: Focus only on parsing labels.

### 3.1 The Label Parser

- [ ] Create `App\Services\Discovery\LabelParser`.
- [ ] Logic:
    - Iterate through `getContainers()` from Phase 2.
    - Filter for `muraqib.enable=true`.
    - Map `muraqib.name`, `muraqib.group`, `muraqib.icon`.
- [ ] **Commit:** "Label Parsing Logic"

### 3.2 The Dynamic Dashboard

- [ ] Update `Dashboard/Index.vue`.
- [ ] Replace hardcoded widgets with a `v-for` loop based on the Parsed Groups.
- [ ] **Verify:** Add `muraqib.enable=true` to a random container in your homelab. Refresh Muraqib. See it appear.
- [ ] **Commit:** "Dynamic Dashboard Discovery"

## Phase 4: The Stack Store (Tier 4)

Goal: Deploy new apps.

Dependencies: Phase 1 (Settings for PUID/PGID), Phase 2 (Portainer for Deployment).

### 4.1 Template Engine

- [ ] Create `App\Services\Marketplace\TemplateFetcher`.
- [ ] Logic: `Http::get('github.com/muraqib/templates/index.json')`.
- [ ] Create `App Store` UI page: Grid of templates.
- [ ] **Commit:** "Marketplace Browser"

### 4.2 The Wizard Logic

- [ ] Create `WizardController`.
- [ ] Logic: Parse `.env.example` -> Generate Form Fields.
- [ ] Logic: On Submit -> Merge User Input + Global Settings -> Generate Final Compose String.
- [ ] **Commit:** "Wizard Variable Parsing"

### 4.3 The Deploy Action

- [ ] Add Method to `PortainerClient`: `deployStack(name, composeString, envVars)`.
- [ ] Wire Wizard "Deploy" button to this method.
- [ ] **Verify:** Deploy a "Hello World" stack from Muraqib.
- [ ] **Commit:** "Full Deployment Pipeline"

## Phase 5: External Integrations (Tier 5)

Goal: The "Nice to haves" (Proxmox, Cloudflare).

Strategy: Pick ONE. Finish it. Move to next.

### 5.1 Proxmox Module

- [ ] Create `ProxmoxClient` (Auth via Settings Vault).
- [ ] Implement `getNodes()` and `getClusterStatus()`.
- [ ] Create "Infrastructure Dashboard" widget (Gauges).
- [ ] **Commit:** "Proxmox Integration"

### 5.2 Cloudflare Automation

- [ ] Create `CloudflareClient`.
- [ ] Implement `createTunnel()` and `addDnsRecord()`.
- [ ] Update Wizard (Phase 4) to include "Expose via Cloudflare" checkbox.
- [ ] **Commit:** "Cloudflare Tunnel Automation"

## Final Phase: Polish & Real-time

- [ ] Install `laravel/reverb`.
- [ ] Create `SystemStats` event.
- [ ] Broadcast CPU/RAM usage every 3 seconds.
- [ ] Frontend: Listen to channel `system` and update gauges without refresh.
- [ ] **Final Commit:** "v1.0.0 Release Candidate"
