<?php

namespace thepixelage\markasnew\behaviors;

use yii\base\Behavior;
use yii\base\Component;

class EntryQueryBehavior extends Behavior
{
    public $markedAsNew = null;

    public function markedAsNew($markedAsNew): Component
    {
        $this->markedAsNew = $markedAsNew;

        return $this->owner;
    }
}
