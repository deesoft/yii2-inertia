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
            'viewFile' => '@app/views/app.php', // default @dee/inertia/views/app.php
            'serializer' => ['class' => dee\inertia\Serializer::class], //default
            'encript_history' => true, // default false
            'vite_entry_file' => 'client/app.js', // default
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

Create Url
----------
Use function ```toUrl``` to generate url from route. It's equivalent with ```yii\helpers\Url::to()```.
```js
const {toUrl} = window;

const url = toUrl('product/view', {id:row.id}); // equivalent Url::to(['/product/view', 'id'=>$row->id])
```