<?php declare (strict_types = 1);
namespace Careminate\Tests\Unit;

use Careminate\Container\Container;
use Careminate\Exceptions\ContainerException;
use Careminate\Tests\Unit\DependantClass;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
     public function test_services_can_be_recursively_autowired()
    {
        $container = new Container();

        $dependantService = $container->get(DependantClass::class);

        $dependancyService = $dependantService->getDependency();

        $this->assertInstanceOf(DependencyClass::class, $dependancyService);
        $this->assertInstanceOf(SubDependencyClass::class, $dependancyService->getSubDependency());
    }

    public function test_a_service_can_be_retrieved_from_the_container()
    {
        // Setup
        $container = new Container();

        // Do something
        // id string, concrete class name string | object
        $container->bind('dependant-class', DependantClass::class);

        // Make assertions
        $this->assertInstanceOf(DependantClass::class, $container->get('dependant-class'));
    }

    public function test_a_ContainerException_is_thrown_if_a_service_cannot_be_found()
    {
        $container = new Container();

        $this->expectException(ContainerException::class);

                                   // Do NOT call add(). Instead, try to resolve something that doesn't exist:
        $container->get('foobar'); // this will trigger Container::build() and throw the exception
    }

    public function test_can_check_if_the_container_has_a_service(): void
    {
        // Setup
        $container = new Container();

        // Do something
        $container->bind('dependant-class', DependantClass::class);

        $this->assertTrue($container->has('dependant-class'));
        $this->assertFalse($container->has('non-existent-class'));
    }
    
    

    public function test_it_works()
    {
        $this->assertTrue(true);
    }
    
}
