<?php

namespace dee\inertia;

use Yii;
use yii\web\AssetBundle;
use yii\helpers\FileHelper;

/**
 * Description of ViteAsset
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ViteAsset extends AssetBundle
{
    /**
     * {@inheritDoc}
     */
    public $sourcePath = '@client/dist';
    public $basePath = '@webroot/assets/dist';
    public $baseUrl = '@web/assets/dist';
    public $bootstrap = 'client/app.js';
    /**
     * {@inheritDoc}
     */
    public function init()
    {        
        parent::init();
        $port = Inertia::config('vite_port');
        $bootstrap = $this->bootstrap;
        if (!Inertia::config('vite_prod') && @fopen("http://localhost:$port/{$bootstrap}", 'r')) {
            $this->sourcePath = null;
            $this->js = [
                ["http://localhost:$port/@vite/client", 'type' => 'module'],
                ["http://localhost:$port/{$bootstrap}", 'type' => 'module'],
            ];
        } else {
            $manifest_file = "{$this->sourcePath}/.vite/manifest.json";
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
            }
        }
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $port = Inertia::config('vite_port');
        $bootstrap = $this->bootstrap;
        $destPath = $this->basePath;
        if ((!Inertia::config('vite_prod') && @fopen("http://localhost:$port/{$bootstrap}", 'r')) || !file_exists("$destPath/.vite/manifest.json")) {
            return md5(Yii::getVersion() . Inertia::class);
        }

        return md5(filemtime("$destPath/.vite/manifest.json"));
    }

    /**
     * {@inheritDoc}
     */
    public function publish($am)
    {
        if(!$this->sourcePath){
            return;
        }
        $src = $this->sourcePath;
        $dstDir = $this->basePath;
        $options = $this->publishOptions;
        if ($am->linkAssets) {
            if (!is_dir($dstDir)) {
                FileHelper::createDirectory(dirname($dstDir), $am->dirMode, true);
                try { // fix #6226 symlinking multi threaded
                    symlink($src, $dstDir);
                } catch (\Exception $e) {
                    if (!is_dir($dstDir)) {
                        throw $e;
                    }
                }
            }
        } elseif (!empty($options['forceCopy']) || ($am->forceCopy && !isset($options['forceCopy'])) || !is_dir($dstDir) || !is_file("$dstDir/.vite/manifest.json") || filemtime("$dstDir/.vite/manifest.json") < filemtime("$src/.vite/manifest.json")) {
            $opts = array_merge(
                $options,
                [
                    'dirMode' => $am->dirMode,
                    'fileMode' => $am->fileMode,
                    'copyEmptyDirectories' => false,
                ]
            );
            if (!isset($opts['beforeCopy'])) {
                if ($am->beforeCopy !== null) {
                    $opts['beforeCopy'] = $am->beforeCopy;
                } else {
                    $opts['beforeCopy'] = function ($from, $to) {
                        return strncmp(basename($from), '.', 1) !== 0;
                    };
                }
            }
            if (!isset($opts['afterCopy']) && $am->afterCopy !== null) {
                $opts['afterCopy'] = $am->afterCopy;
            }
            FileHelper::copyDirectory($src, $dstDir, $opts);
            FileHelper::createDirectory("$dstDir/.vite", $am->dirMode ?? 0775);
            copy("$src/.vite/manifest.json", "$dstDir/.vite/manifest.json");
        }
        parent::publish($am);
    }
}
