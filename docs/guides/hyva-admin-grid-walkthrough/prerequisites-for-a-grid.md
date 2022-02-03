# Prerequisites for a Grid

To create a grid, we need a backend page.

That means there needs to be

* A backend route configuration (`etc/adminhtml/routes.xml`).
* A backend action controller (`Controller/Adminhtml/Some/Page.php`).
* Some way to navigate to the page, so probably a `etc/adminhtml/menu.xml` entry.
* Maybe a `etc/acl.xml` entry for the controller and menu entry, too, even though Hyvä grids don’t use the ACL.
* A layout XML file for the page in `view/adminhtml/layout/my_module_some_page.xml`

All of these are standard Magento things and not specific to Hyva_Admin.
