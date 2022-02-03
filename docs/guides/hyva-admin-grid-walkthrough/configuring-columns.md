# Configuring Columns

Hyva_Admin tries to extract the available columns from the grid source.

Without any configuration, all columns are shown in an order determined by the source type.

In some cases this already will be good enough, but often you will need to tweak which columns are displayed, the ordering of columns or how column data is rendered.

This is done in the `<columns>` grid XML section.

To specify which columns to display, use the `<columns><include/></columns>` branch.

For example, to display only `id`, `sku` and `name columns`, in that order, configure:

```html
<columns>
    <include>
        <column name="id"/>
        <column name="sku"/>
        <column name="name"/>
    </include>
</columns>
```

Once include columns are configured, all other available columns are no longer displayed.

To hide columns, use the `<columns><exclude/><columns>` branch.

Say you would like to display all columns except the `category_gear` column, this configuration would do the trick:

```html
<columns>
    <exclude>
        <column name="category_gear"/>
    </exclude>
</columns>
```

There are many attributes that can be specified on include columns.

They are (in alphabetical order):

* initiallyHidden
* label
* name
* renderAsUnsecureHtml
* rendererBlockName
* sortable
* sortOrder
* source
* template
* type

For more information on the attributes, I suggest you inspect the auto-completion offered by your editor, or have a look at the Grid XML API reference (or read the schema XSD file).

