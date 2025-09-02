## Project Overview
Teleskope Affinities is a multi-tenant enterprise application for managing Employee Resource Groups (ERGs) with four variants: 
- Affinities - ERG management (https://subdomain).affinities.io) 
- office Ravenee - office communications (https://subdomain).officeraven.io) 
- Talent Peak - Employee mentoring (https://(subdomain).talentpeak.io) 
- Pre-joiner and alumni engagement (https://(subdomain).peoplehero.io) s=people Hero
All variants share the same codebase in '/affinity/' with zone-based configuration determining enabled modules.

## Coding Conventions
We use the following coding conventions: 
- All Static methods in the classes start with a capital letter 
- Non-static methods start with lowercase letter
- All method names are in camel case 
- Some methods can use within the camel case naming
## Architecture

### Directory Structure 
- `/admin/` - Admin portal code (accessed via https://(subdomain).teleskope.io) 

- `/affinity/` - Main application code serving all 4 variants
- `/user/` - Login and authentication modules 
- `include/` - Core business logic classes and models 
- `/api/` - Mobile app APT endpoints (Flutter) 
- `/eai/` - Enterprise Application Interface APIs 
- `/common/` Shared code between admin and applications 
- `/vendor/` Third-party JavaScript libraries 
- `include/libs/vendor/` - Third-party PHP libraries (Composer managed) 
- `/super/` - Super admin interface for Teleskope support 
- `/cron/` - Scheduled job scripts 
- `/email/` - Email templates 
- `/native/` - Mobile app webview code ### Core Patterns


## Copilot Agent Internal Instructions
1. Use semantic and grep searches to locate relevant code, functions, or documentation. Prefer searching for function names, class names, or comments to quickly find context.
2. When editing files, preserve the existing code structure and indentation. Use concise comments (`...existing code...`) to avoid unnecessary repetition.
3. Follow PHP conventions for syntax, error handling, and security. Use existing utility functions and common patterns found in the codebase.
4. Changes to admin functionality should be made in the `admin/` directory unless otherwise specified.
5. If adding or modifying features, check for related tests in `UnitTests/` and update or add tests as needed.
6. Update markdown documentation files when making significant changes to features or APIs.
7. The project runs on Windows with PowerShell as the default shell. Generate commands accordingly.
8. Use the local git repository for version control. When making changes, ensure diffs are calculated against the default branch (`master`).
9. Always break down user requests into actionable steps, gather context, and perform edits or provide answers until the request is fully resolved.

---
### Core Patterns

**Entry Points:**
- Admin pages: Include `/admin/head.php`
- Application pages: Include `/affinity/head.php`
- Both handle authentication, session management, and set global context