<?php

namespace App\Core\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;


trait ChecksForeignKeyReference
{
    /**
     * Check if a foreign key is referenced in any table
     *
     * @param mixed $id
     * @param string $foreignKey
     * @return bool
     */
    protected function isForeignKeyReferenced($id, $foreignKey)
    {
        $modelsPath = app_path('Models'); // Directory where models are located
        $modelsWithColumns = [];

        // Scan all model files in the Models directory
        foreach (File::allFiles($modelsPath) as $file) {
            $namespace = 'App\\Models\\';
            $class = $namespace . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            // Check if the class exists and is a subclass of Eloquent Model
            if (class_exists($class) && is_subclass_of($class, \Illuminate\Database\Eloquent\Model::class)) {
                $instance = new $class;
                $table = $instance->getTable(); // Get the table name for the model

                // Check if the table exists
                if (Schema::hasTable($table)) {
                    $columns = Schema::getColumnListing($table);

                    // Check if the foreign key column exists in the table
                    if (in_array($foreignKey, $columns)) {
                        // Check if any record references the foreign key and is not soft deleted
                        if ($instance->where($foreignKey, $id)->whereNull('deleted_at')->exists()) {
                            return true; // Foreign key is referenced
                        }
                    }
                }
            }
        }

        return false; // No foreign key reference found
    }
}
