# grid > source > defaultSearchCriteriaBindings

The `defaultSearchCriteriaBindings` is used to specify automatic search criteria filters against application state.

Often a grid is embedded in a page with additional data, e.g. on a customer detail page all their pending reviews could be shown, or on a product page I want to show all orders.

In a nutshell, there needs to be some default limitation of the displayed data, that is, I want to show the orders related to the current product. This is what `defaultSearchCriteriaBindings` is for.

If multiple default bindings are declared, they all have to match (that is, the filters are combined with `AND`). 
Combining them with `OR` so that one or more have to match, but not all, is possible by specifying the attribute `combineConditionsWith`.  
```
<defaultSearchCriteriaBindings combineConditionsWith="or">
```

The element can contain zero or more `<field>` nodes.
