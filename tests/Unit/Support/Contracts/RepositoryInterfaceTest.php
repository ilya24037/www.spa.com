<?php

namespace Tests\Unit\Support\Contracts;

use Tests\TestCase;
use App\Support\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RepositoryInterfaceTest extends TestCase
{
    /** @test */
    public function repository_interface_has_all_required_methods()
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        
        $expectedMethods = [
            'find',
            'findOrFail', 
            'all',
            'create',
            'update',
            'delete',
            'paginate',
            'findBy',
            'findOneBy',
            'exists',
            'count',
            'where',
            'updateOrCreate'
        ];

        $actualMethods = array_map(function ($method) {
            return $method->getName();
        }, $reflection->getMethods());

        foreach ($expectedMethods as $method) {
            $this->assertContains($method, $actualMethods, "Method $method is missing from RepositoryInterface");
        }
    }

    /** @test */
    public function find_method_has_correct_signature()
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('find');
        
        $this->assertEquals('find', $method->getName());
        $this->assertEquals(1, $method->getNumberOfParameters());
        
        $param = $method->getParameters()[0];
        $this->assertEquals('id', $param->getName());
        $this->assertEquals('int', $param->getType()->getName());
    }

    /** @test */
    public function create_method_has_correct_signature()
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('create');
        
        $this->assertEquals('create', $method->getName());
        $this->assertEquals(1, $method->getNumberOfParameters());
        
        $param = $method->getParameters()[0];
        $this->assertEquals('data', $param->getName());
        $this->assertEquals('array', $param->getType()->getName());
    }

    /** @test */
    public function update_method_has_correct_signature()
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('update');
        
        $this->assertEquals('update', $method->getName());
        $this->assertEquals(2, $method->getNumberOfParameters());
        
        $params = $method->getParameters();
        $this->assertEquals('id', $params[0]->getName());
        $this->assertEquals('int', $params[0]->getType()->getName());
        $this->assertEquals('data', $params[1]->getName());
        $this->assertEquals('array', $params[1]->getType()->getName());
    }

    /** @test */
    public function paginate_method_has_correct_default_parameter()
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('paginate');
        
        $this->assertEquals('paginate', $method->getName());
        $this->assertEquals(1, $method->getNumberOfParameters());
        
        $param = $method->getParameters()[0];
        $this->assertEquals('perPage', $param->getName());
        $this->assertEquals(15, $param->getDefaultValue());
    }

    /** @test */
    public function where_method_has_optional_third_parameter()
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('where');
        
        $this->assertEquals('where', $method->getName());
        $this->assertEquals(3, $method->getNumberOfParameters());
        
        $params = $method->getParameters();
        $this->assertEquals('field', $params[0]->getName());
        $this->assertEquals('operator', $params[1]->getName());
        $this->assertEquals('value', $params[2]->getName());
        $this->assertTrue($params[2]->isOptional());
        $this->assertNull($params[2]->getDefaultValue());
    }

    /** @test */
    public function update_or_create_method_has_default_empty_array()
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('updateOrCreate');
        
        $this->assertEquals('updateOrCreate', $method->getName());
        $this->assertEquals(2, $method->getNumberOfParameters());
        
        $params = $method->getParameters();
        $this->assertEquals('attributes', $params[0]->getName());
        $this->assertEquals('values', $params[1]->getName());
        $this->assertTrue($params[1]->isOptional());
        $this->assertEquals([], $params[1]->getDefaultValue());
    }
}