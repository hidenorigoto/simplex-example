<?php
 
namespace Simplex\Tests;
 
use Simplex\Framework;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;

set_include_path(__DIR__ .'/../../../vendor/phake/src/'.PATH_SEPARATOR.get_include_path());
require_once 'Phake.php';

class phake_FrameworkTest extends \PHPUnit_Framework_TestCase
{
    public function testNotFoundHandling()
    {
        $framework = $this->getFrameworkForException(new ResourceNotFoundException());
 
        $response = $framework->handle(new Request());
 
        $this->assertEquals(404, $response->getStatusCode());
    }
 
    protected function getFrameworkForException($exception)
    {
        $matcher = \Phake::mock('Symfony\Component\Routing\Matcher\UrlMatcherInterface');
        $resolver = \Phake::mock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');

        \Phake::when($matcher)->match(\Phake::anyParameters())->thenThrow($exception);
 
        return new Framework($matcher, $resolver);
    }

    public function testErrorHandling()
    {
        $framework = $this->getFrameworkForException(new \RuntimeException());
 
        $response = $framework->handle(new Request());
 
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testControllerResponse()
    {
        $matcher = \Phake::mock('Symfony\Component\Routing\Matcher\UrlMatcherInterface');
        $resolver = \Phake::mock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');

        $request = new Request();
        $name = 'Fabien';
        $arguments = array('name' => $name);
        $controller = function ($name) {
            return new Response('Hello '.$name);
        };

        \Phake::when($matcher)->match(\Phake::anyParameters())->thenReturn(
            array_merge($arguments,
            array(
                '_route' => 'foo',
                '_controller' => $controller,
            )))
        ;

        \Phake::when($resolver)->getController($request)->thenReturn($controller);
        \Phake::when($resolver)->getArguments($request, $controller)->thenReturn($arguments);

        $framework = new Framework($matcher, $resolver);
        $response = $framework->handle($request);
     
        \Phake::verify($resolver)->getController($request);
        \Phake::verify($resolver)->getArguments($request, $controller);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Hello Fabien', $response->getContent());
    }
}

