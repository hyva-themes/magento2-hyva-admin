# grid > source > processors

The `<processors>` node contains the list of `HyvaGridSourceProcessorInterface` class names that should be applied to the grid (in `<processor>` child nodes).

The `<processors>` element has no arguments.

```html
<source>
    <processors>
        <processor class="Hyva\AdminTest\HyvaGridProcessor\ProductGridQueryProcessor"/>
    </processors>
</source>
```

