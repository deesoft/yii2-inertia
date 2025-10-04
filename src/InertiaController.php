<?php

namespace dee\inertia;

use yii\console\Controller;
use yii\helpers\Console;
use const STDERR;
use const STDIN;
use const STDOUT;

/**
 * Description of InertiaController
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class InertiaController extends Controller
{
    /**
     * @var bool whether to overwrite all existing code files when in non-interactive mode.
     * Defaults to false, meaning none of the existing code files will be overwritten.
     * This option is used only when `--interactive=0`.
     */
    public $overwrite;
    /**
     *
     * @var string[]
     */
    public $webServices = [
        // address, root document
        'app' => ['localhost:8080', 'web'],
    ];
    /**
     *
     * @var string
     */
    public $viteCommand = 'npm run dev';
    /**
     * 
     * @var bool whether to not execute vite dev.
     * Defaults to false, meaning vite dev will be executed.
     */
    public $noVite = false;

    /**
     *
     * @param string $root
     * @param string $basePath
     * @return string[]
     */
    protected function getFileList($root, $basePath = '')
    {
        $files = [];
        $handle = opendir($root);
        while (($path = readdir($handle)) !== false) {
            if ($path === '.git' || $path === '.svn' || $path === '.' || $path === '..') {
                continue;
            }
            $fullPath = "$root/$path";
            $relativePath = $basePath === '' ? $path : "$basePath/$path";
            if (is_dir($fullPath)) {
                $files = array_merge($files, $this->getFileList($fullPath, $relativePath));
            } else {
                $files[] = $relativePath;
            }
        }
        closedir($handle);
        return $files;
    }

    public function actionIndex()
    {
        $this->run('/help', [$this->uniqueId]);
    }

    public function actionInit()
    {
        $options = [
            'y' => 'Overwrite this file.',
            'n' => 'Skip this file.',
            'ya' => 'Overwrite this and the rest of the changed files.',
            'na' => 'Skip this and the rest of the changed files.',
        ];
        if (class_exists('Diff') && class_exists('Diff_Renderer_Text_Unified')) {
            $options['v'] = 'View difference';
        }
        $source = dirname(__DIR__) . '/resources';
        $dest = dirname($_SERVER['SCRIPT_FILENAME']);
        $files = $this->getFileList($source);

        $n = count($files);
        if ($n === 0) {
            echo "No code to be generated.\n";
            return;
        }
        echo "The following files will be generated:\n";
        $skipAll = $this->interactive ? null : !$this->overwrite;
        $answers = [];
        $contents = [];
        foreach ($files as $file) {
            $srcFile = "$source/$file";
            $dstFile = "$dest/$file";
            $content = file_get_contents($srcFile);
            if (is_file($dstFile)) {
                $existingFileContents = file_get_contents($dstFile);
                if ($existingFileContents === $content) {
                    echo '  ' . $this->ansiFormat('[unchanged]', Console::FG_GREY);
                    echo $this->ansiFormat(" $file\n", Console::FG_CYAN);
                    $answers[$file] = false;
                } else {
                    echo '    ' . $this->ansiFormat('[changed]', Console::FG_RED);
                    echo $this->ansiFormat(" $file\n", Console::FG_CYAN);
                    if ($skipAll !== null) {
                        $answers[$file] = !$skipAll;
                    } else {
                        do {
                            $answer = $this->select("Do you want to overwrite this file?", $options);

                            if ($answer === 'v') {
                                $diff = new \Diff(explode("\n", $existingFileContents), explode("\n", $content));
                                echo $diff->render(new \Diff_Renderer_Text_Unified());
                            }
                        } while ($answer === 'v');

                        $answers[$file] = $answer === 'y' || $answer === 'ya';
                        if ($answer === 'ya') {
                            $skipAll = false;
                        } elseif ($answer === 'na') {
                            $skipAll = true;
                        }
                    }
                }
            } else {
                echo '        ' . $this->ansiFormat('[new]', Console::FG_GREEN);
                echo $this->ansiFormat(" $file\n", Console::FG_CYAN);
                $answers[$file] = true;
            }
            if ($answers[$file]) {
                $contents[$file] = $content;
            }
        }

        if (!array_sum($answers)) {
            $this->stdout("\nNo files were chosen to be generated.\n", Console::FG_CYAN);
            return;
        }

        if (!$this->confirm("\nReady to generate the selected files?", true)) {
            $this->stdout("\nNo file was generated.\n", Console::FG_CYAN);
            return;
        }
        foreach ($contents as $file => $content) {
            file_put_contents("$dest/$file", $content);
            $this->stdout("Generated file $file\n", Console::FG_GREEN);
        }
        $this->stdout("\nFiles were generated successfully!\n", Console::FG_GREEN);
    }

    /**
     *
     * @param string $cmd
     * @param string $title
     * @return mixed
     */
    protected function runCommand($cmd, $title)
    {
        echo "▶️ Menjalankan: $title ($cmd)\n";
        $descriptor_spec = [
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR
        ];
        $process = proc_open($cmd, $descriptor_spec, $pipes);
        if (is_resource($process)) {
            return $process;
        } else {
            $this->stdout("\nSome errors occurred while execute '$cmd'\n", Console::FG_RED);
            return null;
        }
    }

    /**
     *
     * @param string[] $apps
     */
    public function actionServe($apps = [])
    {
        if (!$apps) {
            $apps = array_keys($this->webServices);
        }
        foreach ($apps as $app) {
            if (isset($this->webServices[$app])) {
                list($address, $root) = $this->webServices[$app];
                $cmd = PHP_BINARY . " -S $address -t $root";
                $this->runCommand($cmd, "PHP Server $app");
            }
        }
        if (!$this->noVite) {
            $this->runCommand($this->viteCommand, "Vite Dev Server");
        }
        while (true) {
            sleep(1);
        }
    }

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        switch ($actionID) {
            case 'init':
                $options[] = 'overwrite';
                break;
            case 'serve':
                $options[] = 'noVite';
                break;
            default:
                break;
        }
        return $options;
    }
}
