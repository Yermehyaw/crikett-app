<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class RepositoryAutoBindProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->bindRepositories(app_path('Repositories'));
        $this->bindServices(app_path('Services'));
    }

    /**
     * Auto-bind repository interfaces to their implementations
     * Pattern: Repository -> RepositoryImplement
     */
    private function bindRepositories(string $path): void
    {
        $files = File::allFiles($path);

        foreach ($files as $file) {
            $fileName = $file->getFilename();

            if (! str_ends_with($fileName, 'Repository.php') || str_ends_with($fileName, 'RepositoryImplement.php')) {
                continue;
            }

            $relativePath = str_replace('/', '\\', $file->getRelativePath());
            $className = pathinfo($fileName, PATHINFO_FILENAME);

            $interfaceClass = 'App\\Repositories\\'.($relativePath ? $relativePath.'\\' : '').$className;
            $interfaceClass = str_replace('\\\\', '\\', $interfaceClass);

            $implementClass = 'App\\Repositories\\'.($relativePath ? $relativePath.'\\' : '').$className.'Implement';
            $implementClass = str_replace('\\\\', '\\', $implementClass);

            if (interface_exists($interfaceClass) && class_exists($implementClass)) {
                $this->app->bind($interfaceClass, $implementClass);
            }
        }
    }

    /**
     * Auto-bind service interfaces to their implementations
     * Pattern: Service -> ServiceImplement
     */
    private function bindServices(string $path): void
    {
        $files = File::allFiles($path);

        foreach ($files as $file) {
            $fileName = $file->getFilename();

            if (! str_ends_with($fileName, 'Service.php') || str_ends_with($fileName, 'ServiceImplement.php')) {
                continue;
            }

            $relativePath = str_replace('/', '\\', $file->getRelativePath());
            $className = pathinfo($fileName, PATHINFO_FILENAME);

            $interfaceClass = 'App\\Services\\'.($relativePath ? $relativePath.'\\' : '').$className;
            $interfaceClass = str_replace('\\\\', '\\', $interfaceClass);

            $implementClass = 'App\\Services\\'.($relativePath ? $relativePath.'\\' : '').$className.'Implement';
            $implementClass = str_replace('\\\\', '\\', $implementClass);

            if (interface_exists($interfaceClass) && class_exists($implementClass)) {
                $this->app->bind($interfaceClass, $implementClass);
            }
        }
    }
}
