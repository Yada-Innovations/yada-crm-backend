<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Extends Spatie's Permission model to allow customization.
 * Registered in config/permission.php under models.permission.
 */
class Permission extends SpatiePermission
{
    // Add custom attributes or methods here if needed
}