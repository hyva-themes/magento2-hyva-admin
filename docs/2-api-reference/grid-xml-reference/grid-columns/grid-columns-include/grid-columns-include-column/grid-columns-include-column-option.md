# grid > columns > include > column > option

For simple use cases it would be too much to create a PHP source model.

For example, when a list of websites for a shop is pretty static, it might be enough to just hardcode them in a grid configuration for a column.

```html
<column name="websites">
    <option value="5" label="Spain"/>
    <option value="2" label="Italy"/>
    <option value="30" label="Germany"/>
    <option value="31" label="Poland"/>
    <option value="5" label="Estonia"/>
</column>
```

Column values will automatically be mapped from the value to a matching label when options are present. Option values don’t have to be numbers, they can also be string values.

The option labels are always passed through the translation function `__()` before they are rendered.