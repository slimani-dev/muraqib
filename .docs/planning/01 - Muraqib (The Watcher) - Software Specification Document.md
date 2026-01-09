Version: 1.4.0

Status: Comprehensive Draft

Target Stack: Laravel 11 (Backend), Vue.js 3 (Frontend), Tailwind CSS

## 1. Project Vision & Architecture

### 1.1 Vision

Muraqib is a self-hosted, "single pane of glass" dashboard designed for power users, DevOps engineers, and homelab enthusiasts. Unlike passive dashboards (e.g., Glance, Homepage) which only _display_ links and status, Muraqib is **active**. It monitors health, interacts with APIs, manages Docker containers, and centralizes developer workflows.

### 1.2 Technology Stack

- **Backend:** Laravel 11
    
    - **Database:** SQLite (default for easy portability) or MySQL/PostgreSQL.
        
    - **Task Scheduling:** Laravel Scheduler (for polling APIs).
        
    - **Real-time:** Laravel Reverb (WebSockets) for pushing live stats (CPU/RAM) to the frontend.
        
- **Frontend:** Vue.js 3 (Composition API)
    
    - **Framework:** Inertia.js (Monolith approach) or SPA with API.
        
    - **Styling:** Tailwind CSS.
        
    - **Icons:** Phosphor Icons or FontAwesome.
        
- **Infrastructure:** Docker (The app itself runs as a container).
    

## 2. Service Discovery Engine (The Core)

The defining feature of Muraqib is its ability to auto-configure via Docker labels, similar to Traefik or Glance, but with richer configuration options.

### 2.1 The "Muraqib" Label Namespace

Services declare their presence to Muraqib using `labels` in `docker-compose.yml`. Muraqib's "Watcher" service scans the Docker Socket periodically to build the dashboard.

Standard Labels:

| Label Key | Value Type | Description |

| :--- | :--- | :--- |

| muraqib.enable | true/false | Required. Enables discovery for this container. |

| muraqib.name | String | Display name (e.g., "Prowlarr"). |

| muraqib.group | String | Categorization (e.g., "Media", "Infrastructure", "Dev"). |

| muraqib.icon | URL/String | Icon URL or icon class name (e.g., fas fa-server). |

| muraqib.url | URL | The internal/external URL to open on click. |

| muraqib.description| String | Subtitle or short description. |

### 2.2 Integration Labels (Advanced)

To enable specific widgets (like status checks or queue depths), containers add specific integration labels.

**Example: Sonarr Integration**

```
labels:
  - muraqib.enable=true
  - muraqib.group=Media
  - muraqib.integration=sonarr
  - muraqib.api_key=${SONARR_API_KEY} # Environment variable injection recommended
```

## 3. Integration Modules

Muraqib is divided into functional modules. Each module has specific widgets and API capabilities.

### 3.1 Infrastructure Module

- **Portainer / Docker:**
    
    - **Container Management:** List, Start, Stop, Restart, and View Logs directly from Muraqib.
        
    - **Update Manager:**
        
        - **Check:** Compares local image hash against the registry hash to detect updates.
            
        - **Action:** "Pull & Redeploy" button updates the stack with zero CLI interaction.
            
- **Proxmox VE:**
    
    - **Dashboard Widget:** Real-time gauge cluster for Cluster CPU, RAM, and Storage usage via WebSockets.
        
    - **VM/LXC Control:** Power toggle (Start/Shutdown/Reboot) for VMs directly from the UI.
        
- **Cloudflare:**
    
    - **Tunnel Monitor:** Queries Cloudflare API to check the status of Cloudflare Tunnels (Healthy/Degraded/Down).
        
    - **DDNS Status:** Verifies if the public IP matches the DNS record.
        

### 3.2 Media & Entertainment Module

- **Jellyfin / Plex:**
    
    - **"Now Watching" Widget:** Shows active sessions, user avatar, and media backdrop.
        
    - **Library Stats:** Total movies/series count.
        
- **Jellyseerr / Overseerr:**
    
    - **Requests Widget:** Shows pending requests with **Approve** and **Deny** buttons directly on the dashboard.
        
- **The ARR Stack (Sonarr/Radarr/Prowlarr):**
    
    - **Calendar Widget:** "Coming Soon" scrollable list.
        
    - **Queue Widget:** Current download speeds and active items.
        
    - **Health:** Warning indicators if an indexer is down (via Prowlarr).
        

### 3.3 Developer Workflow Module

- **GitHub:**
    
    - **Notifications Widget:** Unread notification count (Issues, Mentions).
        
    - **PR Watch:** List of open PRs on specific repositories with CI/CD status.
        
- **PostHog:**
    
    - **Events Widget:** Graph showing event spikes (e.g., "User Signups").
        
    - **Alerts:** Display critical alerts triggered in PostHog.
        

## 4. Unified Deployment Engine (The Builder)

This module replaces manual configuration. It acts as a "Community Market" that fetches templates, gathers user input via a Wizard, and orchestrates deployment across **Portainer, Proxmox, Traefik, and Cloudflare**.

### 4.1 Community Template Marketplace

Muraqib connects to git repositories to populate the "App Store". This allows the community to build stacks (e.g., "Ultimate Media Stack", "Dev Environment").

**Standardized Folder Structure:**

```
/templates
  ├── /arr-stack
  │     ├── docker-compose.yml   # Base stack for Portainer
  │     ├── install.sh           # Base script for Proxmox LXC (optional)
  │     ├── .env.example         # Template variables (parsed by Wizard)
  │     ├── muraqib.json         # Meta-config (Categories, Icons, Description)
  │     └── README.md            # Detailed instructions shown in UI
```

### 4.2 The Deployment Wizard (Laravel Logic)

When a user clicks "Install" on a template, Muraqib launches a 3-step wizard:

**Step 1: Configuration (The .env Parser)**

- Reads `.env.example` and generates a dynamic form.
    
- **Smart Defaults:** Auto-fills generic vars (`PUID`, `PGID`, `TZ`, `EMAIL`) from Global Settings.
    
- **Secrets:** Renders password fields for keys ending in `_KEY`, `_TOKEN`, `_PASSWORD`.
    

**Step 2: Networking & Access**

- **Domain:** User enters subdomain (e.g., `media`). Muraqib appends the root domain (`.home.example.com`).
    
- **Exposure:** Checkbox: "Publicly Accessible via Cloudflare?".
    
- **Auth:** Checkbox: "Protect with Traefik BasicAuth/Authelia?".
    

**Step 3: Target Selection**

- **Compute Provider:** Choose "Docker (Portainer)" or "Proxmox LXC".
    

### 4.3 Multi-Provider Orchestration (The "Magic")

Once the wizard is submitted, Muraqib executes the following pipeline automatically:

#### A. Ingress Layer (Cloudflare Automation)

- **Tunnel Check:** Queries Cloudflare API for the `muraqib-cluster` tunnel. If missing, it creates one.
    
- **Token Injection:** Generates the `TUNNEL_TOKEN` and injects it into the container's environment.
    
- **Routing Update:** Pushes a new ingress rule to Cloudflare: `hostname: media.home.example.com -> service: http://media-container:8096`.
    
- **DNS Record:** Creates a CNAME record via API pointing the subdomain to the tunnel UUID.
    

#### B. Routing Layer (Traefik)

- **Dynamic Label Injection:** Muraqib modifies the `docker-compose.yml` in memory.
    
    - Adds `traefik.http.routers.service.rule=Host('media.home.example.com')`.
        
    - Adds `traefik.http.routers.service.middlewares=authelia@docker` (if Auth was selected).
        

#### C. Compute Layer (Deployment)

- **Portainer:** Sends the modified Compose file and Environment variables to the Portainer API (`POST /api/stacks`).
    
- **Proxmox:** Connects via SSH, runs the install script, and injects variables.
    

## 5. System Settings & Configuration

### 5.1 The Settings UI

A comprehensive backend interface to manage credentials and global defaults.

- **API Keys Vault:**
    
    - **Portainer:** URL, Access Token, Endpoint ID.
        
    - **Proxmox:** Hostname, Node Name, API Token.
        
    - **Cloudflare:** Email, Global API Key (or Scoped Token).
        
    - **GitHub:** Personal Access Token (for fetching private templates).
        
- **Global Variables:** Define defaults applied to all templates (`PUID`, `PGID`, `TZ`, `ROOT_DOMAIN`, `ADMIN_EMAIL`).
    
- **Template Sources:** Manage the list of Git Repositories used for the Marketplace.
    

### 5.2 Network Requirements

- **Docker Socket:** Muraqib container must have `/var/run/docker.sock` mounted.
    
- **Internal Network:** Muraqib must reside on the same Docker network as the services it monitors.
    

## 6. Visual & UX Design

### 6.1 Design Philosophy

- **Layout:** Responsive Grid System (Mobile-First).
    
- **Information Density:** High. Use "Sparklines" and badges instead of large whitespace.
    
- **Themes:** Dark Mode by default (Cyberpunk/Hacker aesthetic optional).
    

### 6.2 Key Interface Elements

1. **Sidebar:** Navigation (Dashboards, **App Store**, Settings).
    
2. **The "Pulse" Bar:** Top sticky bar showing aggregate system health (CPU/RAM/Net).
    
3. **Action Drawers:** Slide-out panels for quick actions (Restart, Logs) without leaving the page.
    

## 7. User Scenario: The "Zero to Hero" Flow

**The Goal:** The user wants to install **Sonarr**.

1. **Discovery:** User goes to "App Store" tab in Muraqib.
    
2. **Selection:** User searches "Sonarr" (results pulled from Community Git Repo) and clicks "Install".
    
3. **Wizard:**
    
    - Muraqib asks for `SONARR_API_KEY` (User generates one or leaves blank to auto-generate).
        
    - Muraqib auto-fills `PUID`/`PGID` from global settings.
        
    - User checks "Public Access via Cloudflare".
        
4. **Deployment:** User clicks "Deploy".
    
5. **Automation (Backend):**
    
    - Muraqib talks to Cloudflare -> Creates Tunnel Config -> Creates DNS `sonarr.home.dev`.
        
    - Muraqib talks to Portainer -> Deploys Stack with Traefik labels.
        
6. **Success:** 1 minute later, the Sonarr card appears on the dashboard with a green status light.