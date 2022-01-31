# grid > navigation > filters > filter > option

Filter `option` elements are for one specific use case:

when column values are static and don’t warrant the creation of a whole source model.

Also, options support grouping multiple values together.

```html
<filter column="color">
    <option label="reddish">
        <value>16</value>
        <value>17</value>
        <value>18</value>
    </option>
    <option label="blueish">
        <value>12</value>
    </option>
    <option label="rose">
        <value>100</value>
        <value>103</value>
    </option>
</filter>
```

When filter options are specified, the filter is always rendered as a select input type.

For groups of option values, any record with a matching value will be included in the grid data (internally all values of a selected group are added to the `SearchCriteria` using an `OR` condition).

The above example would render a filter with three options: `reddish`, `blueish` and `rose`.

When `reddish` is selected, any records with the color attribute matching `16`, `17` or `18` would be shown in the grid.

The option values don’t have to be integers, string values are valid, too.