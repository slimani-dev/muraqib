# Cloudflare Integration & Tunnel Wizard Specification

Module: Network & Access

Purpose: To automate the exposure of local services (Muraqib, Netdata, Portainer) to the public internet securely using Cloudflare Tunnels, without opening firewall ports.

## 1. Database Schema: "The Vault"

We need a secure place to store the Cloudflare credentials and the state of the tunnel.

Table: infrastructure_secrets (Polymorphic or Key-Value) or specific columns in settings.

Recommendation: Add specific encrypted columns to settings or a dedicated cloudflare_configs table if supporting multiple accounts.

Schema: cloudflare_configs

| Column | Type | Description |

| :--- | :--- | :--- |

| id | bigint | Primary Key |

| account_id | string | Cloudflare Account ID (Public) |

| api_token | text | Encrypted. The User's API Token. |

| tunnel_id | uuid | The ID of the created tunnel. |

| tunnel_name | string | e.g., "muraqib-home-server" |

| tunnel_token | text | Encrypted. The token used by the cloudflared agent to connect. |

| is_active | boolean | Status of the tunnel connection. |

| domain_zone_id | string | The Zone ID of the main domain (e.g., example.com). |

## 2. The Wizard Workflow

This wizard runs immediately after Muraqib is installed or when the user navigates to "Remote Access".

### Step 1: Authentication

- **User Action:** Enters `Cloudflare Account ID` and `API Token`.
    
- **System Action:** Validates credentials by calling `GET /user/tokens/verify`.
    
- **UI Hint:** Provides a direct link to create the token with the correct permissions template.
    

### Step 2: Tunnel Creation

- **System Action:**
    
    1. Checks if a tunnel named `muraqib-node` exists.
        
    2. If not, calls `POST /accounts/{id}/tunnels` to create it.
        
    3. Retrieves the `tunnel_token`.
        
    4. Saves `tunnel_id` and `tunnel_token` to the Vault.
        

### Step 3: Agent Installation (The "Script" Phase)

The tunnel exists in the cloud, but the local server needs to connect to it. Muraqib offers 3 methods:

Option A: Docker (Recommended for Portainer Users)

Muraqib uses the Portainer Client (from previous specs) to deploy a cloudflared container automatically.

```
image: cloudflare/cloudflared:latest
command: tunnel run
environment:
  - TUNNEL_TOKEN={stored_tunnel_token}
restart: always
```

**Option B: Direct Linux Install (Copy-Paste)**

```
curl -L --output cloudflared.deb [https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb](https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb) && 
sudo dpkg -i cloudflared.deb && 
sudo cloudflared service install {stored_tunnel_token}
```

Option C: Proxmox LXC (For Advanced Users)

"Run this in your Proxmox Shell to create a dedicated Gateway LXC:"

(Reference to a helper script that installs cloudflared in a new container)

### Step 4: Domain Routing (Ingress Rules)

- **User Action:** Selects a domain (fetched from Cloudflare Zones) and maps subdomains.
    
- **UI Form:**
    
    - `muraqib.example.com` -> `http://localhost:80`
        
    - `netdata.example.com` -> `http://192.168.1.199:19999`
        
    - `portainer.example.com` -> `http://192.168.1.5:9000`
        
- **System Action:**
    
    1. Updates Tunnel Configuration (API) to route traffic.
        
    2. Creates DNS CNAME records (`muraqib` -> `tunnel_uuid.cfargotunnel.com`).
        

## 3. Backend Service: `CloudflareService`

**Dependencies:** `guzzlehttp/guzzle`

```
namespace App\Services\Network;

use Illuminate\Support\Facades\Http;
use App\Models\CloudflareConfig;

class CloudflareService
{
    protected string $baseUrl = '[https://api.cloudflare.com/client/v4](https://api.cloudflare.com/client/v4)';

    /**
     * 1. Validate Token
     */
    public function verifyToken(string $token): bool
    {
        $response = Http::withToken($token)->get("$this->baseUrl/user/tokens/verify");
        return $response->json('result.status') === 'active';
    }

    /**
     * 2. Create or Get Tunnel
     */
    public function findOrCreateTunnel(CloudflareConfig $config, string $name = 'muraqib-node')
    {
        // Check existing
        $list = Http::withToken($config->api_token)
            ->get("$this->baseUrl/accounts/{$config->account_id}/tunnels?is_deleted=false");
        
        $existing = collect($list->json('result'))->firstWhere('name', $name);
        
        if ($existing) {
            return $existing;
        }

        // Create new
        $response = Http::withToken($config->api_token)
            ->post("$this->baseUrl/accounts/{$config->account_id}/tunnels", [
                'name' => $name,
                'config_src' => 'cloudflare', // CRITICAL: Enables remote management
            ]);

        return $response->json('result');
    }

    /**
     * 3. Get Tunnel Token (Required for installation)
     */
    public function getTunnelToken(CloudflareConfig $config)
    {
        // The token is not returned in listing, we must fetch it specifically
        // Or construct it? Actually, for 'remote' tunnels, we fetch the token via:
        // GET /accounts/:account_identifier/tunnels/:tunnel_id/token
        
        $response = Http::withToken($config->api_token)
            ->get("$this->baseUrl/accounts/{$config->account_id}/tunnels/{$config->tunnel_id}/token");
            
        return $response->json('result');
    }

    /**
     * 4. Update Ingress Rules (Map Domains to Local Ports)
     */
    public function updateIngressRules(CloudflareConfig $config, array $services)
    {
        // Services array format: [['hostname' => 'sub.domain.com', 'service' => 'http://localhost:80']]
        
        $ingress = [];
        foreach ($services as $svc) {
            $ingress[] = [
                'hostname' => $svc['hostname'],
                'service' => $svc['service'], // e.g., [http://192.168.1.5:9000](http://192.168.1.5:9000)
            ];
        }
        
        // Catch-all 404 rule (Required by Cloudflare)
        $ingress[] = ['service' => 'http_status:404'];

        $response = Http::withToken($config->api_token)
            ->put("$this->baseUrl/accounts/{$config->account_id}/tunnels/{$config->tunnel_id}/configurations", [
                'config' => [
                    'ingress' => $ingress
                ]
            ]);

        return $response->successful();
    }

    /**
     * 5. Create DNS Record
     */
    public function createDnsRecord(CloudflareConfig $config, string $zoneId, string $subdomain)
    {
        return Http::withToken($config->api_token)
            ->post("$this->baseUrl/zones/{$zoneId}/dns_records", [
                'type' => 'CNAME',
                'name' => $subdomain, // e.g., "netdata"
                'content' => "{$config->tunnel_id}.cfargotunnel.com",
                'proxied' => true,
            ]);
    }
}
```

## 4. Implementation Steps (Wizard Logic)

### Phase 1: Installation & Connection

1. User enters credentials. Backend validates.
    
2. Backend creates Tunnel ID `xxxxx-xxxx-xxxx`.
    
3. Backend fetches Tunnel Token `eyJh...`.
    
4. **UI displays Install Options:**
    
    **Option 1: "Install via Portainer" (Button)**
    
    - Since Muraqib connects to Portainer, we trigger a Stack Deployment via the PortainerClient service.
        
    - Stack Name: `cloudflared`
        
    - Content: A simple Docker Compose using the token.
        
    - _Result:_ Zero touch installation.
        
    
    **Option 2: "Manual Install" (Code Block)**
    
    - Display the `curl` or `docker run` command populated with the token.
        

### Phase 2: Configuration (The "App Store" feel)

Once the tunnel is online (Backend polls connection status):

1. **Fetch Zones:** Muraqib lists user's domains (e.g., `myserver.com`).
    
2. **Suggest Subdomains:**
    
    - "We detected **Netdata** running on port 19999." -> Suggest `netdata.myserver.com`.
        
    - "We detected **Portainer**." -> Suggest `portainer.myserver.com`.
        
    - "Expose **Muraqib**?" -> Suggest `dashboard.myserver.com`.
        
3. **Apply:** User clicks "Publish".
    
4. **Backend:**
    
    - Updates Tunnel Ingress (The Routing).
        
    - Creates DNS CNAMEs (The Addressing).
        

## 5. Security Note

- **Token Storage:** The `tunnel_token` allows anyone to host content on that specific tunnel. It must be encrypted in the database.
    
- **Zero Trust Access (Optional Upgrade):** Later, you can add an "Authentication" step to the wizard, where you use the Cloudflare Access API to put a "Login with Google/Email" screen in front of these subdomains.