# Settings Module Specification (The Vault)

Purpose: Detailed blueprint for "1.4 The Settings UI". This module manages the credentials required for Muraqib to interact with external services.

Security Level: Critical. All sensitive fields (Keys, Tokens, Passwords) must be encrypted at rest.

## 1. Architecture & Database Design

We will use `spatie/laravel-settings` to group configurations into logical classes. Each class maps to a JSON file or database rows, but we will use the **Database Repository** with column encryption.

### 1.1 Settings Classes (Backend)

**A. `GeneralSettings`**

- `site_name` (String): Custom name (e.g., "Slimani Lab").
    
- `root_domain` (String): The base domain for Traefik rules (e.g., `home.slimani.dev`).
    
- `timezone` (String): Default TZ for deployments (e.g., `Africa/Algiers`).
    
- `puid` (Int): Default User ID for Docker volumes (e.g., `1000`).
    
- `pgid` (Int): Default Group ID (e.g., `1000`).
    

**B. `InfrastructureSettings` (Portainer, Proxmox, Cloudflare)**

- `portainer_url` (URL): e.g., `https://portainer.local:9443`.
    
- `portainer_api_key` (Encrypted).
    
- `proxmox_url` (URL): e.g., `https://192.168.1.10:8006`.
    
- `proxmox_user` (String): e.g., `root@pam`.
    
- `proxmox_token_id` (String): e.g., `muraqib-token`.
    
- `proxmox_secret` (Encrypted).
    
- `cloudflare_email` (String).
    
- `cloudflare_api_token` (Encrypted).
    
- `cloudflare_account_id` (String): Required for Tunnel API.
    

**C. `MediaSettings` (Jellyfin, Seerr, Downloaders)**

- `jellyfin_url` (URL).
    
- `jellyfin_api_key` (Encrypted).
    
- `jellyseerr_url` (URL).
    
- `jellyseerr_api_key` (Encrypted).
    
- `transmission_url` (URL): RPC endpoint.
    
- `transmission_username` (String).
    
- `transmission_password` (Encrypted).
    

**D. `DeveloperSettings` (GitHub, PostHog)**

- `github_token` (Encrypted): PAT for reading private templates/repos.
    
- `posthog_project_key` (Encrypted).
    
- `posthog_host` (String): `us` or `eu`.
    

## 2. UI Implementation Details (Frontend)

File: resources/js/Pages/Settings/Index.vue

Layout: MainLayout -> 2-Column Grid (Sidebar Menu | Content Panel).

### 2.1 Component Architecture

**1. `SettingsLayout.vue` (Wrapper)**

- **Sidebar:** Vertical tabs (`General`, `Infrastructure`, `Media`, `Developer`).
    
- **Slot:** Renders the active form group.
    

**2. `SettingsCard.vue` (Container)**

- **Props:** `title` (String), `description` (String).
    
- **Slots:** `default` (Form inputs), `footer` (Save/Test buttons).
    
- **Style:** `bg-slate-800 rounded-xl border border-slate-700 p-6`.
    

**3. `SecretInput.vue` (The "Eye" Field)**

- **Props:** `modelValue`, `id`, `label`, `placeholder`.
    
- **Internal State:** `isRevealed` (Boolean).
    
- **Template:**
    
    ```
    <div class="relative">
      <input :type="isRevealed ? 'text' : 'password'" ... />
      <button @click="isRevealed = !isRevealed" class="absolute right-3 top-3">
        <EyeIcon v-if="!isRevealed" />
        <EyeSlashIcon v-else />
      </button>
    </div>
    ```
    

**4. `TestConnectionBtn.vue` (Interactive Feedback)**

- **Props:** `service` (String), `payload` (Object).
    
- **Action:** POSTs the credentials to `/api/test-connection/{service}` without saving.
    
- **States:** `Idle` (Gray) -> `Testing` (Spinner) -> `Success` (Green Check) -> `Fail` (Red X + Error Msg).
    

## 3. Detailed Service Inventory (The Form Fields)

This table defines exactly what inputs are needed for each service to function fully.

### Group 1: Infrastructure (The Backbone)

|   |   |   |   |   |
|---|---|---|---|---|
|**Service**|**Field Label**|**Var Name**|**Type**|**Why do we need it?**|
|**Portainer**|Host URL|`portainer_url`|URL|To reach the API.|
||Access Token|`portainer_api_key`|Secret|To List Containers, Deploy Stacks, Restart Services.|
|**Proxmox**|Host URL|`proxmox_url`|URL|To reach the API.|
||User/Realm|`proxmox_user`|String|Auth (e.g., `root@pam`).|
||Token ID|`proxmox_token_id`|String|API Auth ID.|
||Secret|`proxmox_secret`|Secret|API Auth Secret.|
|**Cloudflare**|Account ID|`cf_account_id`|String|Required to list/create Tunnels.|
||API Token|`cf_api_token`|Secret|Must have `Zone:Read`, `DNS:Edit`, `Tunnel:Edit` permissions.|

### Group 2: Media Center (The Content)

|   |   |   |   |   |
|---|---|---|---|---|
|**Service**|**Field Label**|**Var Name**|**Type**|**Why do we need it?**|
|**Jellyfin**|Host URL|`jellyfin_url`|URL|To link to the interface.|
||API Key|`jellyfin_key`|Secret|To fetch "Now Playing" sessions and Library counts.|
|**Jellyseerr**|Host URL|`jellyseerr_url`|URL|To link to the interface.|
||API Key|`jellyseerr_key`|Secret|To fetch "Pending Requests" and approve/deny them.|
|**Transmission**|RPC URL|`trans_url`|URL|For the Queue Widget (Download Speed/Progress).|
||Username|`trans_user`|String|RPC Auth.|
||Password|`trans_pass`|Secret|RPC Auth.|

### Group 3: Developer Tools (The Workflow)

|   |   |   |   |   |
|---|---|---|---|---|
|**Service**|**Field Label**|**Var Name**|**Type**|**Why do we need it?**|
|**GitHub**|Personal Token|`github_token`|Secret|To fetch private templates and check CI/CD status on Repos.|
||Username|`github_user`|String|To filter PRs assigned to you.|
|**PostHog**|Instance|`posthog_host`|Select|`US` or `EU` (Cloud) or Custom URL (Self-hosted).|
||Project Key|`posthog_key`|Secret|To fetch Event charts.|

## 4. Backend Implementation Plan

### 4.1 Routes

```
Route::middleware('auth')->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-connection', [ConnectionTesterController::class, 'test']);
});
```

### 4.2 Validation Logic

- **URLs:** Must be valid URLs (`url`), usually enforcing `https` is recommended but `http` allowed for local LAN.
    
- **Keys:** `nullable|string`. If left empty during an update, **do not overwrite** the existing encrypted value in DB (classic password update logic).
    

### 4.3 The `ConnectionTesterController`

This is crucial for UX. Before saving, users can verify their keys work.

- `test(Request $request)`:
    
    - Switch `$request->service`:
        
        - **Case 'portainer':** Try `GET $url/api/endpoints` with headers. Return 200 OK or 401 Unauthorized.
            
        - **Case 'proxmox':** Try `GET $url/api2/json/version`.
            
        - **Case 'cloudflare':** Try `GET https://api.cloudflare.com/client/v4/user/tokens/verify`.