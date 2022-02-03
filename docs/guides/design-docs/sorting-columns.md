# Sorting Columns

As a default, all columns are sortable when possible.

If you don’t want a column to be sortable, add a `sortable="false"` attribute to the column configuration.

The question is, *when can a column **not** be sorted*?

It depends on the grid data provider. For array providers, all columns are sortable, because the sorting is applied to the grid data by the Hyva_Admin module itself.

For Repository grid data providers (and in future Collection and Query), the sorting is applied by the repository itself, when the search criteria is mapped to the underlying ORM collection.

For columns that are loaded loaded as part of the main query, that works very well.

However, if a column value is loaded in a separate query, the sorting can’t be applied.

For this reason, Hyva_Admin sets the sortable property on columns to false automatically in the following cases:

* For columns containing an extension attribute value
* For product category IDs
* For the product media gallery

More special cases might exist that I haven’t encountered yet. In such cases you will see an exception when trying to sort by a non-existent column, or maybe sorting will simply not do anything, depending on the source repository implementation.

If you want to enable sorting by such fields, you can override that default sortable value in the column XML definition by setting `sortable="true"`, and implement a plugin to apply the sorting after the data has been loaded by the repository.

