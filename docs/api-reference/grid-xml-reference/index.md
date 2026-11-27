# Grid XML Reference

The nested documents describe the XML configuration for Hyva Admin grids.

When the configurations for a grid is loaded, it is merged from all modules, just like any other Magento 2 XML configuration file.

This allows customizing grids that are declared in other modules.

The API reference documents are sorted in alphabetical order.

If you want to read them all that’s fine, but if you only want to dip your toes into Hyva_Admin grids, I suggest you start with the [grid > source](source/index.md) configuration, followed by the [grid > columns > includes](columns/include/index.md) config.

Everything else can then be loaded into your brain when it is needed.

The grid XML schema may also be of interest - it can be found in the Hyvä_Admin module at `etc/hyva-grid.xsd`.

## Grid attributes

### acl

The `acl` attribute on the `grid` element declares the ACL resource an admin user needs to see the grid.

```xml
<grid xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:module:Hyva_Admin:etc/hyva-grid.xsd"
      acl="My_Module::my_grid">
    ...
</grid>
```

If the admin user isn't allowed to access the resource, the grid isn't rendered, and the ajax paging and export endpoints don't serve it.

The resource needs to be declared in an `acl.xml` file. Magento only allows undeclared resources for admin users with access to all resources.

The attribute only applies in the admin. Storefront grids are not protected by it. Because there is no admin user in CLI commands or cron jobs, grids with an `acl` attribute aren't rendered there.

The attribute is optional. Usually the admin page that renders a grid is protected by its controller's `ADMIN_RESOURCE`, which also limits who can use the grid's ajax paging and exports (see below). The `acl` attribute is checked whenever the grid is rendered and on every ajax paging and export request. It is recommended for grids with sensitive data, and for grids that are rendered on pages that are accessible to more admin users than the grid should be.

## Access to ajax paging and exports

Grids use the endpoints `hyva-admin/ajax/paging` and `hyva-admin/export/download` to load ajax pages and exports. These endpoints only serve grids that were rendered for the admin user during their current admin session.

This means the ajax paging and exports of a grid are only available to admin users who are allowed to open a page that renders the grid. Opening an export link in a new admin session, before the grid page was opened, is denied.

Only grids rendered with a Hyvä grid block (`Hyva\Admin\Block\Adminhtml\HyvaGrid` or any other block extending `Hyva\Admin\Block\BaseHyvaGrid`) are recorded. Grids rendered with a different block class can't use the ajax paging and export endpoints. If the grid block is the child of a block with a `cache_lifetime`, the grid isn't rendered on a cache hit, so it isn't recorded either.

Grids are not rendered through template directives like `{{block}}`, `{{layout}}` or `{{widget}}`, in any area. This includes email template previews, the CMS WYSIWYG editor and Page Builder in the admin, as well as CMS content and emails on the storefront. Otherwise, admin users with access to those features could record any grid, or publish it on the storefront.

A grid that is rendered by other means than an admin page, for example as part of a UI component, is available to the admin users who can access that, regardless of the page ACL. Declare an `acl` attribute for such grids.

Recorded grids stay available until the admin user logs out. If the ACL of an admin role changes, the endpoints keep serving grids that were already recorded, unless the grid declares an `acl` attribute, which is checked on every request.

Storefront grids are rendered without ajax paging and exports, because these endpoints only exist in the admin.
