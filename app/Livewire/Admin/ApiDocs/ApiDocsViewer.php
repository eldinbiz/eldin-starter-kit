<?php

declare(strict_types=1);

namespace App\Livewire\Admin\ApiDocs;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class ApiDocsViewer extends Component
{
    #[Url(as: 'tag')]
    public string $selectedTag = 'all';

    #[Url(as: 'endpoint')]
    public string $selectedEndpointKey = 'auth_me';

    public string $selectedToken = '';
    public string $snippetLanguage = 'curl'; // 'curl' | 'js' | 'php' | 'python'

    // Interactive Request Runner Form
    public array $requestHeaders = [
        'Accept' => 'application/json',
        'X-Company-ID' => '',
    ];
    public array $queryParams = [];
    public array $pathParams = [];
    public string $requestBodyJson = '';

    // Response State
    public ?int $responseStatus = null;
    public ?float $responseDurationMs = null;
    public ?array $responseHeaders = null;
    public ?string $responseBody = null;

    public function mount(): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();
        if ($company) {
            $this->requestHeaders['X-Company-ID'] = (string) $company->id;
        }

        $this->selectEndpoint($this->selectedEndpointKey);
    }

    #[Computed]
    public function endpoints(): array
    {
        return [
            'auth_me' => [
                'tag' => 'Auth',
                'method' => 'GET',
                'path' => '/api/v1/me',
                'summary' => 'Get Authenticated User Profile',
                'description' => 'Returns profile data, global abilities, and company memberships for the authenticated token owner.',
                'ability' => 'ability:auth:me',
                'scope' => 'Platform / Global',
                'headers' => ['Accept' => 'application/json'],
                'pathParams' => [],
                'queryParams' => [],
                'hasBody' => false,
            ],
            'notifications_index' => [
                'tag' => 'Notifications',
                'method' => 'GET',
                'path' => '/api/v1/notifications',
                'summary' => 'List User Notifications',
                'description' => 'Returns latest in-app and system notifications for the authenticated user.',
                'ability' => 'ability:notifications:index',
                'scope' => 'User Level',
                'headers' => ['Accept' => 'application/json'],
                'pathParams' => [],
                'queryParams' => ['per_page' => '15'],
                'hasBody' => false,
            ],
            'users_index' => [
                'tag' => 'Users',
                'method' => 'GET',
                'path' => '/api/v1/users',
                'summary' => 'List All Users',
                'description' => 'Returns paginated list of all users across the platform (Platform Admin only).',
                'ability' => 'ability:users:index',
                'scope' => 'Platform Admin',
                'headers' => ['Accept' => 'application/json'],
                'pathParams' => [],
                'queryParams' => ['search' => '', 'per_page' => '15'],
                'hasBody' => false,
            ],
            'users_show' => [
                'tag' => 'Users',
                'method' => 'GET',
                'path' => '/api/v1/users/{user}',
                'summary' => 'Get User Details',
                'description' => 'Retrieves a single user resource with their company associations and roles.',
                'ability' => 'ability:users:show',
                'scope' => 'Platform Admin',
                'headers' => ['Accept' => 'application/json'],
                'pathParams' => ['user' => '1'],
                'queryParams' => [],
                'hasBody' => false,
            ],
            'roles_index' => [
                'tag' => 'Roles',
                'method' => 'GET',
                'path' => '/api/v1/roles',
                'summary' => 'List System Roles',
                'description' => 'Retrieves all defined security roles and their granular permission lists.',
                'ability' => 'ability:roles:index',
                'scope' => 'Platform Admin',
                'headers' => ['Accept' => 'application/json'],
                'pathParams' => [],
                'queryParams' => ['per_page' => '20'],
                'hasBody' => false,
            ],
            'companies_index' => [
                'tag' => 'Companies',
                'method' => 'GET',
                'path' => '/api/v1/companies',
                'summary' => 'List Companies & Tenants',
                'description' => 'Returns all company tenants registered on the platform.',
                'ability' => 'ability:companies:index',
                'scope' => 'Platform Admin',
                'headers' => ['Accept' => 'application/json'],
                'pathParams' => [],
                'queryParams' => [],
                'hasBody' => false,
            ],
            'companies_hierarchy' => [
                'tag' => 'Companies',
                'method' => 'GET',
                'path' => '/api/v1/companies/hierarchy',
                'summary' => 'Get Multi-Tenant Org Tree',
                'description' => 'Returns full corporate tree structure of parent and subsidiary companies.',
                'ability' => 'ability:companies:hierarchy',
                'scope' => 'Platform Admin',
                'headers' => ['Accept' => 'application/json'],
                'pathParams' => [],
                'queryParams' => [],
                'hasBody' => false,
            ],
            'audit_logs_index' => [
                'tag' => 'Audit Logs',
                'method' => 'GET',
                'path' => '/api/v1/audit-logs',
                'summary' => 'List Audit Trail History',
                'description' => 'Queries security audit logs and provenance records (Platform Admin only).',
                'ability' => 'ability:audit-logs:index',
                'scope' => 'Platform Admin',
                'headers' => ['Accept' => 'application/json'],
                'pathParams' => [],
                'queryParams' => ['per_page' => '15'],
                'hasBody' => false,
            ],
            'approvals_index' => [
                'tag' => 'Approvals',
                'method' => 'GET',
                'path' => '/api/v1/approvals',
                'summary' => 'List Approval Requests',
                'description' => 'Returns approval requests scoped to company provided in X-Company-ID header.',
                'ability' => 'ability:approvals:index',
                'scope' => 'Company Scoped (X-Company-ID)',
                'headers' => ['Accept' => 'application/json', 'X-Company-ID' => (string) (auth()->user()?->getActiveCompany()?->id ?? 1)],
                'pathParams' => [],
                'queryParams' => ['status' => 'pending'],
                'hasBody' => false,
            ],
        ];
    }

    #[Computed]
    public function filteredEndpoints(): array
    {
        if ($this->selectedTag === 'all') {
            return $this->endpoints;
        }

        return array_filter($this->endpoints, fn ($ep) => strtolower($ep['tag']) === strtolower($this->selectedTag));
    }

    #[Computed]
    public function currentEndpoint(): array
    {
        return $this->endpoints[$this->selectedEndpointKey] ?? $this->endpoints['auth_me'];
    }

    #[Computed]
    public function availableTokens(): array
    {
        /** @var User|null $user */
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        return $user->tokens()->pluck('name', 'id')->toArray();
    }

    public function selectEndpoint(string $key): void
    {
        if (isset($this->endpoints[$key])) {
            $this->selectedEndpointKey = $key;
            $ep = $this->endpoints[$key];
            $this->pathParams = $ep['pathParams'];
            $this->queryParams = $ep['queryParams'];
            $this->responseStatus = null;
            $this->responseBody = null;
        }
    }

    public function setTag(string $tag): void
    {
        $this->selectedTag = $tag;
        $filtered = $this->filteredEndpoints;
        if (! isset($filtered[$this->selectedEndpointKey])) {
            $firstKey = array_key_first($filtered);
            if ($firstKey) {
                $this->selectEndpoint($firstKey);
            }
        }
    }

    public function setSnippetLanguage(string $lang): void
    {
        $this->snippetLanguage = $lang;
    }

    public function generateSnippet(): string
    {
        $ep = $this->currentEndpoint;
        $url = url($this->buildResolvedPath());
        if (! empty($this->queryParams)) {
            $url .= '?' . http_build_query(array_filter($this->queryParams));
        }

        $token = $this->selectedToken ?: 'YOUR_SANCTUM_TOKEN_HERE';
        $companyId = $this->requestHeaders['X-Company-ID'] ?? '';

        return match ($this->snippetLanguage) {
            'js' => "fetch('{$url}', {\n  method: '{$ep['method']}',\n  headers: {\n    'Authorization': 'Bearer {$token}',\n    'Accept': 'application/json'" . ($companyId ? ",\n    'X-Company-ID': '{$companyId}'" : '') . "\n  }\n})\n.then(res => res.json())\n.then(data => console.log(data));",
            'php' => "\$client = new \\GuzzleHttp\\Client();\n\$response = \$client->request('{$ep['method']}', '{$url}', [\n    'headers' => [\n        'Authorization' => 'Bearer {$token}',\n        'Accept' => 'application/json'," . ($companyId ? "\n        'X-Company-ID' => '{$companyId}'," : '') . "\n    ],\n]);\n\$data = json_decode(\$response->getBody(), true);",
            'python' => "import requests\n\nheaders = {\n    'Authorization': 'Bearer {$token}',\n    'Accept': 'application/json'," . ($companyId ? "\n    'X-Company-ID': '{$companyId}'," : '') . "\n}\nresponse = requests.{$this->getLowerMethod()}('{$url}', headers=headers)\nprint(response.json())",
            default => "curl -X {$ep['method']} \"{$url}\" \\\n  -H \"Authorization: Bearer {$token}\" \\\n  -H \"Accept: application/json\"" . ($companyId ? " \\\n  -H \"X-Company-ID: {$companyId}\"" : ''),
        };
    }

    private function getLowerMethod(): string
    {
        return strtolower($this->currentEndpoint['method']);
    }

    private function buildResolvedPath(): string
    {
        $path = $this->currentEndpoint['path'];
        foreach ($this->pathParams as $param => $val) {
            $path = str_replace("{{$param}}", (string) $val, $path);
        }
        return $path;
    }

    public function executeRequest(): void
    {
        $ep = $this->currentEndpoint;
        $path = $this->buildResolvedPath();
        $startTime = microtime(true);

        // Build server headers
        $server = [
            'HTTP_ACCEPT' => 'application/json',
        ];
        if (! empty($this->requestHeaders['X-Company-ID'])) {
            $server['HTTP_X_COMPANY_ID'] = $this->requestHeaders['X-Company-ID'];
        }

        // Internal subrequest dispatch with acting user
        $request = Request::create(
            $path,
            $ep['method'],
            $this->queryParams,
            [],
            [],
            $server,
            $this->requestBodyJson ?: null
        );

        $user = auth()->user();
        if ($user) {
            $request->setUserResolver(fn () => $user);
        }

        try {
            $response = app()->handle($request);
            $this->responseDurationMs = round((microtime(true) - $startTime) * 1000, 2);
            $this->responseStatus = $response->getStatusCode();
            $this->responseHeaders = [
                'content-type' => $response->headers->get('content-type', 'application/json'),
            ];

            $content = $response->getContent();
            $decoded = json_decode($content, true);
            $this->responseBody = $decoded ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $content;
        } catch (\Throwable $e) {
            $this->responseStatus = 500;
            $this->responseDurationMs = round((microtime(true) - $startTime) * 1000, 2);
            $this->responseBody = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
        }
    }

    public function render()
    {
        return view('livewire.admin.api-docs.api-docs-viewer');
    }
}
