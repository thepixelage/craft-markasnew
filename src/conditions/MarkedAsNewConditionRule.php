<?php

namespace thepixelage\markasnew\conditions;

use Craft;
use craft\base\conditions\BaseLightswitchConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;

class MarkedAsNewConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{

    public function getLabel(): string
    {
        return Craft::t('app', 'Marked As New');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['markedAsNew'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->markedAsNew($this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $element->markedAsNew;
    }
}
