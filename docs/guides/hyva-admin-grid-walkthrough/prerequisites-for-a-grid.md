# Prerequisites for a Grid

To create a grid, we need a backend page.

That means there needs to be

* A backend route configuration (`etc/adminhtml/routes.xml`).
* A backend action controller (`Controller/Adminhtml/Some/Page.php`).
* Some way to navigate to the page, so probably a `etc/adminhtml/menu.xml` entry.
* An `etc/acl.xml` entry for the controller and menu entry. The ACL resource of the page also protects the ajax paging
  and exports of the grids rendered on it. Grids can also declare their own ACL resource with the
  [acl](../../api-reference/grid-xml-reference/index.md#acl) attribute.
* A layout XML file for the page in `view/adminhtml/layout/my_module_some_page.xml`

All of these are standard Magento things and not specific to Hyva_Admin.
