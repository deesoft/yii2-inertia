<?php

namespace dee\inertia;

use Yii;
use yii\web\AssetBundle;

/**
 * Description of Asset
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Asset extends AssetBundle
{
    public $baseUrl = '@web/dist';
    public $bootstrap = 'client/app.js';
    public function init()
    {
        $port = env('VITE_PORT', 5173);
        if (!env('VITE_PROD') && @fopen("http://localhost:$port/{$this->bootstrap}", 'r')) {
            $this->js = [
                ["http://localhost:$port/@vite/client", 'type' => 'module'],
                ["http://localhost:$port/{$this->bootstrap}", 'type' => 'module'],
            ];
        } else {
            $manifest = Yii::getAlias('@webroot/dist/.vite/manifest.json');
            if (file_exists($manifest)) {
                $manifest = json_decode(file_get_contents($manifest), true);
                if (isset($manifest[$this->bootstrap])) {
                    $asset = $manifest[$this->bootstrap];
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
