# Muraqib

**Muraqib** (Arabic for "Observer/Monitor") is a unified dashboard designed to bring order to your homelab chaos. Built to be the default homepage for developers, it provides a centralized interface to monitor and manage your infrastructure, containers, and virtual machines without switching tabs.

![Muraqib Dashboard Preview](img/muraqib_dashboard.png)

---

### 🚀 The Vision

I started this project because I wanted a single pane of glass for my Proxmox, Portainer, and Docker instances—a dashboard that feels fast, looks clean, and actually helps me maintain my infrastructure rather than just displaying static status indicators.

### ✨ Key Features

- **Infrastructure Overview:** Real-time monitoring of host resources (CPU, RAM, Storage).
- **Unified Provider Support:** Seamless integration with Proxmox VE, Docker, and Portainer APIs.
- **Developer-First Design:** Clean, distraction-free interface built for efficiency.
- **Customizable Widgets:** A modular card system to prioritize the services that matter to you.
- **Tech Stack:** Built on the robust **Laravel 13** ecosystem, utilizing **FilamentPHP** and **Vue.js** for a reactive, high-performance administrative experience.

### 📈 Muraqib Development Progress Report

Hello everyone,

I want to provide a transparent update on the development of **Muraqib**, our unified dashboard for homelab and infrastructure management. We are making massive strides toward our goal of a true "single pane of glass," and I want to share exactly what is working today and what is currently sitting in the development pipeline.

---

### ✅ Fully Functional Features (Ready)

The core engine of Muraqib is locked in and performing beautifully. The following features are fully implemented:

- **Cloudflare Tunnels & Ingress Integration:** A seamless, optional setup wizard connects the Cloudflare API to Muraqib. Manage tunnels and ingress rules directly from the dashboard. For those who want a unified workflow, you can deploy Portainer stacks and instantly attach Cloudflare ingress rules and tunnels in one place.
- **Advanced Portainer & Docker Orchestration:** Complete visualization of your containers and stacks. You have full control to manage and update your stacks directly from Muraqib, including advanced options like `pull` and `prune`.
- **Smart Image Registry & Label Discovery:** A custom registry-checking service actively monitors your Docker images for updates and tracks container states. We've also built a custom label-based integration system (similar to Glance) to automatically discover and populate your services.
- **Live Netdata Telemetry:** We are successfully pulling real-time stats directly from Netdata nodes. CPU, RAM, and Network traffic are aggregated into clean, readable dashboard widgets.
- **The Ultimate Media Hub:** Muraqib currently features full backend API communication with Jellyfin, Seerr, Radarr, Sonarr, Bazarr, and Transmission. 
- **The Administrative Backbone:** The Filament 5 admin panel handles all API connections, user authentication, and application theming. It's solid and operational, though we are still ironing out a few edge cases to reach 100% stability.

---

### 🚧 Current Development Focus (Work In Progress)

While the features above are working perfectly, I am holding off on an Alpha release until the following items (which I am actively building right now) meet my standards:

- **Cloudflare Access Policies:** Full management of your Zero Trust Access Policies directly from the dashboard.
- **Massive Frontend UI Refactor:** The dashboard is currently undergoing a structural rewrite. I am modularizing the Vue 3 components into dedicated `containers/` and `media/` structures. This makes the UI significantly sleeker and the codebase much easier to maintain for future contributors.
- **Dual-Load Caching & Background Sync:** To ensure the dashboard feels instantaneous, I am actively wiring up a dual-load system. Muraqib will load the dashboard *instantly* using cached data, while simultaneously using Inertia.js to defer the fetching of fresh live data from your APIs in the background. In the near future, this will also include dedicated background jobs to keep the data updated automatically. 
- **Dynamic Weather Widget:** I am finalizing the UI for a gorgeous weather widget powered by Open-Meteo and dynamic Unsplash backgrounds.
- **Portainer Setup Wizard (Roadmap):** We are laying the groundwork for a setup wizard that will eventually allow you to auto-deploy standard default stacks directly from Muraqib.

---

### 🔮 The Next Phase (Advanced Features & Dedicated Views)

Once the core dashboard is fully polished, development will expand into advanced team management and dedicated full-page views for deeper control:

- **Ultimate Dashboard Customization (Modular Widgets):** A massive upcoming overhaul to allow fully customizable, free-flowing dashboard layouts. Every service widget (like Jellyfin's "Continue Watching" slider) will be engineered in multiple sizes and functional variants so they can snap perfectly into any column or full-width row you desire.
- **Multi-Tenancy (Environments):** Manage completely separate environments (e.g., 'Home' vs. 'Work') from a single Muraqib instance, with fully isolated panels and dashboards. Invite specific users to one tenant while keeping others private.
- **Role & User Management:** Advanced user administration with fine-grained access control and role-based permissions.
- **Passkey Authentication:** Secure, passwordless logins using your device's built-in biometrics or security keys.
- **OIDC Integration:** Connect Muraqib with your existing identity providers (Authentik, Authelia, Keycloak, etc.) for seamless Single Sign-On (SSO).
- **Netdata Fleet View:** A dedicated page to aggregate and compare advanced metrics across all your Netdata servers simultaneously.
- **Media Management View:** A centralized hub solely focused on your entire media stack for deeper insights and control.
- **Productivity & Work Hub:** A focused workspace page integrating your to-do lists, upcoming meetings, emails, and other daily productivity tools.
- **RSS Feed Reader:** A built-in RSS feed widget and dedicated view to keep up with your favorite news, blogs, and homelab updates directly from Muraqib.

---

### 🛠️ The Technology Stack

As a reminder, Muraqib is built for developer experience and raw performance, utilizing:
- **Laravel 13**
- **Filament 5**
- **Vue 3 (Inertia.js v3)**
- **Tailwind CSS v4**

I am obsessing over the UI quality and load performance before dropping the first release. Once this current frontend refactoring is complete, I will share a demo video and an official timeline.

Thank you for your continued patience and support!

---

### 🤝 Support the Development

Muraqib is a passion project built to solve the fragmentation of self-hosted management. If you find value in what I'm building, consider supporting the development through Ko-fi. Your support helps cover the cost of development hardware and caffeine!

**[Support Muraqib on Ko-fi](https://ko-fi.com/slimani_dev)**

---

### 📬 Stay Updated

This project is currently in active development. To be notified when the Alpha/Beta is released:

1. **Star this repository** to keep track of progress.
2. **"Watch" this repository** (selecting "Releases only") to get an email notification the moment the first build drops.

Built by Mohamed | [slimani*dev*](https://ko-fi.com/slimani_dev)
