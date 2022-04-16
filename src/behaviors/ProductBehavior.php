<?php

namespace thepixelage\markasnew\behaviors;

use DateTime;
use yii\base\Behavior;

class ProductBehavior extends Behavior
{
    public bool $markedAsNew = false;
    public mixed $markedNewTillDate = null;
}
