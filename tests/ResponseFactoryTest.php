<?php
namespace tests;

use dee\inertia\Inertia;

class ResponseFactoryTest extends TestCase
{
    public function testResolve()
    {
        $this->mockController();
        $factory = Inertia::render('test', [
            'merge' => Inertia::merge(['c1' => 1]),
            'defer' => Inertia::defer(fn() => ['c2' => 2])->merge(),
            'defer2' => Inertia::defer(fn() => ['c2' => 2], 'test'),
            'defer3' => Inertia::defer(fn() => ['c2' => 2], 'test'),
        ]);
        $res = $factory->data();
        $this->assertEquals(['c1' => 1], $res['props']['merge']);
        $this->assertEquals(['merge', 'defer'], $res['mergeProps']);
        $this->assertEquals(['' => ['defer'], 'test' => ['defer2', 'defer3']], $res['deferredProps']);
    }

    public function testNested()
    {
        $this->mockController();
        $factory = Inertia::render('test', [
            'nested' => function(){
                return [
                    'merge' => Inertia::merge(['c1' => 1]),
                    'defer' => Inertia::defer(fn() => ['c2' => 2])->merge(),
                    'defer2' => Inertia::defer(fn() => ['c2' => 2], 'test'),
                    'defer3' => Inertia::defer(fn() => ['c2' => 2], 'test'),
                ];
            },
        ]);
        $res = $factory->data();
        $this->assertEquals(['merge' => ['c1' => 1]], $res['props']['nested']);
        $this->assertEquals(['nested.merge', 'nested.defer'], $res['mergeProps']);
        $this->assertEquals(['' => ['nested.defer'], 'test' => ['nested.defer2', 'nested.defer3']], $res['deferredProps']);
    }
}