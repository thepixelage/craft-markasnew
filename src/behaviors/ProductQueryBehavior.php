<?php

namespace thepixelage\markasnew\behaviors;

use yii\base\Behavior;
use yii\base\Component;

class ProductQueryBehavior extends Behavior
{
    public ?bool $markedAsNew = null;

    public function markedAsNew($markedAsNew): Component
    {
        $this->markedAsNew = $markedAsNew;

        return $this->owner;
    }
}
