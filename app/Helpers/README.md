# Helpers Directory

## UnderConstruction Helper

### Usage in Public Pages

Add to the top of any public-facing page:

```php
require_once __DIR__ . '/bootstrap/app.php';

use App\Helpers\UnderConstruction;
UnderConstruction::show();

// Your page code here...
```

### What It Does

- ✅ Shows construction page to public visitors (if enabled)
- ✅ Allows admin panel access (bypasses automatically)
- ✅ Allows API access (bypasses automatically)
- ✅ Allows logged-in admin users to see full site

### Methods

- `UnderConstruction::isEnabled()` - Check if mode is enabled
- `UnderConstruction::shouldBypass()` - Check if current request should bypass
- `UnderConstruction::show()` - Show construction page if needed
- `UnderConstruction::enable()` - Enable construction mode
- `UnderConstruction::disable()` - Disable construction mode

## PageHelper (Future)

Helper for common page initialization tasks.

---

**Note:** Always add `UnderConstruction::show()` to new public pages to ensure they respect the construction mode.

