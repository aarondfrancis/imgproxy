# Path Validation

Path validation restricts which files can be served through ImgProxy. This is important for security—without validation, any file on the configured disk could potentially be accessed.

## Built-in Validators

ImgProxy includes a `PathValidator` class with chainable methods:

```php
use AaronFrancis\ImgProxy\PathValidator;
```

### Directory Validation

Restrict to specific directories:

```php
'validator' => PathValidator::directories(['images', 'uploads'])
```

Only paths starting with `images/` or `uploads/` are allowed:
- `/w=400/source/images/photo.jpg` — allowed
- `/w=400/source/uploads/avatar.png` — allowed
- `/w=400/source/private/secret.jpg` — blocked (403)

### Extension Validation

Restrict to specific file extensions:

```php
'validator' => PathValidator::extensions(['jpg', 'png', 'webp'])
```

- `/w=400/source/photo.jpg` — allowed
- `/w=400/source/image.webp` — allowed
- `/w=400/source/document.pdf` — blocked (403)

### Pattern Matching

Use glob-style patterns for flexible matching:

```php
'validator' => PathValidator::matches(['**/*.jpg', '**/*.png'])
```

Pattern syntax:
- `*` matches any characters except `/`
- `**` matches any characters including `/`
- `?` matches a single character

Examples:
```php
// All JPGs in any subdirectory
PathValidator::matches(['**/*.jpg'])

// Images only in the photos directory
PathValidator::matches(['photos/*.jpg', 'photos/*.png'])

// Specific naming pattern
PathValidator::matches(['user-*/avatar.*'])
```

## Chaining Validators

Methods can be chained in any order:

```php
// Directory + extension
PathValidator::directories(['uploads'])->extensions(['jpg', 'png'])

// Extension + directory (same result)
PathValidator::extensions(['jpg', 'png'])->directories(['uploads'])

// All three
PathValidator::directories(['images'])
    ->extensions(['jpg', 'png', 'webp'])
    ->matches(['**/*'])
```

When chained, **all** conditions must pass.

## Root Path Behavior

When a source has a `root` configured, the validator receives the **full path including the root**:

```php
'sources' => [
    'uploads' => [
        'disk' => 'public',
        'root' => 'user-uploads',
        'validator' => PathValidator::directories(['user-uploads/images']),
    ],
],
```

Request: `/w=400/uploads/images/photo.jpg`
Path passed to validator: `user-uploads/images/photo.jpg`

This allows you to validate the complete filesystem path.

## Custom Validators

For complex validation logic, implement `PathValidatorContract`:

```php
<?php

namespace App\Validators;

use AaronFrancis\ImgProxy\Contracts\PathValidatorContract;

class UserImageValidator implements PathValidatorContract
{
    public function validate(string $path): bool
    {
        // Only allow images in user directories
        if (!preg_match('/^users\/\d+\//', $path)) {
            return false;
        }

        // Block certain filenames
        if (str_contains($path, 'private')) {
            return false;
        }

        // Check file extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp']);
    }
}
```

Register in config:

```php
'sources' => [
    'users' => [
        'disk' => 'public',
        'validator' => \App\Validators\UserImageValidator::class,
    ],
],
```

### Callable Validators

You can also use a closure (useful for simple cases):

```php
'validator' => function (string $path): bool {
    return str_starts_with($path, 'public/');
},
```

## Built-in Security

ImgProxy always blocks directory traversal attacks regardless of your validator:

```
/w=400/images/../../../etc/passwd
```

This returns 403 Forbidden before your validator is even called.

## Validation Flow

1. **Route matching** — Source must exist in config (404 if not)
2. **Traversal check** — Block `..` in path (403)
3. **Root prepending** — Add `root` prefix if configured
4. **Custom validation** — Run your validator on full path (403 if fails)
5. **File loading** — Load from disk (404 if not found)

## Examples

### Public images only

```php
'public' => [
    'disk' => 'public',
    'validator' => PathValidator::directories(['images']),
],
```

### User uploads with extension restriction

```php
'uploads' => [
    'disk' => 'public',
    'root' => 'uploads',
    'validator' => PathValidator::extensions(['jpg', 'png', 'gif', 'webp']),
],
```

### S3 bucket with multiple allowed directories

```php
's3' => [
    'disk' => 's3',
    'validator' => PathValidator::directories(['products', 'categories', 'blog']),
],
```

### Complex pattern matching

```php
'media' => [
    'disk' => 's3',
    'validator' => PathValidator::matches([
        'products/*/images/*',
        'blog/*/featured.*',
        'users/*/avatar.*',
    ]),
],
```
