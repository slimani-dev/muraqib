
**Purpose:** This document contains the complete visual and functional specifications for the Muraqib application. It combines the Site Map, Global Design System, and detailed specifications for all page tiers (Core, Modules, and Overlays).

## 1. Global Visual Context (Copy this for ALL pages)

Project Context (Muraqib):

"Muraqib" is a high-density, interactive homelab dashboard for DevOps engineers. It serves as a "Single Pane of Glass" to monitor infrastructure (Proxmox, Portainer), manage media stacks (Arr, Jellyfin), and track developer workflows (GitHub, PostHog). Unlike read-only dashboards, Muraqib allows active management (deploying stacks via Wizard, restarting containers, approving requests, checking CI/CD status).

**Design System:**

- **Theme:** Dark Mode (`slate-900` background). Professional, dense, high-contrast.
- **Colors:**
    - `Bg`: `#0f172a` (Main), `#1e293b` (Cards/Panels).
    - `Text`: `#f8fafc` (Headings), `#94a3b8` (Meta/Labels).
    - `Accent`: `#6366f1` (Indigo-500 - Primary), `#10b981` (Emerald-500 - Success), `#ef4444` (Red-500 - Error).
- **Typography:** `Inter` (UI), `JetBrains Mono` (Data/Logs).
- **Shapes:** `rounded-xl` cards, subtle `border-slate-700/50` borders.
- **Layout Shell:**
    - **Sidebar (Left):** Fixed width (80px collapsed or 240px expanded). Deep Slate (`#020617`). Navigation items: Dashboard, App Store, Settings.
    - **Top Bar:** Sticky. Search bar (Cmd+K) in center. "Pulse" bar on right (Mini CPU sparklines).
    - **Content:** Padded grid area (`p-6`).

## 2. Site Map

**Tier 1: Core Navigation**

1. **Dashboard (Home):** The customized "Single Pane of Glass".
2. **App Store (Marketplace):** Browser for community templates + Deployment Wizard.
3. **Settings:** Global configuration and API Vault.

Tier 2: Module Dashboards (Drill-downs)

4. Infrastructure Dashboard: Detailed Proxmox nodes, Docker containers, Cloudflare Tunnels.
5. Media Center: Calendar (Arr stack), Requests (Jellyseerr), Active Streams.
6. Developer Studio: GitHub Repositories, CI Status, PostHog Analytics.

Tier 3: Detail Views & Overlays

7. Container Detail Drawer: Slide-out panel for logs/env vars.
8. Auth/Login Page: Standalone entry point.

## 3. Tier 1 Specifications (Core Pages)

### 3.1 Page: Dashboard (Home)

**Goal:** A "Single Pane of Glass" showing the health of the entire homelab. High density, widget-based grid.

**Layout Structure:**

- **Grid:** CSS Grid (Responsive). 1 col (Mobile) -> 4 col (Desktop).
- **Sections:** Welcome Header, Infrastructure Row, Mixed Widget Grid.
**Component Details:**

- **Welcome Header:**
    - Text: "Good Evening, Slimani." | Subtitle: "System Status: Healthy 🟢".
    - Actions: `[+ Deploy App]` (Indigo), `[Refresh Stacks]`, `[Lock Down]` (Red).
- **Infrastructure Widgets (Top Row):**
    - **Proxmox:** 3 Donut Charts (CPU/RAM/Storage). List of nodes below.
    - **Network:** Sparkline chart (Up/Down). Badge: "Tunnel: Healthy 🟢".
- **Service Widgets (Grid Cards):**
    - **Media:** "Upcoming Releases" horizontal scroll (Movie Posters).
    - **Dev:** "Active PRs" list with CI Status (Green Check/Red X).
    - **Requests:** Jellyseerr list with `[Approve]` and `[Deny]` buttons.

### 3.2 Page: App Store (Marketplace)

**Goal:** A browser for community templates and a wizard to deploy them.

**Layout Structure:**

- **Header:** Large Search Input + Category Tags (Media, Dev, Database).
- **Grid:** Auto-fill grid of App Cards.
**Component Details:**

- **App Card:**
    - Icon + Title ("Sonarr") + Badge ("Official").
    - Desc: "Smart PVR for newsgroup users."
    - Footer: `[Install]` Button (Indigo).
- **The Install Wizard (Modal Overlay):**
    - **Step 1 (Config):** Dynamic `.env` inputs. `API_KEY` (Password), `PUID` (Auto-filled).
    - **Step 2 (Network):** Domain input (`sonarr` + `.home.dev`). Toggles: "Expose via Cloudflare", "Protect with Authelia".
    - **Step 3 (Target):** Large Radio Cards: `[Docker/Portainer]` vs `[Proxmox LXC]`.

### 3.3 Page: Settings

**Goal:** Configuration of Muraqib and secrets vault.

**Layout Structure:**

- **Sidebar Layout:** Left Menu (General, API Vault, Templates) | Right Scrollable Panel.
**Component Details:**

- **API Vault Section:**
    - **Cloudflare:** Inputs for `Token` (Masked), `Account ID`. Status: `Connected 🟢`.
    - **Proxmox:** Inputs for `Host URL`, `Token ID`, `Secret`.
    - **Portainer:** Input for `API Key`.
- **Template Sources:** Table of Git repositories (`github.com/muraqib/templates`).
- **Global Defaults:** Inputs for `Root Domain`, `Admin Email`, `Theme`.

## 4. Tier 2 Specifications (Module Dashboards)

### 4.1 Page: Infrastructure Dashboard

**Goal:** Advanced management of compute and networking resources.

**Layout Structure:**

- **Tabs:** `All Resources` | `Compute (Proxmox)` | `Containers (Docker)` | `Network (Cloudflare)`.
- **Content:** Stacked full-width panels.
**Component Details:**

- **Proxmox Nodes Panel:**
    - Table: `Status` (Icon), `Node Name`, `CPU` (Bar), `RAM` (Bar), `Uptime`, `Actions` (Power/Shell).
- **Docker Containers Panel:**
    - Table: `State` (Border color), `Name`, `Image`, `IP` (Mono), `Ports`, `Controls` (`[Stop]`, `[Restart]`, `[Logs]`).
    - **Update Feature:** Yellow dot on restart button if image update available.
- **Cloudflare Tunnels:**
    - List: `muraqib-cluster` -> Status `Healthy 🟢`.
    - Log: Recent DNS updates/CNAME creation.

### 4.2 Page: Media Center

**Goal:** Graphical overview of entertainment stack.

**Layout Structure:**

- **Split:** Left Col (70% Calendar) | Right Col (30% Active/Requests).
**Component Details:**

- **Calendar:** Month/Week view. Purple pills (TV), Blue pills (Movies). Click for details.
- **Active Streams:**
    - Card: Backdrop image + User Avatar + Progress Bar.
    - Text: "John watching: The Matrix (Direct Play 🟢)".
- **Requests:** List of pending items with `[Approve]` / `[Deny]` actions.

### 4.3 Page: Developer Studio

**Goal:** CI/CD and Analytics tracking.

**Layout Structure:** 3 Column Grid.

**Component Details:**

- **GitHub Repos:** Cards showing `Repo Name`, `Last Commit` (Hash), `Branch`, `CI Status` (Linked circles).
- **PostHog Events:** Area chart "User Activity (24h)".
- **Deployment History:** Vertical timeline of recent stack updates.

## 5. Tier 3 Specifications (Overlays & Auth)

### 5.1 Overlay: Container Detail Drawer

**Goal:** Deep inspection of a single container.

**Layout Structure:** Slide-out panel (Right side).

**Component Details:**

- **Header:** Title (Service Name), Status Badge, Actions (`[Restart]`, `[Stop]`).
- **Tabs:** `Logs`, `Console`, `Environment`, `Stats`.
- **Logs Tab:** Black console window, `JetBrains Mono` font, colored output.
- **Env Tab:** Key-Value table. Secrets masked (`••••`) with toggle.

### 5.2 Page: Auth/Login

**Goal:** Secure entry point.

**Component Details:**

- **Background:** Dark Slate (`#0f172a`) with subtle particle mesh.
- **Card:** Glassmorphism style.
    - Logo + Title "System Access".
    - Inputs: User / Password.
    - Button: `Authenticate` (Indigo Glow).
    - Footer: "Protected by Authelia" or "Local Auth".
