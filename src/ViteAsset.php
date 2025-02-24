<?php

namespace dee\inertia;

use Yii;
use yii\helpers\ArrayHelper;
use yii\web\AssetBundle;

/**
 * Description of ViteAsset
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ViteAsset extends AssetBundle
{
    public $baseUrl = '@web/dist';
    public function init()
    {
        $port = env('VITE_PORT', 5173);
        $bootstrap = ArrayHelper::getValue(Yii::$app->params, 'inertia.vite_entry_file', 'client/app.js');
        if (!env('VITE_PROD') && @fopen("http://localhost:$port/{$bootstrap}", 'r')) {
            $this->js = [
                ["http://localhost:$port/@vite/client", 'type' => 'module'],
                ["http://localhost:$port/{$bootstrap}", 'type' => 'module'],
            ];
        } else {
            $manifest = Yii::getAlias('@webroot/dist/.vite/manifest.json');
            if (file_exists($manifest)) {
                $manifest = json_decode(file_get_contents($manifest), true);
                if (isset($manifest[$bootstrap])) {
                    $asset = $manifest[$bootstrap];
                    $this->js = [
                        [$asset['file'], 'type' => 'module'],
                    ];
                    if(isset($asset['css'])){
                        $this->css = (array)$asset['css'];
                    }
                }
            }
        }
    }
}
