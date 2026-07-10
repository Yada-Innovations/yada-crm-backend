<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Extends Spatie's Role model to allow customization.
 * Registered in config/permission.php under models.role.
 */
class Role extends SpatieRole
{
    // Add custom attributes or methods here if needed
}