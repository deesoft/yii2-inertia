<?php

namespace dee\inertia;

use Yii;
use yii\web\AssetBundle;

/**
 * Description of ViteAsset
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ViteAsset extends AssetBundle
{
    public $sourcePath = '@client/dist';
    public $bootstrap = 'client/app.js';

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $port = Inertia::config('vite_port');
        $bootstrap = $this->bootstrap;
        if (!Inertia::config('vite_prod') && @fopen("http://localhost:$port/{$bootstrap}", 'r')) {
            $this->sourcePath = null;
            $this->js = [
                ["http://localhost:$port/@vite/client", 'type' => 'module'],
                ["http://localhost:$port/{$bootstrap}", 'type' => 'module'],
            ];
        } else {
            $manifest_file = Yii::getAlias("{$this->sourcePath}/.vite/manifest.json");
            if (file_exists($manifest_file)) {
                $manifest = json_decode(file_get_contents($manifest_file), true);
                if (isset($manifest[$bootstrap])) {
                    $asset = $manifest[$bootstrap];
                    $this->js = [
                        [$asset['file'], 'type' => 'module'],
                    ];
                    if (isset($asset['css'])) {
                        $this->css = (array) $asset['css'];
                    }
                }
                $destPath = Yii::$app->assetManager->getPublishedPath($this->sourcePath);
                if (is_dir($destPath)) {
                    $this->publishOptions['forceCopy'] = !file_exists("$destPath/.vite/manifest.json") || filemtime("$destPath/.vite/manifest.json")
                        < filemtime($manifest_file);
                }
            }
        }
        parent::init();
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $port = Inertia::config('vite_port');
        $bootstrap = $this->bootstrap;
        $destPath = Yii::$app->assetManager->getPublishedPath($this->sourcePath);
        if ((!Inertia::config('vite_prod') && @fopen("http://localhost:$port/{$bootstrap}", 'r')) || !file_exists("$destPath/.vite/manifest.json")) {
            return md5(Yii::getVersion() . Inertia::class);
        }

        return md5(filemtime("$destPath/.vite/manifest.json"));
    }
}
