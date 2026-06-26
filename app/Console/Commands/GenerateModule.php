<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class GenerateModule extends Command
{
    protected $signature = 'generate:module {name}';
    protected $description = 'Generate a full modular structure under app/Modules/{Name}';

    protected Filesystem $fs;

    public function __construct(Filesystem $fs)
    {
        parent::__construct();
        $this->fs = $fs;
    }

    public function handle(): void
    {
        $name = $this->argument('name');
        $this->info("Generating module: {$name}");

        $this->generateFiles($name);
        $this->generateModel($name);
        $this->registerRouteInApiPhp($name);

        $this->info("✅ Module [{$name}] generated successfully.");
    }

    // ─────────────────────────────────────────────
    //  FILE GENERATION
    // ─────────────────────────────────────────────

    protected function generateFiles(string $name): void
    {
        $base = app_path("Modules/{$name}");

        $files = [
            // Controllers
            "{$base}/Controllers/{$name}Controller.php" => $this->stubController($name),

            // DTOs
            "{$base}/Dtos/{$name}Dto.php" => $this->stubDto($name),

            // Listeners
            "{$base}/Listeners/{$name}Listener.php" => $this->stubListener($name),

            // Repositories — interface + implementation
            "{$base}/Repositories/Interfaces/I{$name}Repository.php" => $this->stubRepositoryInterface($name),
            "{$base}/Repositories/Implementation/{$name}Repository.php" => $this->stubRepository($name),

            // Requests
            "{$base}/Requests/Store{$name}Request.php" => $this->stubStoreRequest($name),
            "{$base}/Requests/Update{$name}Request.php" => $this->stubUpdateRequest($name),

            // Resources
            "{$base}/Resources/{$name}Resource.php" => $this->stubResource($name),

            // Routes
            "{$base}/Routes/api.php" => $this->stubRoutes($name),

            // Services — interface + implementation
            "{$base}/Services/Interfaces/I{$name}Service.php" => $this->stubServiceInterface($name),
            "{$base}/Services/{$name}Service.php" => $this->stubService($name),
        ];

        foreach ($files as $path => $content) {
            $this->createFile($path, $content);
        }
    }

    protected function createFile(string $path, string $content): void
    {
        if ($this->fs->exists($path)) {
            $this->warn("  Already exists: {$path}");
            return;
        }

        $this->fs->ensureDirectoryExists(dirname($path));
        $this->fs->put($path, $content);
        $this->info("  Created: {$path}");
    }

    // ─────────────────────────────────────────────
    //  MODEL  (app/Models/{Name}.php)
    // ─────────────────────────────────────────────

    protected function generateModel(string $name): void
    {
        $path = app_path("Models/{$name}.php");
        $this->createFile($path, $this->stubModel($name));
    }

    // ─────────────────────────────────────────────
    //  ROUTE REGISTRATION  (routes/api.php)
    // ─────────────────────────────────────────────

    protected function registerRouteInApiPhp(string $name): void
    {
        $apiPhp = base_path('routes/api.php');

        if (!$this->fs->exists($apiPhp)) {
            $this->error('routes/api.php not found.');
            return;
        }

        $moduleRoutePath = "app/Modules/{$name}/Routes/api.php";
        $requireLine     = "require app_path('Modules/{$name}/Routes/api.php');";

        $content = $this->fs->get($apiPhp);

        if (str_contains($content, $requireLine)) {
            $this->warn("  routes/api.php already includes {$name} routes.");
            return;
        }

        // Append at the end of the file
        $content = rtrim($content) . "\n\n" . $requireLine . "\n";
        $this->fs->put($apiPhp, $content);
        $this->info("  Updated routes/api.php → included {$moduleRoutePath}");
    }

    // ─────────────────────────────────────────────
    //  STUBS
    // ─────────────────────────────────────────────

    protected function stubController(string $n): string
    {
        return <<<PHP
<?php

namespace App\Modules\\{$n}\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\\{$n}\Services\Interfaces\I{$n}Service;
use App\Modules\\{$n}\Requests\Store{$n}Request;
use App\Modules\\{$n}\Requests\Update{$n}Request;
use App\Modules\\{$n}\Resources\\{$n}Resource;
use Illuminate\Http\JsonResponse;

class {$n}Controller extends Controller
{
    public function __construct(private I{$n}Service \$service) {}

    public function index(): JsonResponse
    {
        return response()->json({$n}Resource::collection(\$this->service->getAll()));
    }

    public function show(int \$id): JsonResponse
    {
        return response()->json(new {$n}Resource(\$this->service->getById(\$id)));
    }

    public function store(Store{$n}Request \$request): JsonResponse
    {
        return response()->json(new {$n}Resource(\$this->service->create(\$request->validated())), 201);
    }

    public function update(Update{$n}Request \$request, int \$id): JsonResponse
    {
        return response()->json(new {$n}Resource(\$this->service->update(\$id, \$request->validated())));
    }

    public function destroy(int \$id): JsonResponse
    {
        \$this->service->delete(\$id);
        return response()->json(['message' => '{$n} deleted successfully.']);
    }
}
PHP;
    }

    protected function stubDto(string $n): string
    {
        return <<<PHP
<?php

namespace App\Modules\\{$n}\Dtos;

class {$n}Dto
{
    public function __construct(
        // public readonly string \$exampleField,
    ) {}

    public static function fromArray(array \$data): self
    {
        return new self(
            // \$data['example_field'],
        );
    }

    public function toArray(): array
    {
        return [
            // 'example_field' => \$this->exampleField,
        ];
    }
}
PHP;
    }

    protected function stubListener(string $n): string
    {
        return <<<PHP
<?php

namespace App\Modules\\{$n}\Listeners;

class {$n}Listener
{
    public function handle(\$event): void
    {
        // Handle event logic here
    }
}
PHP;
    }

    protected function stubRepositoryInterface(string $n): string
    {
        return <<<PHP
<?php

namespace App\Modules\\{$n}\Repositories\Interfaces;

interface I{$n}Repository
{
    public function getAll(int \$limit);
    public function findById(int \$id);
    public function create(array \$data);
    public function update(int \$id, array \$data);
    public function delete(int \$id): bool;
    public function search(string \$keyword, int \$limit);
}
PHP;
    }

    protected function stubRepository(string $n): string
    {
        return <<<PHP
<?php

namespace App\Modules\\{$n}\Repositories\Implementation;

use App\Models\\{$n};
use App\Modules\\{$n}\Repositories\Interfaces\I{$n}Repository;

class {$n}Repository implements I{$n}Repository
{
    public function getAll(int \$limit = 15)
    {
        return {$n}::paginate(\$limit);
    }

    public function findById(int \$id)
    {
        return {$n}::findOrFail(\$id);
    }

    public function create(array \$data)
    {
        return {$n}::create(\$data);
    }

    public function update(int \$id, array \$data)
    {
        \$model = \$this->findById(\$id);
        \$model->update(\$data);
        return \$model;
    }

    public function delete(int \$id): bool
    {
        return \$this->findById(\$id)->delete();
    }

    public function search(string \$keyword, int \$limit = 15)
    {
        return {$n}::whereAny([], 'LIKE', "%{\$keyword}%")->paginate(\$limit);
    }
}
PHP;
    }

    protected function stubStoreRequest(string $n): string
    {
        return <<<PHP
<?php

namespace App\Modules\\{$n}\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Store{$n}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Add validation rules
        ];
    }
}
PHP;
    }

    protected function stubUpdateRequest(string $n): string
    {
        return <<<PHP
<?php

namespace App\Modules\\{$n}\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Update{$n}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Add validation rules
        ];
    }
}
PHP;
    }

    protected function stubResource(string $n): string
    {
        return <<<PHP
<?php

namespace App\Modules\\{$n}\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {$n}Resource extends JsonResource
{
    public function toArray(Request \$request): array
    {
        return parent::toArray(\$request);
    }
}
PHP;
    }

    protected function stubRoutes(string $n): string
    {
        $lower = strtolower($n);
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use App\Modules\\{$n}\Controllers\\{$n}Controller;

Route::apiResource('{$lower}s', {$n}Controller::class);
PHP;
    }

    protected function stubServiceInterface(string $n): string
    {
        return <<<PHP
<?php

namespace App\Modules\\{$n}\Services\Interfaces;

interface I{$n}Service
{
    public function getAll();
    public function getById(int \$id);
    public function create(array \$data);
    public function update(int \$id, array \$data);
    public function delete(int \$id): bool;
}
PHP;
    }

    protected function stubService(string $n): string
    {
        return <<<PHP
<?php

namespace App\Modules\\{$n}\Services;

use App\Modules\\{$n}\Services\Interfaces\I{$n}Service;
use App\Modules\\{$n}\Repositories\Interfaces\I{$n}Repository;

class {$n}Service implements I{$n}Service
{
    public function __construct(private I{$n}Repository \$repo) {}

    public function getAll()
    {
        return \$this->repo->getAll(15);
    }

    public function getById(int \$id)
    {
        return \$this->repo->findById(\$id);
    }

    public function create(array \$data)
    {
        return \$this->repo->create(\$data);
    }

    public function update(int \$id, array \$data)
    {
        return \$this->repo->update(\$id, \$data);
    }

    public function delete(int \$id): bool
    {
        return \$this->repo->delete(\$id);
    }
}
PHP;
    }

    protected function stubModel(string $n): string
    {
        return <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {$n} extends Model
{
    use HasFactory;

    protected \$fillable = [];
}
PHP;
    }
}