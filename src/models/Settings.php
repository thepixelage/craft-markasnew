<?php

namespace thepixelage\markasnew\models;

use craft\base\Model;

class Settings extends Model
{
    public array $excludeTypes = [];
    public array $includeTypes = [];
}
