<?php declare (strict_types = 1);
namespace Careminate\Console;

use Psr\Container\ContainerInterface;

final class Kernel
{
     public function __construct(
        private ContainerInterface $container,
        private Application $application
    ){}

    public function handle(): int
    {
        // Register commands with the container
        $this->registerCommands();

        // Run the console application, returning a status code
        $status = $this->application->run();

        // return the status code
        return $status;
    }

    private function registerCommands(): void
    {
        // === Register All Built In Commands ===
        $commandFiles = new \DirectoryIterator(__DIR__ . '/Commands');
        $namespace    = $this->container->get('base-commands-namespace');
// dd($commandFiles);
        foreach ($commandFiles as $commandFile) {
            if (! $commandFile->isFile() || $commandFile->getExtension() !== 'php') {
                continue;
            }

            // Extract class name
            $filename = pathinfo($commandFile->getFilename(), PATHINFO_FILENAME);
            $command  = $namespace . $filename;
 //dd($command);
            // If it implements CommandInterface
            if (is_subclass_of($command, \Careminate\Console\Commands\CommandInterface::class)) {
                $ref = new \ReflectionClass($command);

                // Make sure the class has a "name" property
                $prop = $ref->getProperty('name');
                $prop->setAccessible(true);
                // $commandName = $prop->getValue(new $command());
                 $commandName = (new \ReflectionClass($command))->getProperty('name')->getDefaultValue();

                $this->container->add($commandName, $command);
            }
        }

        // === Register all user-defined commands (@todo) ===
    }

}
