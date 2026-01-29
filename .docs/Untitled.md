# Implementation Plan: Cloudflare Transform Rules (Linked Services)

## 1. Database Schema

We will modify the schema to support "Linking" services.

Table: cloudflare_transform_rules (The Manager)

| Column | Type | Description |

| :--- | :--- | :--- |

| id | ulid | Primary Key |

| name | string | e.g., "Main Dashboard Injection" |

| cloudflare_id | fk | The Cloudflare Account to deploy to |

| service_token_id | string | The "Skeleton Key" ID (Muraqib -> Cloudflare) |

| client_id | string | Public ID for the Frontend |

| client_secret | text | Encrypted Secret for the Frontend |

| rule_ids | json | Array of deployed Rule IDs (to track cleanup) |

Table: cloudflare_transform_ruleables (The Links)

| Column | Type | Description |

| :--- | :--- | :--- |

| cloudflare_transform_rule_id | fk | Parent Rule |

| transformable_type | string | App\Models\Netdata or Portainer |

| transformable_id | ulid | ID of the specific service |

## 2. Logic: The "Smart Grouper" Service

Since Cloudflare limits rules (10 on free plan), we cannot create one rule per service. We must group them.

**The Algorithm:**

1. Fetch all linked services (Netdata, Portainer).
    
2. Extract their required Headers and Domain.
    
3. **Group by Credentials:**
    
    - If `Netdata A` and `Netdata B` use the _same_ Service Token -> Group together.
        
    - If `Portainer` uses a unique API Key -> Separate Group.
        
4. **Generate Rules:**
    
    - Create one Cloudflare Rule per Group.
        
    - `Expression`: `http.host in {"n1.home.com", "n2.home.com"}`
        
    - `Headers`: Set `CF-Access-Client-Id` = `Token123`.
        

## 3. Frontend Integration

Remains the same: The Frontend uses `client_id` / `client_secret` (The Skeleton Key) to authenticate _with Cloudflare_. Cloudflare then performs the injection (The Key Swap) before the request hits the origin.