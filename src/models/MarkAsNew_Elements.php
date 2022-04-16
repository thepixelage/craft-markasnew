<?php

namespace thepixelage\markasnew\models;

use craft\base\Element;
use craft\base\Model;
use yii\base\InvalidConfigException;

class MarkAsNew_Elements extends Model
{
    public $id;
    private $element;

    /**
     * @throws InvalidConfigException
     */
    public function getElement(): Element
    {
        if ($this->element !== null) {
            return $this->element;
        }

        if (!$this->id)
        {
            throw new InvalidConfigException('Missing element ID');
        }

        return $this->element;
    }

    public function setElement(Element $element)
    {
        $this->element = $element;
    }
}
