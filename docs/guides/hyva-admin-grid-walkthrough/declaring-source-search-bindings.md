# Declaring source search bindings

Sometimes we might want to show a data related to some other entity in a grid.

For example, all orders or a customer. This can be achieved with a `defaultSearchCriteriaBindings` declaration.

Take the the following example:

```html
<source>
    <repositoryListMethod>Magento\Sales\Api\OrderRepositoryInterface::getList</repositoryListMethod>
    <defaultSearchCriteriaBindings>
        <field name="customer_id" requestParam="id"/>
    </defaultSearchCriteriaBindings>
</source>
```

This example shows only orders in the grid, where the order’s `customer_id` attribute matches the value of the HTTP request parameter `id`.

It is also possible to specify bindings against methods. However, specifying a binding for a field to match a request parameter is the most common case.

If no default search criteria binding is needed, just omit the tag.

If there are multiple default search criteria bindings, they all have to match.  
By adding the attribute `<defaultSearchCriteriaBindings combineConditionsWith="or">`, the conditions can be applied as alternatives (that is, one or more have to match).

The ways the bindings are declared are best explained by examples:

The most simple example is:

```html
<field name="customer_id" method="Magento\Customer\Model\Session::getCustomerId"/>
```

The declaration will limit the grid data to records where the `customer_id` field matches the value of the expression

```php
$objectManager->get('Magento\Customer\Model\Session')->getCustomerId()
```

It is also possible to pass parameters to the method:

```html
<field name="entity_id" method="Magento\Framework\App\RequestInterface::getParam" param="id"/>
```

This example is the equivalent of using `requestParam`. It binds the `entity_id` field to the value of the expression

```php
$objectManager->get('Magento\Framework\App\RequestInterface')->getParam('id');
```

Finally it’s possible to go one level deeper and specify a binding against a property of the methods return value.

```html
<field name="store_id" method="Magento\Store\Model\StoreManagerInterface::getStore" property="id"/>
```

How the property is resolved depends on the returned value. If it is an array, the `property` will be used as an index of the array, e.g. `$value['id']`.

If the returned value is an object with a matching getter method, it will be called to produce the bound value.

```php
$objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId();
```

If that method doesn’t exist, the logic will also try to read the value in a number of other ways (from a public property, or using `getData('id')`, or using array access).

If more complex or chained calls are necessary, they have to be wrapped in a custom class that then can be used in the XML.
