
# Muraqib

<div align="center">

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laravel/framework.svg?style=flat-square&label=Release)](https://packagist.org/packages/laravel/framework)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/laravel/framework/tests.yml?branch=master&label=Tests&style=flat-square)](https://github.com/laravel/framework/actions?query=workflow%3ATests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel/framework.svg?style=flat-square&label=Downloads)](https://packagist.org/packages/laravel/framework)
[![License](https://img.shields.io/packagist/l/laravel/framework.svg?style=flat-square&label=License)](https://packagist.org/packages/laravel/framework)
[![PHP Version](https://img.shields.io/packagist/php-v/laravel/framework.svg?style=flat-square&color=777bb4)](https://packagist.org/packages/laravel/framework)

**The Ultimate Sentinel for your Cloud & Infrastructure.**

[Features](#features) • [Installation](#installation) • [Screenshots](#screenshots) • [Contributing](#contributing) • [License](#license)

</div>

---

## 📖 About Muraqib

**Muraqib** (meaning "Observer" or "Supervisor") is a cutting-edge centralized dashboard designed to unify your infrastructure management. Built on the robust **Laravel 12** framework and energized by **Filament 4**, Muraqib provides a single pane of glass for monitoring servers, managing cloud access, and orchestrating containerized environments.

Stop juggling multiple portals. Monitor your Netdata instances, manage Cloudflare Zero Trust policies, and oversee Portainer stacks from one beautiful, responsive interface.

## ✨ Key Features

### 🛡️ Cloudflare Zero Trust Integration
Seamlessly manage your Zero Trust architecture without leaving the dashboard.
- **Access Policies & Applications**: Create, update, and audit access rules.
- **Service Tokens**: Manage lifecycle and permissions for service-to-service authentication.
- **Deep Integration**: Real-time sync with Cloudflare API.

### 📊 Netdata Monitoring
Keep a pulse on your infrastructure with real-time telemetry.
- **Live Dashboards**: View key metrics (CPU, RAM, Network) at a glance.
- **Disk & Network Widgets**: Specialized widgets for checking storage health and traffic flow.
- **Seamless Connectivity**: Aggregate stats from multiple Netdata nodes.

### 🐳 Portainer & Docker Management
Orchestrate your containers with ease.
- **Stack & Container Visualization**: Inspect running stacks and container states.
- **Sync & Updates**: Track version drifts and sync states with your Portainer instances.
- **Health Checks**: Instant specific container health status visibility.

### ⚡ Modern Tech Stack
Built with developer experience and performance in mind.
- **Laravel 12**: The latest and greatest PHP framework.
- **Filament 4**: The gold standard for TALL stack admin panels.
- **Inertia.js & Vue 3**: A silky smooth, SPA-like frontend experience.
- **Tailwind CSS 4**: Beautiful, modern styling by default.

## 📸 Screenshots

<div align="center">
  <!-- Upload your screenshots to a public host or a 'docs' folder and link them here -->
  <img src="https://placehold.co/1200x600/101827/4f46e5?text=Dashboard+Overview" alt="Dashboard Screenshot" style="border-radius: 10px; margin-bottom: 20px;">
</div>

| Cloudflare Management | Portainer Integration |
|:---:|:---:|
| <img src="https://placehold.co/600x400/101827/4f46e5?text=Cloudflare+Rules" alt="Cloudflare" style="border-radius: 8px;"> | <img src="https://placehold.co/600x400/101827/4f46e5?text=Portainer+Stacks" alt="Netdata" style="border-radius: 8px;"> |

## 🚀 Installation

### Prerequisites
- PHP 8.3+
- Composer
- Node.js & PNPM
- MySQL / PostgreSQL

### Setup Guide

1. **Clone the repository**
   ```bash
   git clone https://github.com/slimani-dev/muraqib.git
   cd muraqib
   ```

2. **Install Dependencies**
   ```bash
   composer install
   pnpm install
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Edit `.env` with your database, Cloudflare, and Portainer credentials.*

4. **Initialize Database**
   ```bash
   php artisan migrate --seed
   ```

5. **Build Assets**
   ```bash
   pnpm run build
   ```

6. **Serve**
   ```bash
   php artisan serve
   ```

## 🛠️ Configuration

### Cloudflare
Add your API Token and Account ID to the `.env` file to enable Zero Trust management:
```env
CLOUDFLARE_API_TOKEN=your_token_here
CLOUDFLARE_ACCOUNT_ID=your_account_id
```

### Portainer & Netdata
Portainer and Netdata instances are managed directly via the UI. Log in as an administrator to add your first endpoint.

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to submit pull requests, report issues, and suggest improvements.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is open-sourced software licensed under the **[MIT license](LICENSE)**.

---

<div align="center">
    <sub>Built with ❤️ by Moh</sub>
</div>
