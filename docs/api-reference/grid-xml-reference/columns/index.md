# grid > columns

### Showing and hiding columns

If no columns are configured, the default is to show all available fields.

If the default isn’t sufficient, the columns can be configured using the `grid/columns/include` and `grid/columns/exclude` child nodes.

The behavior what is displayed is quite intuitive, but not super simple to explain in words:

No `include` and no `exclude` configuration:

→ display all columns

Only `include` and no `exclude` configuration:

→ display only the columns under `include`.

No `include` and only an `exclude` configuration:

→ display all columns except the excluded ones.

Both `include` and `exclude` configuration:

→ display all included columns except the ones that where excluded.

The include element can have an option attribute `keepAllSourceColumns`. If it is present and set to `true`, then configuring include columns will not automatically hide other available columns.

### Grid row actions

Grid columns can have a `rowAction` attribute, which refers to the ID value of an action configured in the grid/actions element.

```html
<columns rowAction="edit">
    ....
</columns>
<actions>
    <action id="edit" label="Edit" url="*/*/edit"/>
</actions>
```

The row action is triggered when a row is clicked.

## Examples:

Display all available columns (just as if no `<columns/>` node where present):

```html
<columns>
</columns>
```

Display only the `id` and `sku` columns:

```html
<columns>
    <include>
        <column name="id"/>
        <column name="sku"/>
    </include>
</columns>
```

Display all available source columns except the `category_gear` column:

```html
<columns>
    <exclude>
        <column name="category_gear"/>
    </exclude>
</columns>
```

Display the `id`, `sku` and `name` columns only:

```html
<columns>
    <include>
        <column name="id"/>
        <column name="sku"/>
        <column name="activity"/>
        <column name="name"/>
    </include>
    <exclude>
        <column name="activity"/>
    </exclude>
</columns>
```

Display all available columns except the `category_gear` column:

```html
<columns>
    <include keepAllSourceColumns="true">
        <column name="id"/>
        <column name="price" type="price"/>
    </include>
    <exclude>
        <column name="category_gear"/>
    </exclude>
</columns>
```

The `id` column will be displayed first, and the `price` column will be displayed second, and the value will be rendered as a price.