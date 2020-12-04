# Hyvä Admin

This module aims to make creating grids and forms in the Magento 2 adminhtml area joyful and fast for developers.  
It does not use any UI components.


## next steps (a.k.a. todos)

* add event or some other hook to allow search criteria preprocessing
  See \Hyva\Admin\Model\GridSourceType\RepositoryGridSourceType::preprocessSearchCriteria
* test configured option value groups for select filters
* make array provider support filtering 
* add ui element to remove all filter values
* test boolean data type when rendering grid
* collection source type
* ajax paging
* caching the merge result in the grid config reader
* query source type
* inline grid editing
* form page support
