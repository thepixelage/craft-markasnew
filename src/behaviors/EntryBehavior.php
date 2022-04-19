<?php

namespace thepixelage\markasnew\behaviors;

use yii\base\Behavior;

class EntryBehavior extends Behavior
{
    public bool $markedAsNew = false;
    public mixed $markNewUntilDate = null;
}
