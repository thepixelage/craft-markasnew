<?php

namespace thepixelage\markasnew\behaviors;

use yii\base\Behavior;

class EntryBehavior extends Behavior
{
    public $markedAsNew = false;
    public $markedNewTillDate = null;
}
