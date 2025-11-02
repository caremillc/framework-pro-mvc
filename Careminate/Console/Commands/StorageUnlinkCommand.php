<?php declare(strict_types=1);
namespace Careminate\Console\Commands;

use Careminate\Support\Console\Traits\CommandHelper;

class StorageUnlinkCommand
{
    use CommandHelper;

    protected string $name = 'storage:unlink';
    protected string $description = 'Remove the symbolic link or junction at "public/storage". Supports --force and --yes.';

    public function handle(array $args = []): void
    {
        $basePath = realpath(__DIR__ . '/../../../../'); // Adjust to framework root
        $link = $basePath . '/public/storage';

        $force = in_array('--force', $args);
        $yes = in_array('--yes', $args);
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if (!file_exists($link)) {
            $this->info("No [public/storage] link exists to remove.");
            return;
        }

        $isLink = is_link($link);
        $isJunction = $isWindows && is_dir($link);

        // If --force is used and not a link/junction, confirm unless --yes passed
        if ($force && !$isLink && !$isJunction) {
            if (!$yes && !$this->confirm("The [public/storage] directory is not a link. Delete it anyway? [yes/no]: ")) {
                $this->info("Aborted.");
                return;
            }
        }

        try {
            $success = false;

            if (is_dir($link)) {
                $success = rmdir($link);
            } elseif (is_file($link) || $isLink) {
                $success = unlink($link);
            }

            if (!$success) {
                $this->error("Failed to remove [public/storage]. Check permissions or use --force.");
                return;
            }

            $this->info("The [public/storage] link has been removed.");
        } catch (\Exception $e) {
            $this->error("Error removing [public/storage]: " . $e->getMessage());
        }
    }
}
