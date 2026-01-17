 
**Purpose:** Detailed blueprint for the `PortainerClient` service and the "Test Connection" workflow. This module enables Muraqib to list containers, manage stacks, and verify credentials before storing them.

## 1. Backend Service Architecture

**Class:** `App\Services\Portainer\PortainerClient`

This service is responsible for all HTTP communication with the Portainer API. It must handle authentication injection, error handling, and data normalization.

### 1.1 Class Structure

```
namespace App\Services\Portainer;

use Illuminate\Support\Facades\Http;
use App\Settings\InfrastructureSettings;

class PortainerClient
{
    protected string $baseUrl;
    protected string $apiKey;

    // Constructor Dependency Injection
    public function __construct(InfrastructureSettings $settings)
    {
        // Default to stored settings
        $this->baseUrl = $settings->portainer_url;
        $this->apiKey = $settings->portainer_api_key;
    }

    /**
     * Runtime Override (For "Test Connection" before saving)
     */
    public function withCredentials(string $url, string $key): self
    {
        $this->baseUrl = $url;
        $this->apiKey = $key;
        return $this;
    }

    /**
     * The core request wrapper
     */
    protected function request()
    {
        return Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Accept' => 'application/json',
        ])->baseUrl($this->baseUrl);
    }
}
```

### 1.2 Core Methods

**A. Get Endpoints (Environments)**

- **Purpose:** Find the Docker ID (usually `1` for local, but can differ).
    
- **API:** `GET /api/endpoints`
    
- **Filter:** We usually want `Type=1` (Docker) or `Type=2` (Agent).
    
- **Return:** Collection of `[id, name, publicURL]`.
    

**B. Get Containers**

- **Purpose:** The main data source for the Infrastructure Dashboard.
    
- **API:** `GET /api/endpoints/{id}/docker/containers/json`
    
- **Params:** `all=1` (Show stopped containers too).
    
- **Return:** Raw JSON list of containers.
    

**C. Restart Container**

- **Purpose:** Action button on dashboard.
    
- **API:** `POST /api/endpoints/{id}/docker/containers/{containerId}/restart`
    

## 2. The "Test Connection" Workflow

You requested that the UI should **verify** credentials before saving and prevent saving invalid keys.

### 2.1 Backend: `ConnectionTesterController`

We need a dedicated endpoint that accepts _raw input_ (not yet saved to DB) and attempts a lightweight API call.

```
// Route: POST /api/test-connection/portainer
public function test(Request $request, PortainerClient $client)
{
    $request->validate([
        'url' => 'required|url',
        'key' => 'required|string',
    ]);

    try {
        // Inject the RAW input into the client (overriding DB settings)
        $response = $client->withCredentials($request->url, $request->key)
                           ->getEndpoints(); // Lightweight call

        return response()->json([
            'status' => 'success',
            'message' => 'Connection Successful',
            'data' => $response // Optional: show "Found 2 Environments"
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Connection Failed: ' . $e->getMessage()
        ], 400);
    }
}
```

### 2.2 Frontend: Settings Logic (Vue)

**File:** `resources/js/Pages/Settings/Partials/InfrastructureForm.vue`

State Management:

We need to track if the current input values have been verified.

```
const form = useForm({
    portainer_url: props.settings.portainer_url,
    portainer_api_key: props.settings.portainer_api_key,
});

// UI State
const verificationState = ref('idle'); // 'idle', 'testing', 'success', 'error'
const isDirty = computed(() => form.isDirty); // If user types, verification is void

// Watch for changes to reset verification
watch(() => form.portainer_api_key, () => {
    verificationState.value = 'idle'; 
});

const testConnection = () => {
    verificationState.value = 'testing';
    axios.post('/api/test-connection/portainer', {
        url: form.portainer_url,
        key: form.portainer_api_key
    })
    .then(() => verificationState.value = 'success')
    .catch(() => verificationState.value = 'error');
};
```

**Template Logic (The Guardrails):**

```
<!-- The Inputs -->
<input v-model="form.portainer_url" />
<SecretInput v-model="form.portainer_api_key" />

<!-- The Test Button -->
<button @click.prevent="testConnection" :disabled="verificationState === 'testing'">
    <span v-if="verificationState === 'testing'">Testing...</span>
    <span v-else>Test Connection</span>
</button>

<!-- Status Indicator -->
<div v-if="verificationState === 'success'" class="text-green-500">
    Connection Verified 🟢
</div>
<div v-if="verificationState === 'error'" class="text-red-500">
    Connection Failed 🔴
</div>

<!-- The Save Button (Conditional) -->
<!-- Only allow save if verified OR if data hasn't changed (clean form) -->
<PrimaryButton :disabled="verificationState !== 'success' && isDirty">
    Save Settings
</PrimaryButton>
```

## 3. Implementation Checklist

### Step 1: The Service

- [ ] Create `app/Services/Portainer/PortainerClient.php`.
    
- [ ] Add `withCredentials()` method for runtime overrides.
    
- [ ] Add `getEndpoints()` method using `Http` facade.
    

### Step 2: The Controller

- [ ] Create `ConnectionTesterController`.
    
- [ ] Implement the `test` method to catch exceptions (e.g., 401 Unauthorized, Connection Refused).
    

### Step 3: The UI

- [ ] Update `Settings/Index.vue`.
    
- [ ] Add the `verificationState` logic.
    
- [ ] **Crucial:** Ensure the "Save" button is grayed out (disabled) if the user has typed a new key but hasn't clicked "Test Connection" yet.
    

### Step 4: Verification

1. Go to Settings.
    
2. Type a fake URL `http://google.com`.
    
3. Click "Test". Expect Red Error. "Save" button should be disabled.
    
4. Type real Portainer URL and Key.
    
5. Click "Test". Expect Green Success. "Save" button enables.
    
6. Click Save.
    
7. Check Database: `select * from settings;` (Value should be encrypted).
    
8. **Tinker Test:** `app(PortainerClient::class)->getEndpoints()` should now work using the saved DB credentials.