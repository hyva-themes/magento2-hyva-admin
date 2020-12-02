# Hyvä Admin

This module aims to make creating grids and forms in the Magento 2 adminhtml area joyful and fast for developers.  
It does not use any UI components.


## next steps (a.k.a. todos)

* filtering (with filter option type instead of array)
* test bool filter

* changing rows-per-page should not reset filters
* add ui element to remove all filter values
* make array provider support filtering 
* make filter input type extendable
 * See \Hyva\Admin\ViewModel\HyvaGrid\GridFilter::guessInputType
 * See \Hyva\Admin\ViewModel\HyvaGrid\GridFilter::apply
* test boolean data type
* collection source type
* ajax paging
* caching the merge result in the grid config reader
* query source type
* inline grid editing
* form page support
