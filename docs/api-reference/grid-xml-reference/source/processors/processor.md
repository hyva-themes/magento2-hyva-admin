# grid > source > processors > processor

The <processor> node defines the class name of HyvaGridSourceProcessorInterface implementations that should be applied to the current grid.

```html
<processors>
    <processor class="Hyva\AdminTest\HyvaGridProcessor\ProductGridQueryProcessor" enabled="false"/>
</processors>
```

## Attributes

### class (required)

The `class` attribute takes the fully qualified class name or the 

`Hyva\Admin\Api\HyvaGridSourceProcessorInterface` implementation. Often the classes don’t implement the interface directly, but instead extend from `Hyva\Admin\Model\GridSource\AbstractGridSourceProcessor`. More information on grid source processors can be found in the grid walkthrough documentation.

### enabled

The optional `enabled` attribute can be used to disable processors that have been declared in different modules. It’s value defaults to `true`.
