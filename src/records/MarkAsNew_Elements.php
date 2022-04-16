<?php

namespace thepixelage\markasnew\records;

use craft\base\Element;
use craft\db\ActiveRecord;
use DateTime;
use thepixelage\markasnew\db\Table;
use yii\db\ActiveQueryInterface;

/**
 * Class MarkAsNew_Elements record.
 *
 * @property int $elementId Element ID
 * @property DateTime $markedNewTillDate Marked As New Till date
 */
class MarkAsNew_Elements extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::MARKASNEW_ELEMENTS;
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
