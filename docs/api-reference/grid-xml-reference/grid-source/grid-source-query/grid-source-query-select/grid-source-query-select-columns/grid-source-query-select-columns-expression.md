# grid > source > query > select > columns > expression

The `<expression>` element is used to configure an SQL expression that will be included in the select result.

The content of the expression element is not validated. It will be wrapped in a `Zend_Db_Expr` instance and added to the select instance unmodified.

### Attributes:

It takes one optional Attribute:

* as

  The `as` attribute is used to specify an alias name for the expression column in the result.

### Example:

```html
<expression as="count">COUNT(*)</expression>
```

