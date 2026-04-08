# Domain-Based Architecture Migration

## Summary

Migrate the current flat `app/` structure to a Spatie-inspired domain-based architecture with three layers: **Domain** (business logic), **Application** (delivery mechanisms), and **Support** (cross-cutting utilities). This brings organizational clarity and explicit bounded contexts without leaving Laravel's PSR-4 autoloading conventions.

## Architecture

### Three-Layer Structure

```
app/
├── Domain/          ← Pure business logic, grouped by bounded context
├── Application/     ← Delivery layer (Web, API, Filament, Console)
└── Support/         ← Cross-cutting helpers, base classes, shared concerns
```

- **Domain/** contains models, services, events, listeners, observers, policies, enums, and jobs — everything that represents core business rules.
- **Application/** contains controllers, form requests, resources, middleware, Filament panels, and console commands — everything that adapts domain logic to a delivery context.
- **Support/** contains shared utilities that don't belong to any single domain (e.g., `HasHashid` trait, `LinkHeader` helper, `ConsentNotice`).

### Domain Boundaries

| Domain | Models | Services | Events | Listeners | Observers | Policies | Enums | Jobs |
|---|---|---|---|---|---|---|---|---|
| **User** | `User`, `TwoFactor`, `Skill` | `RegistrationService`, `BackupCodeService`, `WebAuthnService`, `YubicoService`, `EdnaService`, `EdnaCheckResult` | `NewProfilePhotoEvent` | `LogFailedLoginListener`, `LogUserLoginListener`, `LogUserLogoutListener`, `LogUserLockoutListener`, `LogUserPasswordResetListener`, `LogUserRegisteredListener`, `LogUserVerifiedListener`, `SyncAutomatedSystemGroups` | `UserObserver` | — | `TwoFactorTypeEnum`, `StaffProfileVisibility` | — |
| **Group** | `Group`, `GroupUser` | `NextcloudService` | `GroupCreated`, `GroupDeleted`, `GroupUpdated`, `GroupUserAdded`, `GroupUserRemoved`, `GroupUserUpdated` | `AssignGroupOwner`, all `Nextcloud/*` listeners, `Concerns/ChecksNextcloudEnvironment` (trait) | `GroupObserver`, `GroupUserObserver` | `GroupPolicy`, `GroupUserPolicy` | `GroupTypeEnum`, `GroupUserLevel` | all `Nextcloud/*` jobs |
| **OAuth** | `App`, `AppCategory`, `OauthSession`, `UserAppMetadata`, `WebhookDelivery` | `Hydra/Admin`, `Hydra/Client`, `Hydra/HydraRequestException`, `Hydra/Models/App`, `OpenIDService`, `Webhooks/WebhookDispatcher`, `Webhooks/WebhookSigner`, `Webhooks/UserFieldMap` | `AppLoginEvent` | `LogUserAppLoginListener` | `AppObserver` | `AppPolicy` | — | `Webhooks/DeliverWebhook` |
| **Convention** | `Convention`, `ConventionAttendee` | — | — | — | — | — | — | — |
| **Notification** | `NotificationType`, `AppNotificationRecord` | `Notifications/NotificationPreferenceResolver`, `TelegramNotifier` | — | `SendTelegramLoginNotification` | `NotificationTypeObserver` | — | `NotificationCategory`, `NotificationChannel` | `PurgeOldNotificationsJob`, `SendAppNotificationJob` |
| **Directory** | — (uses `User`, `Group`) | `DirectoryTreeBuilder`, `DirectoryAuthorizer` | — | — | — | — | — | — |

### Application Layer

| Application | Contents |
|---|---|
| **Web/Controllers/Auth/** | `LoginController`, `LogoutController`, `RegisterController`, `RegisterVerifyController`, `ForgotPasswordController`, `PasswordResetController`, `ConsentController`, `EmailController`, `ErrorController`, `BackChannelLogoutController`, `FrontChannelLogoutController`, `RememberSessionController`, `VerifyCodeController`, `VerifyEmailController`. **Note:** `AuthController`, `TwoFactorController`, and `UpdateEmailController` currently live at the controller root — they move into `Auth/` as part of this migration. |
| **Web/Controllers/Profile/** | `ShowProfileController`, `UpdateProfileController`, `StoreAvatarController`, `DeleteAccountController`, `ExportMyDataController`, `MyDataController`, `SecurityController`, `UserinfoController`, `UpdatePreferencesController`, `NotificationsController`, `NotificationPreferencesController`, `UpdateConventionAttendanceController`, `UpdateGroupCreditAsController`, `GrantStaffProfileConsentController`, `WithdrawStaffProfileConsentController`, `RevokeAppConsentController`, `SearchSkillsController`, `UpdateStaffProfileController` |
| **Web/Controllers/Profile/Settings/** | `AppsController`, `Apps/NotificationTypesController`, `AppWebhookController`, `ChangeEmailController`, `ConfirmPasswordController`, `SessionController`, `TelegramController`, `TwoFactor/BackupCodesController`, `TwoFactor/PasskeySetupController`, `TwoFactor/SecurityKeySetupController`, `TwoFactor/TotpSetupController`, `TwoFactor/TwoFactorController`, `TwoFactor/YubikeySetupController`, `UpdatePasswordController` |
| **Web/Controllers/Directory/** | `DirectoryController`, `DirectoryDepartmentController`, `DirectoryMemberController`, `DirectoryTeamController`, `NdaController`, `StaffProfileController` |
| **Web/Controllers/** (root) | `DashboardController` |
| **Web/Controllers/** (root, cont.) | `ExportManagerController`, `HealthController`, `Controller` (base) |
| **Web/Requests/** | All form requests currently in `app/Http/Requests/` that serve web routes (Auth/*, Profile/*, Directory/*, Developer/*, plus loose ones like `ChangeEmailRequest`, `ForgotPasswordRequest`, `UpdatePasswordRequest`, etc.) |
| **Web/Middleware/** | All current middleware files |
| **Api/V1/Controllers/** | `ConventionController`, `GroupController`, `GroupUserController`, `IntrospectionController`, `UserinfoController` |
| **Api/V1/Resources/** | `ConventionResource`, `GroupCollection`, `GroupResource`, `GroupUserCollection`, `GroupUserResource`, `LoginResource`, `TokenResource`, `UserinfoResource` |
| **Api/V1/Requests/** | `IntrospectionRequest`, `GroupStoreRequest`, `GroupUpdateRequest`, `GroupUserStoreRequest`, `UserinfoRequest` |
| **Api/V2/Controllers/** | `ConventionController`, `GroupController`, `GroupMemberController`, `IntrospectionController`, `MetadataController`, `NotificationController`, `StaffController`, `UserinfoController` |
| **Api/V2/Resources/** | `ConventionResource`, `GroupMemberResource`, `GroupResource`, `MetadataResource`, `StaffResource`, `StaffResourceCollection`, `TokenResource`, `UserinfoResource` |
| **Api/V2/Concerns/** | `ChecksScopes` (controller trait) |
| **Api/V2/Requests/** | `SendNotificationRequest`, `StoreGroupMemberRequest`, `UpdateGroupMemberRequest`, `UpsertMetadataRequest` |
| **Filament/** | Stays as-is — already self-contained with its own panel structure |
| **Console/Commands/** | All artisan commands, grouped: `User/` commands, `ClearUnverifiedCommand`, `FixTeamMembershipsCommand`, `PruneExpiredMetadataCommand`, `PruneWebhookDeliveries`, `appsSyncCommand` |
| **Telegram/** | `Handlers/FallbackHandler`, `Handlers/LinkHandler`, `Handlers/StartHandler`, `Handlers/UnlinkHandler` |

### Support Layer

| File | Reason |
|---|---|
| `Models/Concerns/HasHashid` | Used across multiple domains |
| `Models/Scopes/HashidScope` | Used across multiple domains |
| `StaffProfile/ConsentNotice` | Cross-cutting concern |
| `Services/Auth/AdminAuth` | Infrastructure auth concern |
| `Services/Auth/ApiGuard` | Infrastructure auth concern |
| `ScopeChecker` | Shared OAuth scope checking utility |
| `Providers/*` | Framework bootstrap, stays at `app/Providers/` (including `Socialite/SocialiteIdentityProvider`) |

### Notifications

`app/Notifications/` (the Laravel notification classes — `AppNotification`, `PasswordResetQueuedNotification`, `UpdateEmailNotification`, `VerifyEmailCodeNotification`) plus `Channels/AppDatabaseChannel` and `Channels/AppTelegramChannel` move into `Domain/Notification/Notifications/` and `Domain/Notification/Channels/`.

## Target Directory Structure

```
app/
├── Domain/
│   ├── User/
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   ├── TwoFactor.php
│   │   │   └── Skill.php
│   │   ├── Services/
│   │   │   ├── RegistrationService.php
│   │   │   ├── BackupCodeService.php
│   │   │   ├── WebAuthnService.php
│   │   │   ├── YubicoService.php
│   │   │   ├── EdnaService.php
│   │   │   └── EdnaCheckResult.php
│   │   ├── Events/
│   │   │   └── NewProfilePhotoEvent.php
│   │   ├── Listeners/
│   │   │   ├── LogFailedLoginListener.php
│   │   │   ├── LogUserLoginListener.php
│   │   │   ├── LogUserLogoutListener.php
│   │   │   ├── LogUserLockoutListener.php
│   │   │   ├── LogUserPasswordResetListener.php
│   │   │   ├── LogUserRegisteredListener.php
│   │   │   ├── LogUserVerifiedListener.php
│   │   │   └── SyncAutomatedSystemGroups.php
│   │   ├── Observers/
│   │   │   └── UserObserver.php
│   │   └── Enums/
│   │       ├── TwoFactorTypeEnum.php
│   │       └── StaffProfileVisibility.php
│   │
│   ├── Group/
│   │   ├── Models/
│   │   │   ├── Group.php
│   │   │   └── GroupUser.php
│   │   ├── Services/
│   │   │   └── NextcloudService.php
│   │   ├── Events/
│   │   │   ├── GroupCreated.php
│   │   │   ├── GroupDeleted.php
│   │   │   ├── GroupUpdated.php
│   │   │   ├── GroupUserAdded.php
│   │   │   ├── GroupUserRemoved.php
│   │   │   └── GroupUserUpdated.php
│   │   ├── Listeners/
│   │   │   ├── AssignGroupOwner.php
│   │   │   ├── Concerns/
│   │   │   │   └── ChecksNextcloudEnvironment.php
│   │   │   └── Nextcloud/
│   │   │       ├── AddUserToNextcloudGroup.php
│   │   │       ├── CreateNextcloudGroup.php
│   │   │       ├── DeleteNextcloudGroup.php
│   │   │       ├── RemoveUserFromNextcloudGroup.php
│   │   │       ├── UpdateNextcloudGroup.php
│   │   │       └── UpdateUserNextcloudGroupLevel.php
│   │   ├── Observers/
│   │   │   ├── GroupObserver.php
│   │   │   └── GroupUserObserver.php
│   │   ├── Policies/
│   │   │   ├── GroupPolicy.php
│   │   │   └── GroupUserPolicy.php
│   │   ├── Enums/
│   │   │   ├── GroupTypeEnum.php
│   │   │   └── GroupUserLevel.php
│   │   └── Jobs/
│   │       └── Nextcloud/
│   │           ├── AddUserToGroupJob.php
│   │           ├── CreateGroupJob.php
│   │           ├── DeleteGroupJob.php
│   │           ├── RemoveUserFromGroupJob.php
│   │           ├── UpdateGroupJob.php
│   │           └── UpdateUserGroupLevelJob.php
│   │
│   ├── OAuth/
│   │   ├── Models/
│   │   │   ├── App.php
│   │   │   ├── AppCategory.php
│   │   │   ├── OauthSession.php
│   │   │   ├── UserAppMetadata.php
│   │   │   └── WebhookDelivery.php
│   │   ├── Services/
│   │   │   ├── Hydra/
│   │   │   │   ├── Admin.php
│   │   │   │   ├── Client.php
│   │   │   │   ├── HydraRequestException.php
│   │   │   │   └── Models/
│   │   │   │       └── App.php
│   │   │   ├── OpenIDService.php
│   │   │   └── Webhooks/
│   │   │       ├── WebhookDispatcher.php
│   │   │       ├── WebhookSigner.php
│   │   │       └── UserFieldMap.php
│   │   ├── Events/
│   │   │   └── AppLoginEvent.php
│   │   ├── Listeners/
│   │   │   └── LogUserAppLoginListener.php
│   │   ├── Observers/
│   │   │   └── AppObserver.php
│   │   ├── Policies/
│   │   │   └── AppPolicy.php
│   │   └── Jobs/
│   │       └── DeliverWebhook.php
│   │
│   ├── Convention/
│   │   └── Models/
│   │       ├── Convention.php
│   │       └── ConventionAttendee.php
│   │
│   ├── Notification/
│   │   ├── Models/
│   │   │   ├── NotificationType.php
│   │   │   └── AppNotificationRecord.php
│   │   ├── Services/
│   │   │   ├── NotificationPreferenceResolver.php
│   │   │   └── TelegramNotifier.php
│   │   ├── Notifications/
│   │   │   ├── AppNotification.php
│   │   │   ├── PasswordResetQueuedNotification.php
│   │   │   ├── UpdateEmailNotification.php
│   │   │   └── VerifyEmailCodeNotification.php
│   │   ├── Channels/
│   │   │   ├── AppDatabaseChannel.php
│   │   │   └── AppTelegramChannel.php
│   │   ├── Listeners/
│   │   │   └── SendTelegramLoginNotification.php
│   │   ├── Observers/
│   │   │   └── NotificationTypeObserver.php
│   │   ├── Enums/
│   │   │   ├── NotificationCategory.php
│   │   │   └── NotificationChannel.php
│   │   └── Jobs/
│   │       ├── PurgeOldNotificationsJob.php
│   │       └── SendAppNotificationJob.php
│   │
│   └── Directory/
│       └── Services/
│           ├── DirectoryTreeBuilder.php
│           └── DirectoryAuthorizer.php
│
├── Application/
│   ├── Web/
│   │   ├── Controllers/
│   │   │   ├── Controller.php
│   │   │   ├── DashboardController.php
│   │   │   ├── HealthController.php
│   │   │   ├── ExportManagerController.php
│   │   │   ├── Auth/
│   │   │   │   └── ... (all auth controllers, incl. AuthController, TwoFactorController, UpdateEmailController moved from root)
│   │   │   ├── Profile/
│   │   │   │   ├── ... (all profile controllers)
│   │   │   │   └── Settings/
│   │   │   │       ├── ... (all settings controllers)
│   │   │   │       └── TwoFactor/
│   │   │   │           └── ... (all 2FA controllers)
│   │   │   └── Directory/
│   │   │       └── ... (all directory controllers)
│   │   ├── Requests/
│   │   │   ├── Auth/
│   │   │   ├── Profile/
│   │   │   ├── Developer/
│   │   │   ├── Directory/
│   │   │   ├── Groups/
│   │   │   ├── TwoFactor/
│   │   │   └── ... (loose request files)
│   │   ├── Middleware/
│   │   │   └── ... (all middleware)
│   │   └── Kernel.php
│   │
│   ├── Api/
│   │   ├── V1/
│   │   │   ├── Controllers/
│   │   │   ├── Resources/
│   │   │   └── Requests/
│   │   └── V2/
│   │       ├── Controllers/
│   │       ├── Resources/
│   │       └── Requests/
│   │
│   ├── Filament/
│   │   └── ... (stays as-is)
│   │
│   ├── Console/
│   │   ├── Commands/
│   │   │   ├── User/
│   │   │   └── ...
│   │   └── Kernel.php
│   │
│   └── Telegram/
│       └── Handlers/
│           └── ...
│
├── Support/
│   ├── Models/
│   │   ├── Concerns/
│   │   │   └── HasHashid.php
│   │   └── Scopes/
│   │       └── HashidScope.php
│   ├── Auth/
│   │   ├── AdminAuth.php
│   │   └── ApiGuard.php
│   ├── ScopeChecker.php
│   └── StaffProfile/
│       └── ConsentNotice.php
│
├── Providers/
│   └── ... (stays as-is)
│
├── Exceptions/
│   └── Handler.php
│
└── helpers.php
```

## Namespace Mapping

All namespaces stay under `App\` — no custom autoloading needed.

| Old Namespace | New Namespace |
|---|---|
| `App\Models\User` | `App\Domain\User\Models\User` |
| `App\Models\Group` | `App\Domain\Group\Models\Group` |
| `App\Models\App` | `App\Domain\OAuth\Models\App` |
| `App\Models\Convention` | `App\Domain\Convention\Models\Convention` |
| `App\Models\NotificationType` | `App\Domain\Notification\Models\NotificationType` |
| `App\Services\RegistrationService` | `App\Domain\User\Services\RegistrationService` |
| `App\Services\Hydra\Admin` | `App\Domain\OAuth\Services\Hydra\Admin` |
| `App\Events\GroupCreated` | `App\Domain\Group\Events\GroupCreated` |
| `App\Http\Controllers\Auth\LoginController` | `App\Application\Web\Controllers\Auth\LoginController` |
| `App\Http\Controllers\Api\v2\GroupController` | `App\Application\Api\V2\Controllers\GroupController` |
| `App\Http\Requests\Auth\LoginRequest` | `App\Application\Web\Requests\Auth\LoginRequest` |
| `App\Http\Resources\V2\GroupResource` | `App\Application\Api\V2\Resources\GroupResource` |
| `App\Http\Middleware\Authenticate` | `App\Application\Web\Middleware\Authenticate` |
| `App\Notifications\AppNotification` | `App\Domain\Notification\Notifications\AppNotification` |
| `App\Models\Concerns\HasHashid` | `App\Support\Models\Concerns\HasHashid` |
| `App\Services\Auth\ApiGuard` | `App\Support\Auth\ApiGuard` |

## Migration Strategy

This is a **namespace-only refactor** — no logic changes, no new features, no behavioral changes. Every step should result in a passing test suite.

### Approach: Domain-by-domain migration

Migrate one domain at a time. Each domain migration is a single commit:

1. **Domain/User** — Move models, services, events, listeners, observers, enums. Update all `use` statements project-wide.
2. **Domain/Group** — Move models, events, listeners, observers, policies, enums, jobs.
3. **Domain/OAuth** — Move models, services, events, listeners, observers, policies, jobs.
4. **Domain/Convention** — Move models.
5. **Domain/Notification** — Move models, services, notifications, channels, listeners, observers, enums, jobs.
6. **Domain/Directory** — Move services.
7. **Support** — Move shared concerns (HasHashid, HashidScope, ConsentNotice, Auth services).
8. **Application/Web** — Move controllers, requests, middleware.
9. **Application/Api** — Move API controllers, resources, requests.
10. **Application/Console** — Move commands and Kernel.
11. **Application/Telegram** — Move handlers.
12. **Application/Filament** — Move Filament resources (already self-contained, mostly a namespace change).
13. **Cleanup** — Remove empty directories, verify all tests pass, update route files and service provider references.

### Key Concerns

- **Route files** must be updated to reference new controller namespaces.
- **Service provider bindings** (model observers, policies, event mappings) must be updated.
- **Filament resource `$model` properties** must point to new model namespaces. This includes `Providers/Filament/AdminPanelProvider.php` and `Providers/Filament/ConventionPanelProvider.php`.
- **Config files** referencing model classes (e.g., `auth.php`, `activitylog.php`) must be updated.
- **Database seeders and factories** — all factory `$model` properties and `use` imports must be updated. These are particularly fragile because `User::factory()` relies on the model's `HasFactory` trait resolving the correct factory class.
- **Tests** — all `use` imports must be updated. Test structure does not need to change.
- **API version casing** — the current codebase uses lowercase `v1`/`v2` for API directories. This migration normalizes to uppercase `V1`/`V2` for PSR-4 consistency. This is an intentional directory rename, not just a namespace change.
- **HTTP Kernel** (`app/Http/Kernel.php`) moves to `Application/Web/Kernel.php`. The `bootstrap/app.php` reference must be updated.
- **Root-level controllers** — `AuthController.php`, `TwoFactorController.php`, and `UpdateEmailController.php` currently sit at the controller root. They move into `Auth/` as part of this migration.
- **`helpers.php`** stays at `app/helpers.php` — no change needed.

### What Does NOT Change

- Database schema — no migrations needed.
- Public API contracts — no endpoint changes.
- Frontend — Inertia pages and Vue components are unaffected.
- Composer autoloading — standard PSR-4 under `App\` namespace, no changes to `composer.json`.
- Business logic — zero functional changes.

### Post-Migration Verification

After all steps are complete, run:

1. `composer dump-autoload` — verify no orphan classes
2. `php artisan route:list` — verify all routes resolve
3. `php artisan config:cache` — verify config compiles
4. Full test suite — verify no regressions
5. `grep -r "App\\\\Models\\\\" app/ config/ routes/ database/ tests/` — confirm no old namespace references remain
6. `grep -r "App\\\\Http\\\\" app/ config/ routes/ database/ tests/` — confirm no old HTTP namespace references remain
7. `grep -r "App\\\\Services\\\\" app/ config/ routes/ database/ tests/` — confirm no old Services namespace references remain
8. `grep -r "App\\\\Events\\\\" app/ config/ routes/ database/ tests/` — confirm no old Events namespace references remain

## Risks

| Risk | Mitigation |
|---|---|
| Missing a `use` statement causes runtime error | Run full test suite after each domain migration step |
| IDE refactoring misses dynamic class references (e.g., `config/auth.php`) | Manual review of config files, route files, and string-based class references |
| Large diff makes code review hard | One commit per domain keeps diffs focused |
| Ongoing feature branches conflict | Coordinate timing — do this when no large feature branches are in flight |
