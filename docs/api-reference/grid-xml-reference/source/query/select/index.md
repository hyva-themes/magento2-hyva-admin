# grid > source > query > select

The `<select>` element contains the configuration for the main grid query.

There is one required child element and three optional children:

* from (required)
* columns
* join
* groupBy

### Example:

```html
<select>
    <from table="sales_order"/>
</select>
```

