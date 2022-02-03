# Event Reference

Hyva_Admin dispatches some events to allow customization.

The events are:

* `'hyva_grid_source_prefetch_' . $this->getGridNameEventSuffix($gridName)`
* `'hyva_grid_source_prefetch'`

* `'hyva_grid_column_definition_build_after_' . $this->getGridNameEventSuffix($gridName)`

The event name suffix is based on the grid name.

Because the Magento `events.xml` schema only allows alphanumeric characters in event names, any non-alphanumeric characters are transformed into underscores, e.g `hyva_grid_source_prefetch_product-grid` (invalid) becomes `hyva_grid_source_prefetch_product_grid` (valid).

Please refer to the nested pages for detailed information.

