<?php

namespace tests;

use dee\inertia\Serializer;
use yii\base\DynamicModel;
use yii\data\ArrayDataProvider;
use yii\helpers\Url;

class SerializerTest extends TestCase
{
    public function testModel()
    {
        $model = new DynamicModel([
            'field1' => 'one',
            'field2' => 'two',
        ]);
        $res = Serializer::serialize(['model' => $model]);
        $this->assertEquals(['model' => [
            'field1' => 'one',
            'field2' => 'two',
        ]], $res);
        $model->addError('field1', 'Error');
        
        $res = Serializer::serialize(['model' => $model]);
        $this->assertEquals([
            'model' => [
                'field1' => 'one',
                'field2' => 'two',
            ],
            'errors' => [
                'field1' => 'Error'
            ]
        ], $res);
    }

    public function testDataProvider()
    {
        $this->mockController();
        $rows = array_map(fn($v) => ['c1' => $v, 'c2' => 2*$v], range(1, 20));
        $data = new ArrayDataProvider([
            'allModels' => $rows,
            'pagination' => [
                'pageSize' => 10,
            ]
        ]);
        $sub = array_slice($rows, 0, 10);
        $res = Serializer::serialize($data);
        $this->assertEquals([
            'items' => $sub,
            'meta' => [
                'currentPage' => 1,
                'pageCount' => 2,
                'totalCount' => 20,
                'perPage' => 10,            
            ],
            'links' => [
                ['href' => Url::current(['page' => 1, 'per-page' => 10]), 'label' => 'first', 'active' => true],
                ['href' => Url::current(['page' => 1, 'per-page' => 10]), 'label' => '1', 'active' => true],
                ['href' => Url::current(['page' => 2, 'per-page' => 10]), 'label' => '2', 'active' => false],
                ['href' => Url::current(['page' => 2, 'per-page' => 10]), 'label' => 'next', 'active' => false],
                ['href' => Url::current(['page' => 2, 'per-page' => 10]), 'label' => 'last', 'active' => false],
            ]
        ], $res);
    }
}