# Mark As New Plugin for Craft CMS

Mark As New is a Craft CMS/Craft Commerce plugin for marking native elements as new. It provides a date/time field in the edit pages of entries and products that can be used to specify a date/time until when the entry or product is to be considered as new.

Currently, only these native elements are supported:

- Entries (Craft CMS)
- Products (Craft Commerce)

## Features

### New Date/Time Field Edit Page in Control Panel

A **"Mark New Until"** date/time field is added to the right sidebar of entry editing and product editing pages in the control panel. If set, it specifies that the element is considered as new until the selected date/time.

For entries, the field is displayed after the Expiry Date field. For products, the field is displayed only after the product is saved, and is displayed after the Date Updated meta field.

#### Exclude/Include Entry Types or Product types

By default, the field is added to all entry types and product types. If you wish to exclude certain types from displaying the field, add a `markasnew.php` file to the `config` folder, and specify the following:

```php
return [
    'excludeTypes' => [
        'entries.blog',
        'products.clothing',
    ],
];
```

The types are specified in the format of `<namespace>.<typeHandle>`. The namespaces `entries` and `products` indicate the type handle is an entry type handle and a product type handle respectively.

If you have a particularly long list of entry types and product types to exclude, you can instead use the `includeTypes` key:

```php
return [
    'includeTypes' => [
        'entries.services',
        'products.bikes',
    ],
];
```

The above will hide the field from **_all_** types, except those specified under `includeTypes`.

### Element Attributes

Entries and products get two new attributes that can be accessed to find out if they are marked as new or to retrieve the date when it is no longer marked as new.

| Attribute           | Data type  |
|---------------------|------------|
| `markedAsNew`       | `bool`     |
| `markNewUntilDate` | `DateTime` |

There are also two element index table attributes corresponding to these two element attributes that can be used to display their columns in the entry listing or product listing in the control panel.

### Element Query

For example, to query a list of entries in the `blog` section that are marked as new:

```twig
{% set entries = craft.entries.section('blog').markedAsNew(true).all() %}
```

And to query a list of products that are marked as new:

```twig
{% set products = craft.products.markedAsNew(true).all() %}
```

### GraphQL

For example, to query a list of entries that are marked as new:

```graphql
{
    entries(markedAsNew: true) {
        title
        markedAsNew
        markNewUntilDate
    }
}
```

And to query a list of products that are marked as new:

```graphql
{
    products(markedAsNew: true) {
        title
        markedAsNew
        markNewUntilDate
    }
}
```

---

Created by [ThePixelAge](https://www.thepixelage.com)
