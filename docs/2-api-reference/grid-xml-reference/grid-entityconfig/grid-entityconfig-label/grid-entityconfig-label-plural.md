# grid > entityConfig > label > plural

The entityConfig `plural` node configures the entity name to render when no records are found for a grid.

If no `plural` label is present, the value of the sibling element `singular` is used with a `s` suffix as the default.

If both the `plural` and the `singular` entity config label is absent, then the grid name is used as a fallback.

```html
<entityConfig>
    <label>
        <singular>Product</singular>
        <plural>Products</plural>
    </label>
</entityConfig>
```

