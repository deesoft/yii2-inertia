<?php

namespace dee\inertia\gii;

use yii\helpers\Html;

/**
 * Description of CodeFile
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class CodeFile extends \yii\gii\CodeFile
{


    /**
     * Returns preview or false if it cannot be rendered
     *
     * @return bool|string
     */
    public function preview()
    {
        if (($pos = strrpos($this->path, '.')) !== false) {
            $type = substr($this->path, $pos + 1);
        } else {
            $type = 'unknown';
        }

        if (in_array($type, ['php', 'vue', 'ts', 'js'])) {
            return highlight_string($this->content, true);
        } elseif (!in_array($type, ['jpg', 'gif', 'png', 'exe'])) {
            return nl2br(Html::encode($this->content));
        }

        return false;
    }
}
