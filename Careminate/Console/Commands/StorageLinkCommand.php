<?php declare(strict_types=1);
namespace Careminate\Console\Commands;

use Careminate\Support\Console\Traits\CommandHelper;

class StorageLinkCommand
{
    use CommandHelper;

    protected string $name        = 'storage:link';
    protected string $description = 'Create a symbolic link or junction from "public/storage" to "storage/app/public".';

    public function handle(array $args = []): void
    {
        $basePath = realpath(__DIR__ . '/../../../../');
        $target   = $basePath . '/storage/app/public';
        $link     = $basePath . '/public/storage';

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if (! is_dir($target)) {
            $this->error("The target directory [$target] does not exist.");
            return;
        }

        if (file_exists($link)) {
            $this->info("The [public/storage] link already exists.");
            return;
        }

        try {
            // Simulate progress for demonstration
            $totalSteps = 10;
            for ($i = 0; $i <= $totalSteps; $i++) {
                usleep(100000); // Sleep 0.1 sec to simulate work
                $this->progress($i, $totalSteps, "Creating link...");
            }

            if ($isWindows) {
                $cmd = sprintf('mklink /J "%s" "%s"', $link, $target);
                shell_exec("cmd /c " . $cmd);

                if (! is_dir($link)) {
                    $this->error("Failed to create junction. Try running as administrator.");
                    return;
                }
            } else {
                symlink($target, $link);
                if (! is_link($link)) {
                    $this->error("Failed to create symlink.");
                    return;
                }
            }

            $this->info("The [public/storage] directory has been linked.");
        } catch (\Exception $e) {
            $this->error("Error creating symbolic link: " . $e->getMessage());
        }
    }

}

