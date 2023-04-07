# grid > columns > include > column

The include `column` element allows configuring if column should be displayed or not, and if it is displayed, how the column should be rendered.

The include `column` can have a number of attributes, almost all of which are optional:

* name (required)
* label
* type
* template
* renderAsUnsecureHtml
* rendererBlockName
* sortOrder
* sortable
* source
* initiallyHidden

In the following you can find a description of each column attribute.

### name

This is the only required attribute. It specifies the column key that is used to fetch the cell value from the grid source record.

```html
<column name="sku"/>
```

### label

The `label` is shown as the column title. Without a label attribute the title is determined from the column name.

```html
<column name="image" label="Main Image"/>
```

### type

The type influences how a column value is rendered in a grid cell. It sometimes can be determined automatically, but then again, sometimes Hyva_Admin will guess wrong, or maybe there is a more specific or custom value type you want to use.

The main purpose a column has a type is to turn the raw value from the record into a string or HTML that can be rendered in a grid cell.

```html
<column name="amount" type="price"/> 
```

For PHP values that can be rendered as strings natively usually no type attribute is required.

If the simply string cast is not sufficient, for example, a decimal value representing a currency amount, a simple existing type can be used, e.g. `type="price"` or `type="datetime"` so it looks nicer.

If a record contains PHP custom objects that can’t be cast to a string, then a custom type is required.

There are many existing types you can use as an example. The existing types can be found in the `Hyva_Admin` module directory `Hyva/Admin/Model/DataType`. Each type class has to implement the interface `Hyva\Admin\Api\DataType\ValueToStringConverterInterface` (or `Hyva\Admin\Api\DataTypeInterface` which inherits the former).

More information on the interfaces can be found in the Hyva_Admin PHP type reference.

### template

Sometimes column values require special treatment to be rendered, or maybe multiple column values need to be combined in one cell (e.g. an image URL as a `<img src` attribute and an image label as the `<img alt` attribute).

For such cases the template attribute is used configure the template to render each cell. The file is specified in the `Vendor_Module::file.phtml` syntax common in Magento 2.

```html
<column name="documents" template="My_Module::grid/cell/docs.phtml"/>
```

In the template, the cell instance that is currently being rendered is automatically injected as the variable `$cell`.

This type hint can be added to the `.phtml` file to allow autocompletion in IDEs:

```php
/** @var \Hyva\Admin\ViewModel\HyvaGridCellInterface $cell */
```

### renderAsUnsecureHtml

By default all cell content is escaped, so no malicious code can be injected.

If a cell content needs to be rendered as HTML (for example an image or a link), the attribute `renderAsUnsecureHtml="true"` has to be specified.

```html
<column name="image" type="magento_product_image" renderAsUnsecureHtml="true"/>
```

If `renderAsUnsecure` is set to `true`, the data type method `DataType\ValueToStringConverterInterface::toHtmlRecursive()`

is used to fetch a cells content for rendering.

If `renderAsUnsecure` is set to `false`, the data type method `DataType\ValueToStringConverterInterface::toString()`

is used to fetch a cells content for rendering.

### rendererBlockName

In some cases specifying a template to render a grid cell might not be sufficient. For example, if additional functionality through view models is required, those can not be made available to the template block rendering the cells.

To support advanced use cases like this, the layout XML name of a block that is declared somewhere else in the page layout can be set as the renderBlockName.

```html
<column name="activity" rendererBlockName="activity-renderer-block"/>
```

The renderer block will be used to render each cell in that column, roughly using code like this:

```php
$renderer = $layout->getBlock($rendererBlockName);
return $renderer->setData('cell', $this)->toHtml()
```

This PHP code snippet is only shown to further understanding of the internal workings - you don’t have to add this code - all you have to do is declare the renderer block in layout XML, and maybe specify the template it should use.

Please note that this block has to be declared in layout XML on every page the grid is used.

Please also note that adding a block renderer for a column automatically disables Ajax navigation, because the layout XML that defines the renderer block would not be loaded during the processing of the Ajax navigation request.

To access the cell data in the custom renderer, use `$this->getData('cell')`.

If the custom renderer uses a template, the cell can be accessed within the template using

`$block->getData('cell')`.

### sortOrder

Without any include columns configuration, columns are sorted however they are returned by the grid source type class.

If include columns are configured, the columns in the grid are rendered in the same order as the grids in the grid XML.

If include `keepAllSourceColumns="true"` is set, all specified include columns are rendered first, and then all remaining available columns are rendered after those.

This will take care of at least 80% of all grids.

However, maybe you want to change the sort order of a grid that is declared in a different module through XML merging.

To support this use case, a `sortOrder` attribute can be set on columns. Columns are sorted in ascending order, e.g.  `sortOrder="1"` before `sortOrder="2"`.

```html
<column name="id" sortOrder="20"/>
<column name="sku" sortOrder="10"/>
```

### sortable

By default columns are sortable by clicking on the column title, except if the column is based on extension attributes or other linked values that are loaded in a separate query, like the product `media_gallery` or `category_ids` attributes.

To disable sorting on a column, specify `sortable="false"` in the grid configuration.

```html
<column name="sku" sortable="false"/>
```

### source

For EAV attributes that use a `select` or `multiselect` frontend input, the attribute source model is determined automatically.

But if you need to, you can specify an attribute source model with the source column attribute.

```html
<column name="websites" source="Magento\Customer\Model\Customer\Attribute\Source\Website"/>
```

The class will then be used to map the column values to the display label.

The class has to implement the `Magento\Eav\Model\Entity\Attribute\Source\SourceInterface` interface.

Note that for simple cases attribute options can also be configured using option child elements. Please refer to the grid:columns:include:column:option documentation for more information.

### initiallyHidden

By default, all columns inside the `<include>` section will be displayed on the grid the first time it is loaded.

If you need to hide a column by initially, but at the same time allow a user to display it in the grid using the “Display” dropdown, you can set this attribute to `"true"`.

Please note this only affects the initial state of the grid. Once it has been loaded into the browser page, the column display state is stored in session storage.

```html
<column name="image" initiallyHidden="true"/>
```

