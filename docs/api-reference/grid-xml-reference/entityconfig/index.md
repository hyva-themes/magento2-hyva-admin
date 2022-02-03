# grid > entityConfig

The grid entity configuration is currently only used to specify the labels to display when a grid is empty.

```html
<entityConfig>
    <label>
        <singular>Product</singular>
        <plural>Products</plural>
    </label>
</entityConfig>
```

The above example would render

```
No Products found.
```

If no entityConfig is present, the grid name is used instead. On a grid with the name product-grid, the message would then look like this:

```
No product-grid records found.
```

In future more children elements might be added to the `entityConfig` element.