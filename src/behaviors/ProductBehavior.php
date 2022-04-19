<?php

namespace thepixelage\markasnew\behaviors;

use yii\base\Behavior;

class ProductBehavior extends Behavior
{
    public $markedAsNew = false;
    public $markNewUntilDate = null;
}
