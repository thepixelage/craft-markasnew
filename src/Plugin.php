<?php

namespace thepixelage\markasnew;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\commerce\elements\db\ProductQuery;
use craft\commerce\elements\Product;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\events\DefineBehaviorsEvent;
use craft\events\DefineGqlTypeFieldsEvent;
use craft\events\DefineHtmlEvent;
use craft\events\ModelEvent;
use craft\events\PopulateElementEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\gql\TypeManager;
use craft\gql\types\DateTime;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\services\Gql;
use GraphQL\Type\Definition\Type;
use thepixelage\markasnew\behaviors\EntryBehavior;
use thepixelage\markasnew\behaviors\EntryQueryBehavior;
use thepixelage\markasnew\behaviors\ProductBehavior;
use thepixelage\markasnew\behaviors\ProductQueryBehavior;
use thepixelage\markasnew\records\MarkAsNew_Elements as MarkAsNew_ElementsRecord;
use yii\base\Event;

/**
 * Class Plugin
 *
 * @package thepixelage\markasnew
 */
class Plugin extends \craft\base\Plugin
{
    public static Plugin $plugin;

    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->registerMetaFieldsHtml();
        $this->registerBehaviors();
        $this->registerElementAfterSaveEventHandlers();
        $this->registerEntryQueryEventHandlers();
        $this->registerProductQueryEventHandlers();
        $this->registerGqlTypeFields();
        $this->registerGqlArguments();
    }

    private function registerMetaFieldsHtml()
    {
        Event::on(
            Entry::class,
            Element::EVENT_DEFINE_META_FIELDS_HTML,
            function(DefineHtmlEvent $event) {
                /** @var Entry $entry */
                $entry = $event->sender;

                $record = MarkAsNew_ElementsRecord::find()
                    ->where(['id' => $entry->id])
                    ->one();

                $markNewTillDate = DateTimeHelper::toDateTime($record?->markedNewTillDate);

                $event->html .= Cp::dateTimeFieldHtml([
                    'label' => Craft::t('app', 'Marked New Till'),
                    'id' => 'markedNewTillDate',
                    'name' => 'markedNewTillDate',
                    'value' => $markNewTillDate,
                    'errors' => null,
                    'disabled' => false,
                ]);
            }
        );

        Craft::$app->view->hook('cp.commerce.product.edit.details', function(array &$context) {
            $record = MarkAsNew_ElementsRecord::find()
                ->where(['id' => $context['productId']])
                ->one();

            $markNewTillDate = DateTimeHelper::toDateTime($record?->markedNewTillDate);

            $field = Cp::dateTimeFieldHtml([
                'label' => Craft::t('app', 'Marked New Till'),
                'id' => 'markedNewTillDate',
                'name' => 'markedNewTillDate',
                'value' => $markNewTillDate,
                'errors' => null,
                'disabled' => false,
            ]);

            return Html::tag('div', $field, ['class' => 'meta']);
        });
    }

    private function registerBehaviors()
    {
        Event::on(
            EntryQuery::class,
            Query::EVENT_DEFINE_BEHAVIORS,
            function(DefineBehaviorsEvent $event) {
                $event->sender->attachBehaviors([
                    EntryQueryBehavior::class,
                ]);
            }
        );

        Event::on(
            Entry::class,
            Model::EVENT_DEFINE_BEHAVIORS,
            function(DefineBehaviorsEvent $event) {
                $event->sender->attachBehaviors([
                    EntryBehavior::class,
                ]);
            }
        );

        Event::on(
            ProductQuery::class,
            Query::EVENT_DEFINE_BEHAVIORS,
            function(DefineBehaviorsEvent $event) {
                $event->sender->attachBehaviors([
                    ProductQueryBehavior::class,
                ]);
            }
        );

        Event::on(
            Product::class,
            Model::EVENT_DEFINE_BEHAVIORS,
            function(DefineBehaviorsEvent $event) {
                $event->sender->attachBehaviors([
                    ProductBehavior::class,
                ]);
            }
        );
    }

    private function registerElementAfterSaveEventHandlers()
    {
        Event::on(
            Element::class,
            Element::EVENT_AFTER_SAVE,
            function(ModelEvent $event) {
                /** @var Element $element */
                $element = $event->sender;

                $record = MarkAsNew_ElementsRecord::find()
                    ->where(['id' => $element->id])
                    ->one();

                $markedNewTillDateParams = Craft::$app->request->getParam('markedNewTillDate');

                if (!$markedNewTillDateParams || !$markedNewTillDateParams['date']) {
                    $record?->delete();
                    return;
                }

                if (!$record) {
                    $record = new MarkAsNew_ElementsRecord();
                    $record->id = $element->id;
                }

                $record->markedNewTillDate = DateTimeHelper::toDateTime($markedNewTillDateParams);
                $record->save(false);
            }
        );
    }

    private function registerEntryQueryEventHandlers()
    {
        Event::on(
            EntryQuery::class,
            ElementQuery::EVENT_BEFORE_PREPARE,
            [self::class, 'handleElementQueryBeforePrepare']
        );

        Event::on(
            EntryQuery::class,
            ElementQuery::EVENT_AFTER_POPULATE_ELEMENT,
            [self::class, 'handleElementQueryAfterPopulateElement']
        );
    }

    private function registerProductQueryEventHandlers()
    {
        Event::on(
            ProductQuery::class,
            ElementQuery::EVENT_BEFORE_PREPARE,
            [self::class, 'handleElementQueryBeforePrepare']
        );

        Event::on(
            ProductQuery::class,
            ElementQuery::EVENT_AFTER_POPULATE_ELEMENT,
            [self::class, 'handleElementQueryAfterPopulateElement']
        );
    }

    private function registerGqlTypeFields()
    {
        Event::on(
            TypeManager::class,
            TypeManager::EVENT_DEFINE_GQL_TYPE_FIELDS,
            function(DefineGqlTypeFieldsEvent $event) {
                if (in_array($event->typeName, ['EntryInterface', 'ProductInterface'])) {
                    $event->fields['markedAsNew'] = [
                        'name' => 'markedAsNew',
                        'type' => Type::boolean(),
                        'resolve' => function($source, $arguments, $context, $resolveInfo) {
                            return $source->markedAsNew;
                        }
                    ];
                    $event->fields['markedNewTillDate'] = [
                        'name' => 'markedNewTillDate',
                        'type' => DateTime::getType(),
                        'resolve' => function($source, $arguments, $context, $resolveInfo) {
                            return $source->markedNewTillDate;
                        }
                    ];
                }
            }
        );
    }

    private function registerGqlArguments()
    {
        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_QUERIES,
            function(RegisterGqlQueriesEvent $event) {
                $event->queries['entries']['args']['markedAsNew'] = [
                    'name' => 'markedAsNew',
                    'type' => Type::boolean(),
                    'description' => 'Narrows the query results to only entries that are marked as new.'
                ];
                $event->queries['products']['args']['markedAsNew'] = [
                    'name' => 'markedAsNew',
                    'type' => Type::boolean(),
                    'description' => 'Narrows the query results to only products that are marked as new.'
                ];
            }
        );
    }

    public static function handleElementQueryBeforePrepare(Event $event)
    {
        /** @var EntryQuery $query */
        $query = $event->sender;

        $query->leftJoin(['markasnew_elements' => '{{%markasnew_elements}}'], "[[markasnew_elements.id]] = [[elements.id]]");
        $query->addSelect(['markasnew_elements.markedNewTillDate']);

        if (isset($query->markedAsNew)) {
            if ($query->markedAsNew === true) {
                $query->subQuery->andWhere(['>=', 'markasnew_elements.markedNewTillDate', DateTimeHelper::currentUTCDateTime()->format('Y-m-d H:i:s')]);
            } else {
                $query->subQuery->andWhere([
                    'or',
                    ['markasnew_elements.markedNewTillDate' => null],
                    ['<', 'markasnew_elements.markedNewTillDate', DateTimeHelper::currentUTCDateTime()->format('Y-m-d H:i:s')]
                ]);
            }
        }
    }

    public static function handleElementQueryAfterPopulateElement(PopulateElementEvent $event)
    {
        $event->element->markedNewTillDate = DateTimeHelper::toDateTime($event->element->markedNewTillDate);
        $event->element->markedAsNew = $event->element->markedNewTillDate && $event->element->markedNewTillDate >= DateTimeHelper::currentUTCDateTime();
    }
}
