# Agent Instructions & Project Architecture Guidelines

## Execution Guidelines
1. **Auto-Submit / Do Not Wait for User Input:**
   - Do not stop or wait for unnecessary user confirmation before proceeding with modifications.
   - Proceed directly with implementation and auto-submit solutions.

2. **Terminal Command & Testing Restrictions:**
   - **Do NOT run any terminal commands or test commands** (e.g., `php artisan test`, test suites, build tools, etc.) autonomously.
   - Only execute terminal commands when the user explicitly provides the command details and asks for it to be run.

---

## Project Context & Configurations
- **Framework:** Laravel 12 on Laragon (Windows)
- **Admin Login:** `/admin/login` (named route `login`).
- **Installation Bypass:**
  - `APP_INSTALLED=true` in `.env` bypasses the installer entirely without requiring `storage/installed`.
  - `APP_INSTALLED=false` activates the web installer wizard.
- **Local Environment Defaults:**
  - `APP_ENV=local`
  - `APP_DEBUG=true`
  - `CACHE_STORE=file`
  - `SESSION_DRIVER=file`

---

## Architectural Patterns & Backend Standards (Follow for all future features)

When implementing any new feature, module, or entity, follow the established multi-layer architecture strictly:

### 1. Model Layer (`app/Models/{Model}.php`)
- **Base Class:** `Illuminate\Database\Eloquent\Model`
- **Guarded Attributes:** `protected $guarded = ['id'];`
- **Traits to use:**
  - `Illuminate\Database\Eloquent\SoftDeletes` (soft deleting support)
  - `Illuminate\Database\Eloquent\Factories\HasFactory`
  - `Spatie\Activitylog\Traits\LogsActivity` (auditing and activity logs)
    - Implement `getActivitylogOptions(): LogOptions` with `logOnly([...])->logOnlyDirty()->setDescriptionForEvent(...)->useLogName('{module}')`
  - `Spatie\Translatable\HasTranslations` (for multilingual fields: `public array $translatable = ['...'];`)
  - `App\Models\Traits\HasMedia` (for models with media / file attachments)
- **Relationships:** Define clear Eloquent relationships (`belongsTo`, `hasMany`, `morphMany`).

### 2. Repository Layer (`app/Repositories/`)
- **Contract Interface:** `app/Repositories/Contracts/{Model}RepositoryInterface.php`
  - Extends `App\Repositories\Contracts\BaseRepositoryInterface`
  - Defines domain-specific data access methods (e.g., `getActive()`, `getByStatus()`).
- **Concrete Repository:** `app/Repositories/{Model}Repository.php`
  - Extends `App\Repositories\BaseRepository`
  - Implements `{Model}RepositoryInterface`
  - Uses `App\Repositories\Traits\SoftDeleteTrait` and `App\Repositories\Traits\BulkActionTrait`
  - Injects concrete `{Model} $model` in constructor and passes to `parent::__construct($model)`
  - Overrides `create(array $data)` or `update($id, array $data)` for model-specific relations (syncing pivots, handling media, etc.)
- **Service Provider Binding:**
  - Register binding in `app/Providers/AppServiceProvider.php`:
    ```php
    $this->app->bind({Model}RepositoryInterface::class, {Model}Repository::class);
    ```

### 3. Service Layer (`app/Services/`)
- **Class:** `app/Services/{Model}Service.php`
- **Extends:** `App\Services\BaseService`
- **Traits to use:** `App\Services\Traits\SoftDeleteTrait`, `App\Services\Traits\BulkActionTrait`
- **Constructor:** Injects `{Model}RepositoryInterface $repository` and passes to `parent::__construct($repository)`.
- **Transactions:** `BaseService` automatically wraps `create`, `update`, and `delete` inside `DB::transaction(...)`. Use `$this->transaction(fn() => ...)` for custom multi-step mutations.
- **Business Logic:** Put calculations, notifications, external API calls, and business rules inside the Service class, keeping Controllers thin.

### 4. ViewModel Layer (`app/ViewModels/Admin/{Model}FormData.php`)
- Dedicated data carrier classes for form rendering to decouple controllers from view-data assembly:
  - `public static function forCreate(...): self`
  - `public static function forEdit($model, ...): self`
  - `public function toArray(): array`

### 5. Form Request Layer (`app/Http/Requests/{Model}Request.php`)
- Extend `Illuminate\Foundation\Http\FormRequest`.
- Handle authorization (`authorize() => true` or gate check).
- Validation rules in `rules()`. Use `password_rules()` helper when validating passwords.
- Use `App\Http\Requests\BulkActionRequest` for bulk operations (`ids` array validation).

### 6. DataTable Layer (`app/DataTables/{Model}DataTable.php`)
- Extend `Yajra\DataTables\Services\DataTable`.
- Implement `dataTable(QueryBuilder $query): EloquentDataTable`:
  - `multi_check`: Checkbox column for bulk actions.
  - `action`: View, Edit, Delete, Restore, Force-Delete action buttons using component views:
    - `components.action.view-button`
    - `components.action.edit-button`
    - `components.action.delete-button`
    - `components.action.restore-button`
  - Custom column formatters (badges, dates, translatable labels).
  - Search filtering on defined `$searchableColumns`.

### 7. Controller Layer (`app/Http/Controllers/Admin/{Model}Controller.php`)
- Extends `App\Http\Controllers\Controller`.
- Injects `{Model}Service` (never repository directly).
- Standard Resource & Trash Actions:
  - `index({Model}DataTable $dataTable)`: renders index view with DataTable.
  - `trash({Model}DataTable $dataTable)`: renders trash view with restore/permanent delete bulk options.
  - `create()`: uses `{Model}FormData::forCreate(...)`.
  - `store({Model}Request $request)`: calls `$this->service->create($request->validated())`.
  - `show($id)`: views details.
  - `edit($model)`: uses `{Model}FormData::forEdit(...)`.
  - `update({Model}Request $request, $model)`: calls `$this->service->update($model->id, $request->validated())`.
  - `destroy($model)`: calls `$this->service->delete($model->id)` -> returns `ResponseHelper::success([], __('common.deleted'))`.
  - `bulkDestroy(BulkActionRequest $request)`: calls `$this->service->bulkDelete($ids)` -> returns `ResponseHelper::success(...)`.
  - `restore($id)`: calls `$this->service->restore($id)` -> returns `ResponseHelper::success(...)`.
  - `bulkRestore(BulkActionRequest $request)`: calls `$this->service->bulkRestore($ids)` -> returns `ResponseHelper::success(...)`.
  - `forceDelete($id)`: calls `$this->service->forceDelete($id)` -> returns `ResponseHelper::success(...)`.
  - `bulkForceDelete(BulkActionRequest $request)`: calls `$this->service->bulkForceDelete($ids)` -> returns `ResponseHelper::success(...)`.

### 8. Routing & Route Macros (`routes/web.php` & `routes/macros.php`)
- Use the custom macro for full CRUD + Trash support:
  ```php
  Route::resourceWithTrash('{resource}', {Model}Controller::class);
  ```
- Placed inside `Route::middleware(['auth', 'demo', 'permission'])->prefix('admin')->group(...)`.

### 9. RBAC & Permissions Integration
When adding any feature/module:
1. **Define Permissions:** Add permission group/module in `config/permissions.php`.
2. **Route Authorization Mapping:** Add route names to permission slug mapping in `App\Http\Middleware\CheckPermission::$routePermissions`:
   ```php
   '{resource}.index' => 'view_{resource}',
   '{resource}.trash' => 'view_{resource}',
   '{resource}.create' => 'create_{resource}',
   '{resource}.store' => 'create_{resource}',
   '{resource}.edit' => 'edit_{resource}',
   '{resource}.update' => 'edit_{resource}',
   '{resource}.destroy' => 'delete_{resource}',
   '{resource}.bulk_delete' => 'delete_{resource}',
   '{resource}.restore' => 'delete_{resource}',
   '{resource}.bulk_restore' => 'delete_{resource}',
   '{resource}.force-delete' => 'delete_{resource}',
   '{resource}.bulk_force_delete' => 'delete_{resource}',
   ```
3. **Sidebar Menu:** Add entry in `config/admin_menu.php` with `'permission' => 'view_{resource}'`.

### 10. Global Helpers & Responses
- **AJAX Responses:** `App\Helpers\ResponseHelper::success($data, $message, $code)` and `ResponseHelper::error($message, $code, $data)`.
- **Notifications:** `notify($user, string $title, string $message, string $type = 'info', ?string $url = null)`:
  - Automatically stores in database notifications table.
  - Broadcasts real-time WebSocket event via Laravel Reverb (`App\Events\NewNotification`).
- **Settings:** `setting(string $key, $default = null)` reads via `SettingService`.
- **Assets:** `admin_asset($path)` generates URL to `public/backend/assets/{path}`.

---

### 11. Account Type Classification & Customer Scoping
The system distinguishes internal staff from customers via `account_type` enum on the `users` table:
- **Enum Representation:**
  - Database: `enum('account_type', ['admin', 'customer'])` (default `'customer'`)
  - PHP: `App\Enums\AccountType` (`AccountType::ADMIN = 'admin'`, `AccountType::CUSTOMER = 'customer'`)
- **Allowed Values:**
  - `admin`: Internal users (Administrators, Support Agents, Team Leads, custom staff roles).
  - `customer`: Customer-facing accounts.
- **Model Scopes & Helpers:**
  - `User::isAdmin()`: scope for internal user listings (`scopeIsAdmin()`).
  - `User::isCustomer()`: scope for customer user listings (`scopeIsCustomer()`).
  - `$user->isAdmin()` / `$user->isCustomer()`: boolean helpers.
- **Separation of Management:**
  - Admin User Management (`/admin/users`) strictly queries and manages internal accounts (`account_type === 'admin'`).
  - Customer Management (`/admin/customers`) manages customer accounts separately (`account_type === 'customer'`).
- **Panel Separation & Route Security:**
  - Admin and Support Agent share the `/admin` panel.
  - Customer accounts access customer-facing portals only.
  - `/admin/*` routes are guarded by `CheckPermission` which blocks non-admin accounts (`!$user->isAdmin() => 403`).
  - `/admin/login` strictly validates `$user->isAdmin()` during authentication; customers are logged out and denied access.
- **Dynamic RBAC Integrity:**
  - Internal users use dynamic roles and permissions (`Role`, `RolePermission`). Role names like Administrator or Support Agent are NEVER hardcoded.
  - `account_type` is NOT a replacement for RBAC; it categorizes the account. RBAC determines permissions.
- **Customer Ticket Ownership (Requirement 13):**
  - Customer ticket queries MUST always enforce customer ownership using `scopeForCustomer()` / `scopeForCurrentCustomer()` (`App\Models\Traits\BelongsToCustomer`). Never allow cross-customer ticket queries or modifications.

