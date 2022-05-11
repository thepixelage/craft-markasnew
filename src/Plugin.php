<?php /** @noinspection PhpUndefinedClassInspection */

namespace thepixelage\markasnew;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\commerce\elements\db\ProductQuery;
use craft\commerce\elements\Product;
use craft\commerce\helpers\Gql as CommerceGqlHelper;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\events\DefineBehaviorsEvent;
use craft\events\DefineGqlTypeFieldsEvent;
use craft\events\ModelEvent;
use craft\events\PopulateElementEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\SetElementTableAttributeHtmlEvent;
use craft\gql\TypeManager;
use craft\gql\types\DateTime;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Html;
use craft\services\Gql;
use craft\web\Request;
use GraphQL\Type\Definition\Type;
use thepixelage\markasnew\behaviors\EntryBehavior;
use thepixelage\markasnew\behaviors\EntryQueryBehavior;
use thepixelage\markasnew\behaviors\ProductBehavior;
use thepixelage\markasnew\behaviors\ProductQueryBehavior;
use thepixelage\markasnew\models\Settings;
use thepixelage\markasnew\records\MarkAsNew_Elements as MarkAsNew_ElementsRecord;
use yii\base\Event;

/**
 * Class Plugin
 *
 * @package thepixelage\markasnew
 */
class Plugin extends \craft\base\Plugin
{
    public static $plugin;

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
        $this->registerTableAttributes();
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    private function registerMetaFieldsHtml()
    {
        /** @var Settings $settings */
        $settings = $this->getSettings();

        Craft::$app->view->hook('cp.entries.edit.settings', function(array &$context) use ($settings) {
            $entry = $context['entry'];

            if ($settings->includeTypes) {
                $included = array_filter($settings->includeTypes, function ($type) use ($entry) {
                    return $type == sprintf('entries.%s', $entry->type->handle);
                });

                if (!$included) {
                    return null;
                }
            }

            if ($settings->excludeTypes) {
                $excluded = array_filter($settings->excludeTypes, function ($type) use ($entry) {
                    return $type == sprintf('entries.%s', $entry->type->handle);
                });

                if ($excluded) {
                    return null;
                }
            }

            $record = MarkAsNew_ElementsRecord::find()
                ->where(['id' => $context['entryId']])
                ->one();

            $markNewTillDate = $record ? DateTimeHelper::toDateTime($record->markNewUntilDate) : null;

            return Cp::dateTimeFieldHtml([
                'label' => Craft::t('app', 'Mark New Until'),
                'id' => 'markNewUntilDate',
                'name' => 'markNewUntilDate',
                'value' => $markNewTillDate,
                'errors' => null,
                'disabled' => false,
            ]);
        });

        Craft::$app->view->hook('cp.commerce.product.edit.details', function(array &$context) use ($settings) {
            if ($settings->includeTypes) {
                $included = array_filter($settings->includeTypes, function ($type) use ($context) {
                    return $type == sprintf('products.%s', $context['productTypeHandle']);
                });

                if (!$included) {
                    return null;
                }
            }

            if ($settings->excludeTypes) {
                $excluded = array_filter($settings->excludeTypes, function ($type) use ($context) {
                    return $type == sprintf('products.%s', $context['productTypeHandle']);
                });

                if ($excluded) {
                    return null;
                }
            }

            $record = MarkAsNew_ElementsRecord::find()
                ->where(['id' => $context['productId']])
                ->one();

            $markNewTillDate = $record ? DateTimeHelper::toDateTime($record->markNewUntilDate) : null;

            $field = Cp::dateTimeFieldHtml([
                'label' => Craft::t('app', 'Mark New Until'),
                'id' => 'markNewUntilDate',
                'name' => 'markNewUntilDate',
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
                if (!(Craft::$app->request instanceof Request)) {
                    return;
                }

                /** @var Element $element */
                $element = $event->sender;

                $record = MarkAsNew_ElementsRecord::find()
                    ->where(['id' => $element->id])
                    ->one();

                $markNewUntilDateParams = Craft::$app->request->getParam('markNewUntilDate');

                if (!$markNewUntilDateParams || !isset($markNewUntilDateParams['date']) || !$markNewUntilDateParams['date']) {
                    if ($record) {
                        $record->delete();
                    }

                    return;
                }

                if (!$record) {
                    $record = new MarkAsNew_ElementsRecord();
                    $record->id = $element->id;
                }

                $record->markNewUntilDate = DateTimeHelper::toDateTime($markNewUntilDateParams);
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
                    $event->fields['markNewUntilDate'] = [
                        'name' => 'markNewUntilDate',
                        'type' => DateTime::getType(),
                        'resolve' => function($source, $arguments, $context, $resolveInfo) {
                            return $source->markNewUntilDate;
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
                if (GqlHelper::canQueryEntries()) {
                    $event->queries['entries']['args']['markedAsNew'] = [
                        'name' => 'markedAsNew',
                        'type' => Type::boolean(),
                        'description' => 'Narrows the query results to only entries that are marked as new.'
                    ];
                }

                if (Craft::$app->plugins->isPluginEnabled('commerce')) {
                    if (CommerceGqlHelper::canQueryProducts()) {
                        $event->queries['products']['args']['markedAsNew'] = [
                            'name' => 'markedAsNew',
                            'type' => Type::boolean(),
                            'description' => 'Narrows the query results to only products that are marked as new.'
                        ];
                    }
                }
            }
        );
    }

    private function registerTableAttributes()
    {
        Event::on(
            Entry::class,
            Element::EVENT_REGISTER_TABLE_ATTRIBUTES,
            [self::class, 'handleRegisterTableAttributes']
        );

        Event::on(
            Entry::class,
            Element::EVENT_SET_TABLE_ATTRIBUTE_HTML,
            [self::class, 'handleSetTableAttributeHtml']
        );

        Event::on(
            Product::class,
            Element::EVENT_REGISTER_TABLE_ATTRIBUTES,
            [self::class, 'handleRegisterTableAttributes']
        );

        Event::on(
            Product::class,
            Element::EVENT_SET_TABLE_ATTRIBUTE_HTML,
            [self::class, 'handleSetTableAttributeHtml']
        );
    }

    public static function handleElementQueryBeforePrepare(Event $event)
    {
        /** @var EntryQuery $query */
        $query = $event->sender;

        if (!$query->join) {
            $query->leftJoin(['markasnew_elements' => '{{%markasnew_elements}}'], "[[markasnew_elements.id]] = [[elements.id]]");
            if (count($query->select) > 1 || join('', $query->select) != 'COUNT(*)') {
                $query->addSelect(['markasnew_elements.markNewUntilDate']);
            }
        }

        if (isset($query->markedAsNew)) {
            if ($query->markedAsNew === true) {
                $query->subQuery->andWhere(['>=', 'markasnew_elements.markNewUntilDate', DateTimeHelper::currentUTCDateTime()->format('Y-m-d H:i:s')]);
            } else {
                $query->subQuery->andWhere([
                    'or',
                    ['markasnew_elements.markNewUntilDate' => null],
                    ['<', 'markasnew_elements.markNewUntilDate', DateTimeHelper::currentUTCDateTime()->format('Y-m-d H:i:s')]
                ]);
            }
        }
    }

    public static function handleElementQueryAfterPopulateElement(PopulateElementEvent $event)
    {
        $event->element->markNewUntilDate = DateTimeHelper::toDateTime($event->element->markNewUntilDate);
        $event->element->markedAsNew = $event->element->markNewUntilDate && $event->element->markNewUntilDate >= DateTimeHelper::currentUTCDateTime();
    }

    public static function handleRegisterTableAttributes(RegisterElementTableAttributesEvent $event)
    {
        $event->tableAttributes['markedAsNew'] = [
            'label' => 'Marked As New',
        ];
        $event->tableAttributes['markNewUntilDate'] = [
            'label' => 'Mark New Until',
        ];
    }

    public static function handleSetTableAttributeHtml(SetElementTableAttributeHtmlEvent $event)
    {
        /** @var Entry $entry */
        $entry = $event->sender;

        if ($event->attribute == 'markedAsNew') {
            $event->html = Html::tag('span', '', [
                'class' => array_filter([
                    'status',
                    $entry->markedAsNew ? 'active green' : null,
                ])
            ]);
        }

        if ($event->attribute == 'markNewUntilDate') {
            $event->html = $entry->markNewUntilDate ? Craft::$app->formatter->asDatetime($entry->markNewUntilDate, 'short') : null;
        }
    }
}
