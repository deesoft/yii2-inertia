Yii2 Inertia Adapter
===================
This is the Yii 2 server-side adapter for [Inertia](https://inertiajs.com).

With Inertia you are able to build single-page apps using classic server-side routing and controllers, without building an API. 
 
See [client-side setup](https://inertiajs.com/client-side-setup) for client instalation.


Installation
------------

The preferred way to install this extension is through [composer](https://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist deesoft/yii2-inertia "*"
```

or add

```
"deesoft/yii2-inertia": "*"
```

to the require section of your `composer.json` file.

Initialization
--------------

```
# php yii inertia/init
```

Usage
-----

Once the extension is installed, simply use it in your controller  :

```php
    public function actionIndex()
    {
        $query = User::find();
        $request = Yii::$app->getRequest();
        $query->andFilterWhere([
            'id' => $request->get('id'),
            'active' => $request->get('active'),
        ]);

        $query->andFilterWhere(['ilike', 'username', $request->get('username')])
            ->andFilterWhere(['ilike', 'email', $request->get('email')])
            ->andFilterWhere(['ilike', 'phone', $request->get('phone')]);

        if ($q = $request->get('q')) {
            $query->andWhere([
                'OR',
                ['ilike', 'username', $q],
                ['ilike', 'email', $q],
                ['ilike', 'phone', $q],
            ]);
        }

        $sortAttrs = [
            'id',
            'username',
            'email',
            'phone',
        ];
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'attributes' => $sortAttrs,
            ]
        ]);
        return Inertia::render('user/index', [
                'data' => $dataProvider
        ]);
    }

    public function actionCreate()
    {
        $model = new User();

        if ($this->request->isPost) {
            if ($model->load($this->request->post(), '') && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return Inertia::render('user/create', [
            'model' => $model,
        ]);        
    }

// defered prop
        return Inertia::render('user/index', [
                'data' => Inertia::defer(function(){
                    return $dataProvider;
                }),
        ]);
// other prop
        return Inertia::render('user/index', [
                'data' => Inertia::scroll(User::find()->where(['active' => true]))->merge(),
                'warehouses' => Inertia::once(function(){
                    return Warehouse::find()->all();
                }),
                'prop1' => Inertia::optional(fn() => Branch::find()->all()),
                'prop2' => Inertia::merge(fn() => Branch::find()->all())->prepend(),
                'prop3' => function(){
                    return Branch::find()->all();
                }
        ]);
```

Configuration
-------------
Add configuration to Application $params config.
```php
    'components' => [
        ...
    ],
    'params' => [
        'inertia' => [
            'tag' => 'div', // default div
            'id' => 'app', // default app
            'register_vite_asset' => true, // set false when you want handle your vite asset
            'view_file' => '@app/views/app.php', // default @dee/inertia/views/app.php
            'encrypt_history' => true, // default false
        ],
        'inertia.shared' => [
            'user' => function(){
                if(!Yii::$app->user->isGuest){
                    return ['id' => Yii::$app->user->id];
                }
            }
        ],
    ],
```

Vite Asset Bundle
-----------------

If you want to handle vite asset with your own, set `Yii::$app->params['inertia.register_vite_asset']` to `false`.
To use `ViteAsset` bundle, ensure in `vite.config.js` value of `build.manifest` is `true`.
```js
// vite.config.js

        ...
        build: {
            rollupOptions: {
                input: [
                    'client/app.js',
                ],
            },
            manifest: true,
            outDir: 'client/dist',
        },
```

```php
// config/web.php

    'components' => [
        ...
        'assetManager' => [
            'bundles' => [
                \dee\inertia\ViteAsset::class => [
                    'bootstrap' => 'client/app.js', // same as build.rollupOptions.input
                    'sourcePath' => '@client/dist', // same as build.outDir
                ]
            ]
        ]
    ]
```



Create Url
----------
Use function ```yiiUrl``` to generate url from route. It's equivalent with ```yii\helpers\Url::to()```.
```js
const {yiiUrl} = window;

const url = yiiUrl('product/view', {id: row.id}); // equivalent Url::to(['/product/view', id' => $row->id])
```