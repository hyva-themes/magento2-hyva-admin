# grid > source > defaultSearchCriteriaBindings > field

Each `field` element is used to declare a default automatic binding on the `SearchCriteria` instance that is used when loading the grid source, against some value in the current Magento state.

This is mainly useful for showing grids that are embedded in pages of another entity type, for example all orders of a customer.

```html
<source>
  ...
  <defaultSearchCriteriaBindings>
    <field name="entity_id" requestParam="id">
    <field name="entity_id" method="Magento\Framework\App\RequestInterface::getParam" param="id"/>
    <field name="store_id" method="Magento\Store\Model\StoreManagerInterface::getStore" property="id"/>
    <field name="customer_ids" condition="finset" method="Magento\Customer\Model\Session::getCustomerId"/>
  </defaultSearchCriteriaBindings>
</source>
```

There are several possible attributes:

### name (required)

The `name` attribute specifies the field name for the filter.

### requestParam

This attribute is used to bind the filter to a request value. This is the most common binding type.

It is a shorthand for specifying `class="Magento\Framework\App\RequestInterface"` and `method="getParam"` together with a `param` attribute.

### method

The `method` attribute is used to specify the class and method to call to product the filter value.

```html
<field name="customer_id" method="Magento\Customer\Model\Session::getCustomerId"/>
```

### param

If the `method` requires a string parameter to produce the desired value, it can be specified with the `param` attribute. This is sometimes handy for use with the generic `getData($key)` method.

```html
<field name="entity_id" method="Magento\Framework\App\RequestInterface::getParam" param="id"/>
```

### property

Should the method return an array or an object, the property attribute can be used to retrieve a singe value.

```html
<field name="store_id" method="Magento\Store\Model\StoreManagerInterface::getStore" property="id"/>
```

The way the `property` is retrieved depends on the type of value the `method` returns.

For objects the code will try to call a matching getter method, will try `getData`, a public property or `ArrayAccess`.

For arrays it will simply try to use the `property` as an array index.

### condition

By default search criteria bindings are applied using an equality condition (`eq`). Using the condition attribute it is possible to use a different condition. Any of the Magento SearchCriteria condition values is allowed:

* eq
* is
* neq
* lteq
* from
* to
* gteq
* moreeq
* gt
* lt
* like
* nlike
* in
* nin
* notnull
* null
* finset

