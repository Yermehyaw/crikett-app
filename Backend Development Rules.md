# Backend Development Rules



This document outlines the coding standards, patterns, and rules for developing Laravel backend applications using the Repository-Service pattern.



## Table of Contents



1. [Architecture Patterns](#architecture-patterns)

2. [Repository-Service Pattern](#repository-service-pattern)

3. [Route Structure](#route-structure)

4. [Controller Pattern](#controller-pattern)

5. [Form Request Pattern](#form-request-pattern)

6. [Service Implementation](#service-implementation)

7. [Repository Implementation](#repository-implementation)

8. [Resource Classes](#resource-classes)

9. [Model Patterns](#model-patterns)

10. [Migration Patterns](#migration-patterns)

11. [Helper Classes](#helper-classes)

12. [Testing Patterns](#testing-patterns)

13. [Code Quality Rules](#code-quality-rules)



---



## Architecture Patterns



### Separation of Concerns



- **Controllers**: Handle HTTP requests/responses only. No business logic.

- **Services**: Contain all business logic. Use repositories for data access.

- **Repositories**: Handle all database operations. No business logic.

- **Form Requests**: Handle validation only.

- **Resources**: Transform models for API responses.



### Directory Structure



```

app/

├── Http/

│   ├── Controllers/         # HTTP request handlers (organize by feature/domain)

│   ├── Requests/            # Form validation classes

│   ├── Resources/           # API response transformers

│   └── Middleware/          # Custom middleware

├── Services/                # Business logic layer (organize by feature/domain)

├── Repositories/            # Data access layer (organize by feature/domain)

├── Helpers/                 # Reusable helper classes

└── Models/                  # Eloquent models

```

**Note:** You can organize Controllers, Services, and Repositories by feature/domain. For apps with user/admin separation, use subdirectories like `App/` and `Admin/`.



---



## Repository-Service Pattern



### Overview



We use the **Yaza Laravel Repository Service** pattern with automatic binding via `RepositoryAutoBindProvider`.



### Naming Convention



- **Interface**: `{Name}Repository.php` and `{Name}Service.php`

- **Implementation**: `{Name}RepositoryImplement.php` and `{Name}ServiceImplement.php`



### Auto-Binding



The `RepositoryAutoBindProvider` automatically binds:

- `App\Repositories\{Path}\{Name}Repository` → `App\Repositories\{Path}\{Name}RepositoryImplement`

- `App\Services\{Path}\{Name}Service` → `App\Services\{Path}\{Name}ServiceImplement`



**No manual binding required in service providers.**



### Interface Structure



**Repository Interface:**

```php

<?php



namespace App\Repositories\User;



use LaravelEasyRepository\Repository;



interface UserRepository extends Repository

{

    public function findUserByEmail($email);

    public function createUser($data);

    public function updateLastLogin($userId);

}

```



**Service Interface:**

```php

<?php



namespace App\Services\Auth;



use LaravelEasyRepository\BaseService;



interface AuthService extends BaseService

{

    public function register($request);

    public function login($request);

    public function logout($request);

    public function profile($request);

}

```



### Rules



1. **No type hints in interface methods** - Use `$request`, `$data`, etc. without type declarations

2. **No default values in interface methods**

3. **Interfaces extend `LaravelEasyRepository\Repository` or `LaravelEasyRepository\BaseService`**

4. **All methods must be public**



---



## Route Structure



### Route Files



- `routes/web.php` - Web routes (Inertia pages)

- `routes/api.php` - API routes (prefix: `/api`)

- `routes/{role}.php` - Role-based routes (optional, only when using role/permission based setup)



### Route Grouping



**API Routes Example (standard grouping):**

```php

Route::post('/register', [AuthController::class, 'register'])->name('register');



Route::prefix('sessions')->name('sessions.')->group(function () {

    Route::post('/', [AuthController::class, 'login'])->name('login');

    Route::delete('/', [AuthController::class, 'logout'])

        ->middleware('auth:sanctum')

        ->name('logout');

});



Route::middleware('auth:sanctum')->group(function () {

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');

    Route::apiResource('products', ProductController::class);

});

```

**Role-Based Grouping (optional, only when using role/permission based setup):**

```php

// routes/owner.php

Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {

    Route::get('/', 'index')->name('index');

    Route::post('/', 'store')->name('store');

    Route::get('/{productId}', 'show')->name('show');

    Route::put('/{productId}', 'update')->name('update');

});

```



### Route Naming



- Use descriptive route names: `sessions.login`, `sessions.logout`, `profile`

- Group related routes with prefixes: `sessions.*`

- Always name routes for easy reference



### Middleware



- `auth:sanctum` - Sanctum authentication

- `auth` - Session-based authentication (for web routes)

- Custom middleware can be defined in `bootstrap/app.php`



---



## Controller Pattern



### Structure



```php

<?php



namespace App\Http\Controllers\Api;



use App\Http\Controllers\Controller;

use App\Http\Requests\Auth\LoginRequest;

use App\Services\Auth\AuthService;

use Illuminate\Http\JsonResponse;



class AuthController extends Controller

{

    public function __construct(

        protected AuthService $authService

    ) {}



    /**

     * User Login

     *

     * Authenticate a user with email and password. Returns authentication token and user details.

     *

     * @group User Management

     * @subgroup Authentication

     *

     * @bodyParam email string required The user's email address. Example: john.doe@example.com

     * @bodyParam password string required The user's password. Example: password123

     *

     * @response 200 scenario=Success {

     *     "code": 200,

     *     "message": "Login successful",

     *     "data": {

     *         "user": {...},

     *         "token": "2|...",

     *         "roles": ["USER"],

     *         "permissions": ["PROPERTIES_VIEW"]

     *     }

     * }

     * @response 401 scenario=InvalidCredentials {

     *     "code": 401,

     *     "message": "Invalid credentials"

     * }

     * @response 422 scenario=ValidationError {

     *     "message": "The given data was invalid.",

     *     "errors": {...}

     * }

     * @response 500 scenario=ServerError {

     *     "code": 500,

     *     "message": "An error occurred during login",

     *     "error": "Error message details"

     * }

     */

    public function login(LoginRequest $request): JsonResponse

    {

        return $this->authService->login($request)->toJson();

    }

}

```



### Rules



1. **Inject services via constructor** - Use dependency injection

2. **Use Form Requests for validation** - Never validate in controllers

3. **Return service response directly** - Use `->toJson()` method (required for Scribe documentation)

4. **Keep controllers thin** - Delegate all logic to services

5. **One controller per resource area** - `AuthController`, `PropertyController`, etc.

6. **Document all endpoints with Scribe** - Include request parameters and all response scenarios

7. **No inline comments** - Never use inline comments in controllers

8. **Organize controllers by feature** - Group by domain/feature (e.g., `Api/`, `Admin/`, or by resource)



### Scribe Documentation Requirements



**Every controller method must include:**



1. **Method Description** - Clear description of what the endpoint does

2. **@group** - Group name for organizing endpoints (e.g., "User Management", "Admin Management")

3. **@subgroup** - Subgroup for further organization (e.g., "Authentication", "Profile")

4. **@bodyParam** - All request parameters from Form Request validation rules

   - Format: `@bodyParam field_name type required/optional Description. Example: example_value`

5. **@authenticated** - Add this tag if endpoint requires authentication

6. **@response** - Document ALL possible response scenarios from the service:

   - Success responses (200, 201, etc.)

   - Error responses (401, 403, 404, 422, 500)

   - Include full response structure matching Resource classes

   - Use `scenario=ScenarioName` to name each response



**Response Documentation Pattern:**



```php

/**

 * @response 200 scenario=Success {

 *     "code": 200,

 *     "message": "Operation successful",

 *     "data": {

 *         // Full structure from Resource class

 *     }

 * }

 * @response 401 scenario=Unauthenticated {

 *     "code": 401,

 *     "message": "Authentication required"

 * }

 * @response 422 scenario=ValidationError {

 *     "message": "The given data was invalid.",

 *     "errors": {

 *         "field": ["Error message"]

 *     }

 * }

 * @response 500 scenario=ServerError {

 *     "code": 500,

 *     "message": "An error occurred",

 *     "error": "Error message details"

 * }

 */

```



**Important Notes:**



- **Check service implementation** - Review all `setCode()` calls to identify all possible response scenarios

- **Match Resource structure** - Response data structure must match exactly what Resource classes return

- **Include all scenarios** - Document every possible response code the service can return

- **Use actual field names** - Match field names from Form Requests and Resources exactly

- **Include examples** - Provide realistic example values for all parameters



### Hybrid API/Inertia Controllers (Optional - Only When Requested)



If your application uses Inertia.js and needs to serve both API and Inertia responses from the same controller, use this pattern:



```php

public function index(Request $request)

{

    if ($request->expectsJson() || $request->is('api/*')) {

        return $this->productService->getAllProducts($request)->toJson();

    }



    return Inertia::render('products/index', [

        'products' => json_decode($this->productService->getAllProducts($request)->toJson()->getContent(), true),

        'cart' => json_decode($this->cartService->getCartForUser($request)->toJson()->getContent(), true),

        'categories' => json_decode($this->productCategoryService->getAllCategories()->toJson()->getContent(), true),

    ]);

}

```



**Rules for Hybrid Controllers:**



1. **Check for JSON requests** - Use `$request->expectsJson()` or `$request->is('api/*')`

2. **Return service response for API** - Use `->toJson()` method directly

3. **Decode JSON for Inertia props** - Convert service responses to arrays using `json_decode()`

4. **Reuse service logic** - Same service methods for both API and Inertia responses

5. **Keep DRY** - Don't duplicate business logic between API and web controllers



---



## Form Request Pattern



### Location



- Organize by feature/domain: `app/Http/Requests/{Feature}/{Name}.php`

- Or by user type (if applicable): `app/Http/Requests/App/{Feature}/{Name}.php` and `app/Http/Requests/Admin/{Feature}/{Name}.php`



### Structure



```php

<?php



namespace App\Http\Requests\Auth;



use Illuminate\Foundation\Http\FormRequest;



class LoginRequest extends FormRequest

{

    public function authorize(): bool

    {

        return true;

    }



    public function rules(): array

    {

        return [

            'email' => ['required', 'string', 'email'],

            'password' => ['required', 'string'],

        ];

    }



    public function messages(): array

    {

        return [

            'email.required' => 'The email address is required.',

            'email.email' => 'Please provide a valid email address.',

            'password.required' => 'The password is required.',

        ];

    }

}

```



### Rules



1. **Use array syntax for rules** - `['required', 'email']` not `'required|email'`

2. **Keep validation simple** - Complex validation in services if needed

3. **Name files after the action with "Request" suffix** - `LoginRequest.php`, `RegisterRequest.php`, `StoreProductRequest.php`, `UpdateProductRequest.php`

4. **Add custom messages** - Always implement `messages()` method with user-friendly error messages for all validation rules

5. **Organize by feature** - Organize by domain/feature (e.g., `Auth/`, `Product/`, `Order/`)



---



## Service Implementation



### Structure



```php

<?php



namespace App\Services\Auth;



use App\Enums\ResponseCode;

use App\Http\Resources\UserResource;

use App\Repositories\User\UserRepository;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Hash;

use LaravelEasyRepository\ServiceApi;



class AuthServiceImplement extends ServiceApi implements AuthService

{

    /**

     * Initialize the service with user repository dependency.

     *

     * @param  UserRepository  $userRepository  The user repository instance

     */

    public function __construct(

        protected UserRepository $userRepository

    ) {}



    /**

     * Authenticate a user with email and password.

     *

     * Validates user credentials, checks account status, revokes existing tokens,

     * and generates a new authentication token for API access.

     *

     * @param  mixed  $request  The login request containing validated email and password

     * @return AuthServiceImplement Returns service response with user data and access token on success, or error on failure

     */

    public function login($request): AuthServiceImplement

    {

        try {

            DB::beginTransaction();



            $user = $this->userRepository->findByEmail($request->email);



            if (! $user || ! Hash::check($request->password, $user->password)) {

                DB::rollBack();



                return $this->setCode(ResponseCode::UNAUTHORIZED->value)

                    ->setMessage('The provided credentials are incorrect.');

            }



            if ($user->status !== 'ACTIVE') {

                DB::rollBack();



                return $this->setCode(ResponseCode::FORBIDDEN->value)

                    ->setMessage('Your account is not active. Please contact support.');

            }



            $user->tokens()->delete();



            $token = $user->createToken('auth_token')->plainTextToken;



            DB::commit();



            return $this->setCode(ResponseCode::SUCCESS->value)

                ->setMessage('User authenticated successfully')

                ->setData([

                    'user' => new UserResource($user),

                    'token' => $token,

                ]);

        } catch (\Exception $e) {

            DB::rollBack();



            return $this->setCode(ResponseCode::SERVER_ERROR->value)

                ->setMessage('An error occurred during authentication')

                ->setError($e->getMessage());

        }

    }

}

```



### Rules



1. **Extend `ServiceApi`** - Provides `setCode()`, `setMessage()`, `setData()`, `toResponse()`

2. **Inject repositories via constructor** - Never instantiate repositories directly

3. **Use try-catch blocks** - Always handle exceptions, including `AccessDeniedHttpException` for permissions

4. **Return service instance** - Chain methods: `$this->setCode()->setMessage()->setData()`

5. **Use resources for responses** - Transform models via Resource classes

6. **No database operations** - All DB operations go through repositories

7. **Refresh models after updates** - Use `$model->refresh()` if needed

8. **Use ResponseCode enum** - Never hardcode status codes. Use `ResponseCode::SUCCESS->value` instead of `200`

9. **setData must always be an array** - When using `setData()`, always pass an array

10. **Validate permissions** - Use `helpers()->permissionHelper()->validatePermission()` at the start of each service method

11. **Add docblocks to all methods** - Every public method and constructor must have comprehensive PHPDoc blocks

12. **No inline comments** - Never use inline comments. Use docblocks instead

13. **Organize by feature** - Organize by domain/feature (e.g., `Auth/`, `Product/`, `Order/`)



### Response Methods



- `setCode(int $code)` - HTTP status code (use `ResponseCode` enum values)

- `setMessage(string $message)` - Response message

- `setData(array $data)` - Response data (must be an array)

- `setError(string $error)` - Error message

- `toResponse()` - Convert to JsonResponse





### ResponseCode Enum Usage



**Available Response Codes:**



```php

ResponseCode::CREATED = 201

ResponseCode::SUCCESS = 200

ResponseCode::BAD_REQUEST = 400

ResponseCode::UNAUTHORIZED = 401

ResponseCode::FORBIDDEN = 403

ResponseCode::NOT_FOUND = 404

ResponseCode::VALIDATION_ERROR = 422

ResponseCode::SERVER_ERROR = 500

```



**✅ CORRECT:**



```php

use App\Enums\ResponseCode;



return $this->setCode(ResponseCode::SUCCESS->value)

    ->setMessage('Operation successful')

    ->setData(['key' => 'value']);



return $this->setCode(ResponseCode::UNAUTHORIZED->value)

    ->setMessage('Invalid credentials');



return $this->setCode(ResponseCode::SERVER_ERROR->value)

    ->setMessage('An error occurred')

    ->setError($e->getMessage());

```



**❌ WRONG:**

```php

return $this->setCode(200)->setMessage('Success');

return $this->setCode(401)->setMessage('Unauthorized');

return $this->setCode(500)->setMessage('Error');

```



---



## Repository Implementation



### Structure



```php

<?php



namespace App\Repositories\App\User;



use App\Models\User;

use LaravelEasyRepository\Implementations\Eloquent;



class UserRepositoryImplement extends Eloquent implements UserRepository

{

    /**

     * Initialize the repository with User model.

     *

     * @param  User  $model  The User model instance

     */

    public function __construct(User $model)

    {

        $this->model = $model;

    }



    /**

     * Find user by email address.

     *

     * Retrieves a user with the specified email address.

     * Returns null if user is not found.

     *

     * @param  string  $email  The user's email address

     * @return User|null Returns the User model instance or null if not found

     */

    public function findByEmail(string $email): ?User

    {

        return $this->model->where('email', $email)->first();

    }



    /**

     * Find user by ID.

     *

     * Retrieves a user with the specified ID.

     * Returns null if user is not found.

     *

     * @param  string|int  $id  The user's ID

     * @return User|null Returns the User model instance or null if not found

     */

    public function findById(string|int $id): ?User

    {

        return $this->model->find($id);

    }

}

```



### Rules



1. **Extend `RepositoryImplement`** - Base repository class (or `Eloquent` from LaravelEasyRepository)

2. **Implement corresponding interface** - Must match interface methods

3. **Eager load relationships** - Use `with()` to prevent N+1 queries

4. **Hash passwords in repository** - Never in service or controller

5. **Filter unnecessary data** - Remove `password_confirmation`, etc.

6. **Return models, not arrays** - Services handle transformation

7. **Use query builder methods** - `where()`, `with()`, `first()`, etc.

8. **Use helpers for pagination and filtering** - Use `helpers()->queryableHelper()->fetchWithFilters()` for common list queries

9. **Sync relationships properly** - Use `sync()` for many-to-many relationships, handle IDs correctly

10. **Add docblocks to all methods** - Every public method and constructor must have comprehensive PHPDoc blocks

11. **No inline comments** - Never use inline comments. Use docblocks instead

12. **Organize by feature** - Organize by domain/feature (e.g., `User/`, `Product/`, `Order/`)



### Common Patterns



**Finding with relationships:**

```php

return Model::where('field', $value)

    ->with(['relation1', 'relation2'])

    ->first();

```



**Creating with role assignment:**

```php

$model = Model::create($data);

$model->setRole(RoleEnum::ROLE->name);

return $model->load(['relations']);

```



---



## Resource Classes



### Structure



```php

<?php



namespace App\Http\Resources;



use Illuminate\Http\Request;

use Illuminate\Http\Resources\Json\JsonResource;



class UserResource extends JsonResource

{

    public function toArray(Request $request): array

    {

        $role = $this->roles->first();



        return [

            'id' => $this->id ?? 'NA',

            'first_name' => $this->first_name ?? 'NA',

            'last_name' => $this->last_name ?? 'NA',

            'email' => $this->email ?? 'NA',

            'role' => $role ? [

                'name' => $role->name,

            ] : null,

            'agent' => $this->whenLoaded('agent', new AgentResource($this->agent)),

            'landlord' => $this->whenLoaded('landlord', new LandlordResource($this->landlord)),

        ];

    }

}

```



### Rules



1. **Use `whenLoaded()` for relationships** - Prevents N+1 queries

2. **Access relationships as properties** - `$this->roles->first()` not `$this->roles()->first()`

3. **Conditionally include data** - Only include if relationship is loaded

4. **Nest resources for relationships** - Use `new RelatedResource($this->relation)`

5. **Transform data appropriately** - Format dates, enums, etc.

6. **Keep in `Http/Resources/`** - Single location for all resources



---



## Model Patterns



### Structure



```php

<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use Spatie\Permission\Traits\HasRoles;



class User extends Authenticatable

{

    use HasFactory, HasUuids, HasRoles;



    protected $keyType = 'string';

    public $incrementing = false;



    protected $fillable = [

        'first_name',

        'last_name',

        'email',

        'password',

    ];



    protected $casts = [

        'email_verified_at' => 'datetime',

        'password' => 'hashed',

        'date_of_birth' => 'date',

    ];



    public function getFullNameAttribute(): string

    {

        return trim("{$this->first_name} {$this->last_name}");

    }



    public function setRole(string $roleName): void

    {

        if ($this->roles()->exists()) {

            $this->roles()->detach();

        }

        $this->assignRole($roleName);

    }



    public function getAllPermissionNames(): array

    {

        return $this->getAllPermissions()->pluck('name')->toArray();

    }

}

```



### Rules



1. **Use `HasUuids` trait** - All models should use UUID primary keys (unless explicitly changed by user)

2. **Set `$keyType = 'string'` and `$incrementing = false`** - For UUID primary keys

3. **Use `first_name` and `last_name`** - Never use single `name` field

4. **Use `SoftDeletes` trait** - Optional/conditional, use for models that should support soft deletion

5. **Define relationships** - `hasOne()`, `hasMany()`, `belongsTo()`, `belongsToMany()` (always use `sync()` for many-to-many updates)

6. **Add accessors for computed fields** - `getFullNameAttribute()`, etc.

7. **Implement `setRole()` and `getAllPermissionNames()`** - Optional, only when using role and permission based setup

8. **Use `boot()` method for auto-generation** - For auto-generating values like SKU, slugs, etc.

9. **Cast fields appropriately** - Dates, JSON, booleans, integers, enums, etc.

10. **Add helper methods** - Business logic methods like `hasPermission()`, `isLowStock()`, etc.

11. **No inline comments** - Keep code clean and self-documenting



### UUID Generation



**✅ CORRECT:**

```php

use Illuminate\Database\Eloquent\Concerns\HasUuids;



class User extends Model

{

    use HasUuids;

}

```



**❌ WRONG:**

```php

protected static function boot()

{

    parent::boot();

    static::creating(function ($model) {

        $model->id = (string) Str::uuid();

    });

}

```



### Auto-Generating Values with boot()



Use the `boot()` method to auto-generate field values when creating models:



```php

use Illuminate\Support\Str;



class Product extends Model

{

    protected static function boot(): void

    {

        parent::boot();



        static::creating(function ($product) {

            $product->sku = static::generateUniqueSku($product->name);

        });

    }



    protected static function generateUniqueSku(string $name): string

    {

        $nameParts = explode(' ', $name);

        $prefix = '';



        if (count($nameParts) >= 2) {

            $prefix = strtoupper(substr($nameParts[0], 0, 3).substr($nameParts[1], 0, 2));

        } else {

            $prefix = strtoupper(substr($name, 0, 5));

        }



        $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix);



        do {

            $random = strtoupper(Str::random(6));

            $sku = $prefix.'-'.$random;

        } while (static::where('sku', $sku)->exists());



        return $sku;

    }

}

```



**Rules:**

1. **Always call `parent::boot()`** - First line in `boot()` method

2. **Use `static::creating()`** - For auto-generating values on model creation

3. **Make generation methods protected static** - Name them clearly like `generateUniqueSku()`

4. **Ensure uniqueness** - Use `do-while` loops to check for existing values

5. **Keep logic in model** - Don't generate values in repositories or services



### Business Logic Helper Methods



Add helper methods to models for common business logic checks:



```php

class Product extends Model

{

    /**

     * Check if the product is low in stock.

     */

    public function isLowStock(): bool

    {

        return $this->stock_quantity <= $this->minimum_stock;

    }

}



class User extends Authenticatable

{

    /**

     * Check if user has a permission

     *

     * @param  string|PermissionEnum  $permission  name or PermissionEnum case

     */

    public function hasPermission(string|PermissionEnum $permission): bool

    {

        $permissionName = $permission instanceof PermissionEnum ? $permission->name : $permission;



        return $this->hasPermissionTo($permissionName);

    }

}

```



**Rules:**

1. **Use descriptive method names** - `isLowStock()`, `hasPermission()`, `isExpired()`, etc.

2. **Return boolean for check methods** - Methods starting with `is` or `has` should return bool

3. **Accept flexible parameters** - Allow both string and enum for methods like `hasPermission()`

4. **Keep methods focused** - One responsibility per method



---



## Migration Patterns



### UUID Primary Keys



```php

Schema::create('users', function (Blueprint $table) {

    $table->uuid('id')->primary();

    // ... other columns

});

```



### Foreign Keys with UUIDs



```php

$table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');

```



### Enums in Migrations



**When using ALL enum cases:**



You can use either `array_column()` directly or an enum helper method (if available):



```php

use App\Enums\ProductTypeEnum;



$table->enum('type', array_column(ProductTypeEnum::cases(), 'name'));

```



Or if the enum has a `names()` helper method:



```php

use App\Enums\ProductTypeEnum;



$table->enum('type', ProductTypeEnum::names());

```



**When using a SUBSET of enum cases:**



Only manually list enum cases when you're not using all available enum values. For example, if an enum has 5 cases but you only want to allow 3 of them in the database:



```php

use App\Enums\SomeEnum;



$table->enum('status', [

    SomeEnum::ACTIVE->name,

    SomeEnum::INACTIVE->name,

    SomeEnum::PENDING->name,

]);

```



**With default value:**



```php

use App\Enums\ProductStatusEnum;



$table->enum('status', ProductStatusEnum::names())->default(ProductStatusEnum::UNPUBLISHED->name);

```



### JSON Fields



```php

$table->json('tags')->nullable();

$table->json('demo_videos')->nullable();

$table->json('screens')->nullable();

```



### Rules



1. **Use enums, not hardcoded strings** - Reference enum classes in migrations

2. **Use `array_column()` for all enum cases** - When using all enum cases, use `array_column(Enum::cases(), 'name')` or `Enum::names()` if available

3. **Manually list enum cases when using a subset** - Only list specific cases when not using all enum values

4. **Use JSON for complex data** - Instead of separate tables when appropriate

5. **Use `foreignUuid()` for UUID foreign keys** - Not `foreignId()`

6. **Add indexes for frequently queried fields** - `$table->index('email')`

7. **Use `nullable()` appropriately** - Mark optional fields

8. **Add `softDeletes()` for soft deletion** - Use `$table->softDeletes()` for models using SoftDeletes trait

9. **Use appropriate column types** - `unsignedBigInteger` for money in minor units, `integer` for quantities, `boolean` for flags

10. **Add unique constraints** - Use `->unique()` for fields like SKU, barcode that must be unique



---



## Helper Classes



Helper classes provide reusable utility functions across the application. They extend the `L0n3ly\LaravelDynamicHelpers\Helper` base class and are accessed via the global `helpers()` function.



### Location



- All helpers are located in `app/Helpers/`

- Each helper class extends `L0n3ly\LaravelDynamicHelpers\Helper`

- Accessed via `helpers()->helperName()->methodName()`



### Common Helpers



#### PermissionHelper



Validates user permissions and throws exceptions for unauthorized access.



```php

<?php



namespace App\Helpers;



use App\Enums\PermissionEnum;

use L0n3ly\LaravelDynamicHelpers\Helper;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;



class PermissionHelper extends Helper

{

    /**

     * Validate if the authenticated user has the specified permission.

     *

     * @throws AccessDeniedHttpException

     * @throws UnauthorizedHttpException

     */

    public function validatePermission(PermissionEnum $permission): void

    {

        $user = auth()->user();

        if (! $user) {

            throw new UnauthorizedHttpException('', 'User not authenticated');

        }



        if (! $user->hasPermission($permission)) {

            throw new AccessDeniedHttpException('Access denied.');

        }

    }

}

```



**Usage in Services:**

```php

public function getAllProducts()

{

    try {

        helpers()->permissionHelper()->validatePermission(PermissionEnum::VIEW_PRODUCTS);

        // ... rest of method

    } catch (AccessDeniedHttpException $e) {

        return $this->setCode(ResponseCode::FORBIDDEN->value)

            ->setMessage('Access denied. Missing required permission to perform this action.');

    }

}

```



#### QueryableHelper



Handles pagination, filtering, searching, and sorting for queries.



```php

class QueryableHelper extends Helper

{

    /**

     * Get pagination details from a paginated model.

     */

    public function getPagination($model)

    {

        return [

            'from' => $model->firstItem(),

            'to' => $model->lastItem(),

            'total' => $model->total(),

            'per_page' => $model->perPage(),

            'first_page' => 1,

            'previous_page' => $model->currentPage() > 1

                ? $model->currentPage() - 1

                : null,

            'current_page' => $model->currentPage(),

            'next_page' => $model->currentPage() < $model->lastPage()

                ? $model->currentPage() + 1

                : null,

            'last_page' => $model->lastPage(),

        ];

    }



    /**

     * Apply common filters like search, status, sorting, and pagination.

     */

    public function fetchWithFilters(

        Model|EloquentBuilder|QueryBuilder $model,

        ?callable $extraQuery = null

    ): LengthAwarePaginator {

        // Applies search, status, sorting, pagination

    }

}

```



**Usage in Repositories:**

```php

public function getAllProducts(): \Illuminate\Contracts\Pagination\LengthAwarePaginator

{

    $query = $this->model->with('categories');



    return helpers()->queryableHelper()->fetchWithFilters($query);

}

```



**Usage in Services:**

```php

public function getAllProducts()

{

    $products = $this->mainRepository->getAllProducts();



    return $this->setCode(ResponseCode::SUCCESS->value)

        ->setMessage('Products retrieved successfully.')

        ->setData([

            'products' => ProductResource::collection($products),

            'pagination' => helpers()->queryableHelper()->getPagination($products),

        ]);

}

```



#### MoneyHelper



Converts between major and minor currency units (dollars ↔ cents).



```php

class MoneyHelper extends Helper

{

    /**

     * Converts a major currency amount to its minor currency unit.

     * Example: 19.99 → 1999

     */

    public static function toMinor(int|float|null $amount = null)

    {

        if (is_null($amount) || ! is_numeric($amount) || $amount < 0) {

            return null;

        }



        return (int) round($amount * 100);

    }



    /**

     * Converts a minor currency unit amount to its major currency amount.

     * Example: 1999 → 19.99 or "$19.99" if formatted

     */

    public static function fromMinor(int|float|string|null $amount = null, $format = false)

    {

        if (is_null($amount) || ! is_numeric($amount) || $amount < 0) {

            return null;

        }



        $amount = round($amount / 100, 2);

        if ($format) {

            return self::addCurrency($amount);

        }



        return $amount;

    }

}

```



**Usage in Resources:**

```php

public function toArray(Request $request): array

{

    return [

        'cost_price' => helpers()->moneyHelper()->fromMinor($this->cost_price, true),

        'selling_price' => helpers()->moneyHelper()->fromMinor($this->selling_price, true),

    ];

}

```



#### ImageHelper



Handles image URL generation with fallbacks and deletion.



```php

class ImageHelper extends Helper

{

    /**

     * Get the full URL for the image path, with a placeholder fallback.

     */

    public function getImageUrl(?string $path, string $title): string

    {

        $encodedTitle = urlencode($title);



        if ($path && filter_var($path, FILTER_VALIDATE_URL)) {

            return $path;

        }



        if ($path && Storage::disk('public')->exists($path)) {

            return Storage::url(path: $path);

        }



        return "https://placehold.co/800x800/d5d5d5/000000?text={$encodedTitle}";

    }



    /**

     * Get full URLs for a collection of image paths.

     */

    public function getImageCollectionUrls(?array $paths): array

    {

        // Returns array of valid image URLs

    }



    /**

     * Delete an image from storage.

     */

    public function deleteImage(?string $path): bool

    {

        // Deletes image if exists

    }

}

```



**Usage in Resources:**

```php

public function toArray(Request $request): array

{

    return [

        'image_path' => helpers()->imageHelper()->getImageUrl($this->image_path, $this->name),

        'gallery_images_path' => helpers()->imageHelper()->getImageCollectionUrls($this->gallery_images_path),

    ];

}

```



**Usage in Services:**

```php

public function updateProductImage(string $id, array $data)

{

    // Delete old images before updating

    if (! empty($data['image_path'])) {

        helpers()->imageHelper()->deleteImage($product->image_path);

    }

}

```



### Helper Rules



1. **Extend `L0n3ly\LaravelDynamicHelpers\Helper`** - All helpers must extend this base class

2. **Place in `app/Helpers/`** - All helper classes go in this directory

3. **Use `helpers()` global function** - Access via `helpers()->helperName()->methodName()`

4. **Single responsibility** - Each helper should have a focused purpose

5. **Static methods when appropriate** - Use static methods for stateless utilities like `MoneyHelper::toMinor()`

6. **Null-safe operations** - Handle null inputs gracefully

7. **Document all methods** - Add comprehensive PHPDoc blocks



---



## Testing Patterns



### Feature Test Structure



```php

<?php



namespace Tests\Feature\App\Auth;



use App\Models\User;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\TestCase;



class LoginTest extends TestCase

{

    use RefreshDatabase;



    public function test_user_can_login_with_valid_credentials(): void

    {

        $user = User::factory()->create([

            'email' => 'test@example.com',

            'password' => Hash::make('password'),

        ]);



        $response = $this->postJson('/api/sessions', [

            'email' => 'test@example.com',

            'password' => 'password',

        ]);



        $response->assertStatus(200)

            ->assertJsonStructure([

                'data' => [

                    'user',

                    'token',

                    'token_type',

                ],

            ]);

    }

}

```



### Rules



1. **Use `RefreshDatabase` trait** - Clean database for each test

2. **Use factories for test data** - Never hardcode data creation

3. **Test both success and failure cases** - Happy path and error scenarios

4. **Use descriptive test names** - `test_user_can_login_with_valid_credentials`

5. **Assert response structure** - Use `assertJsonStructure()` for API tests

6. **Group related tests** - One test class per feature area

7. **Organize by feature** - Group by domain/feature (e.g., `Feature/Auth/`, `Feature/Product/`)

8. **Use route names** - Always use `route()` helper instead of hardcoded URLs



### Test Organization



**Option 1: By Feature/Domain**

```

tests/Feature/

├── Auth/

│   ├── LoginTest.php

│   └── RegisterTest.php

└── Product/

    ├── CreateProductTest.php

    ├── UpdateProductTest.php

    └── ListProductsTest.php

```



**Option 2: By User Type (if applicable)**

```

tests/Feature/

├── App/

│   ├── Auth/

│   └── Product/

└── Admin/

    ├── Auth/

    └── Product/

```

---



## Code Quality Rules



### General Rules



1. **No inline comments** - Never use inline comments (`//` or `/* */`). Use comprehensive PHPDoc blocks for methods instead

2. **Docblocks for implementations** - All repository and service implementation classes must have docblocks for:

   - Constructors

   - All public methods

   - Include parameter descriptions with types

   - Include return type descriptions

   - Explain method purpose and behavior

3. **No class-level docblocks** - Do not add docblocks to classes, only to methods

4. **Use type hints in implementations** - But not in interfaces

5. **Follow PSR-12 coding standards** - Consistent formatting

6. **Use meaningful variable names** - `$user`, `$property`, not `$u`, `$p`

7. **Keep methods focused** - One responsibility per method

8. **Avoid deep nesting** - Use early returns when possible



### Docblock Requirements



**For Service Implementations:**

```php

/**

 * Authenticate a user with email and password.

 *

 * Validates user credentials, checks if account is active,

 * updates last login timestamp, and generates authentication token.

 * Returns user details with roles and permissions on successful authentication.

 *

 * @param  mixed  $request  The validated form request containing email and password

 * @return \LaravelEasyRepository\ServiceApi Returns service response with user data and token on success, or error on failure

 */

public function login($request)

{

    // Implementation

}

```



**For Repository Implementations:**

```php

/**

 * Find a user by email address.

 *

 * Retrieves a user with the specified email address along with

 * eager-loaded relationships (agent, landlord, roles) to prevent N+1 queries.

 *

 * @param  string  $email  The user's email address

 * @return \App\Models\User|null Returns the User model instance or null if not found

 */

public function findUserByEmail($email)

{

    // Implementation

}

```



**For Constructors:**

```php

/**

 * Initialize the service with user repository dependency.

 *

 * @param  UserRepository  $mainRepository  The user repository instance

 */

public function __construct(protected UserRepository $mainRepository) {}

```



### Import Organization



```php

<?php



namespace App\Services\App\Auth;



use App\Http\Resources\UserResource;

use App\Repositories\User\UserRepository;

use Illuminate\Support\Facades\Hash;

use LaravelEasyRepository\ServiceApi;

```



**Order:**

1. App namespace imports

2. Framework imports

3. Third-party package imports



### Error Handling



**In Services:**

```php

use App\Enums\ResponseCode;



try {

    return $this->setCode(ResponseCode::SUCCESS->value)

        ->setMessage('Success')

        ->setData(['key' => 'value']);

} catch (\Exception $e) {

    return $this->setCode(ResponseCode::SERVER_ERROR->value)

        ->setMessage('An error occurred')

        ->setError($e->getMessage());

}

```



### Database Transactions



**Use transactions for multi-step operations:**

```php

DB::beginTransaction();

try {

    // Multiple operations

    DB::commit();

} catch (\Exception $e) {

    DB::rollBack();

    throw $e;

}

```



---



## Authentication Flow



### Example Authentication Flow



1. **Register**: `POST /api/register` → `AuthController::register()` → `AuthService::register()`

2. **Login**: `POST /api/sessions` → `AuthController::login()` → `AuthService::login()`

3. **Logout**: `DELETE /api/sessions` → `AuthController::logout()` → `AuthService::logout()`

4. **Profile**: `GET /api/profile` → `ProfileController::show()` → `ProfileService::getProfile()`



### Sanctum Token Management



- **Create token**: `$user->createToken('auth-token')->plainTextToken`

- **Delete token**: `$request->user()->currentAccessToken()->delete()`

- **Check auth**: `auth()->check()` or `auth()->user()`



---



## Common Patterns Summary



### Creating a New Feature End-to-End



1. **Create Migration** - Define database schema

2. **Create Model** - With relationships, casts, accessors

3. **Create Factory** - For testing and seeding

4. **Create Form Request** - For validation

5. **Create Repository Interface** - Define data access methods

6. **Create Repository Implementation** - Implement data access

7. **Create Service Interface** - Define business logic methods

8. **Create Service Implementation** - Implement business logic

9. **Create Resource** - Transform model for API

10. **Create Controller** - Handle HTTP requests

11. **Add Routes** - Define API endpoints

12. **Create Tests** - Feature tests for endpoints



### Example: Adding a "Product" Feature



```

1. Migration: create_products_table.php

2. Model: Product.php (with relationships)

3. Factory: ProductFactory.php

4. Request: Product/StoreProductRequest.php, UpdateProductRequest.php

5. Repository: Product/ProductRepository.php + ProductRepositoryImplement.php

6. Service: Product/ProductService.php + ProductServiceImplement.php

7. Resource: ProductResource.php

8. Controller: ProductController.php

9. Routes: routes/api.php (or routes/web.php for Inertia)

10. Tests: Feature/Product/CreateProductTest.php, etc.

```



---



## Quick Reference Checklist



When implementing a new feature, ensure:



- [ ] Repository interface and implementation created in proper subdirectory

- [ ] Service interface and implementation created in proper subdirectory

- [ ] Form requests for validation organized by feature

- [ ] Resource classes for API responses

- [ ] Controller with dependency injection organized by feature

- [ ] Routes properly named and grouped in correct route file

- [ ] Models use `HasUuids` trait (if UUIDs)

- [ ] Eager loading in repositories

- [ ] Error handling in services

- [ ] Feature tests written and organized by feature

- [ ] **Docblocks added to all implementation methods and constructors**

- [ ] **No inline comments used**

- [ ] **ResponseCode enum used instead of hardcoded status codes**

- [ ] **Scribe documentation added to all controller methods**

- [ ] **All response scenarios documented in Scribe annotations**

- [ ] Code follows PSR-12

