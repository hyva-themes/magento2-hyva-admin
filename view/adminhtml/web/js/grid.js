/**
 * Alpine.js component for Hyvä Admin grids.
 *
 * Compatible with the Alpine.js CSP build: templates only reference methods and properties,
 * and all grid specific configuration is read from data attributes on the grid root element.
 */
(function () {
    'use strict';

    const closest = function (selector, element, top) {
        if (!element || element === (top || document)) {
            return null;
        }

        return element.matches(selector)
            ? element
            : closest(selector, element.parentElement, top);
    };

    const parseJson = function (json, fallback) {
        try {
            return json ? JSON.parse(json) : fallback;
        } catch (e) {
            return fallback;
        }
    };

    function initDataTable() {
        const dataset = this.$root.dataset;
        const gridName = dataset.gridName;

        return {
            gridName: gridName,
            ajaxNavigationEnabled: dataset.ajaxNavigationEnabled === '1',
            hasFilters: dataset.hasFilters === 'true',
            filterFormId: dataset.filterFormId,
            massActionStorageName: gridName + '-selectedRows',
            hiddenColsStorageName: gridName + '-hiddenCols',
            columns: parseJson(dataset.cols, []),
            hiddenCols: parseJson(dataset.hiddenCols, []),
            refs: false,
            loading: false,
            selectedRows: [],
            open: false,
            openExport: false,
            filterUri: window.location.href,

            init() {
                this.refs = this.$refs;
                this.initState();
            },

            initState() {
                this.$watch('selectedRows', this.storeSelectedRows.bind(this));
                this.selectedRows = this.getSelectedRows();
                this.hiddenCols = this.getHiddenCols();

                const filterForm = this.hasFilters && document.getElementById(this.filterFormId);
                if (filterForm) {
                    this.filterUri = this.getFormUriEncoded(filterForm);
                }
            },

            getSelectedRows() {
                return parseJson(window.sessionStorage.getItem(this.massActionStorageName), []);
            },

            getHiddenCols() {
                return parseJson(window.sessionStorage.getItem(this.hiddenColsStorageName), this.hiddenCols);
            },

            // Used by the column cells, which have the column key in a data attribute.
            // The optional key argument keeps the 1.x signature working.
            isColumnVisible(key) {
                return !this.hiddenCols.includes(typeof key === 'string' ? key : this.$el.dataset.colKey);
            },

            // Used by the column toggle checkboxes, which are rendered in an x-for loop over the columns
            isColumnToggleChecked() {
                return !this.hiddenCols.includes(this.column.key);
            },

            toggleColumn(key) {
                key = typeof key === 'string' ? key : this.column.key;
                const index = this.hiddenCols.indexOf(key);
                if (index !== -1) {
                    this.hiddenCols.splice(index, 1);
                } else {
                    this.hiddenCols.push(key);
                }
                this.storeHiddenColumns();
            },

            storeHiddenColumns() {
                this.storeValues(this.hiddenColsStorageName, this.hiddenCols);
            },

            storeSelectedRows() {
                this.storeValues(this.massActionStorageName, this.selectedRows);
            },

            storeValues(key, values) {
                window.sessionStorage.setItem(key, JSON.stringify(values.filter(value => null !== value)));
            },

            isRowSelected() {
                return this.selectedRows.includes(this.$el.value);
            },

            toggleRow() {
                const index = this.selectedRows.indexOf(this.$el.value);

                if (index === -1) {
                    this.selectedRows.push(this.$el.value);
                } else {
                    this.selectedRows.splice(index, 1);
                }
            },

            selectAllCheckbox() {
                const checked = !!this.$event.target.checked;
                this.$root.querySelectorAll('.rowCheckbox').forEach(checkbox => {
                    const index = this.selectedRows.indexOf(checkbox.value);
                    if (checked && index === -1) {
                        this.selectedRows.push(checkbox.value);
                    } else if (!checked && index > -1) {
                        this.selectedRows.splice(index, 1);
                    }
                });
            },

            doMassAction(option) {
                option = option instanceof HTMLOptionElement ? option : this.$event.target.selectedOptions[0];
                const url = option.value;
                const form = this.refs['massaction-form'];
                const checked = this.getSelectedRows();
                const select = this.refs['massaction-select'];

                if (!url || !form || !select) {
                    return;
                }

                if (checked.length === 0) {
                    alert(this.$root.dataset.msgNoSelection);
                    select.selectedIndex = 0;
                    return;
                }
                if (option.dataset.requireConfirm === 'true' && !confirm(this.$root.dataset.msgConfirm)) {
                    select.selectedIndex = 0;
                    return;
                }
                const rowCheckboxName = select.dataset.massActionsId + '[]';

                const alreadyPresent = Array.from(form.elements)
                    .filter(element => element.name === rowCheckboxName)
                    .filter(element => element.checked)
                    .map(element => element.value);

                const missing = checked.filter(value => !alreadyPresent.includes(value));

                missing.forEach(value => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = rowCheckboxName;
                    input.value = value;
                    form.append(input);
                });

                form.action = url;

                this.resetFormState();

                this.loading = true;
                form.submit();
            },

            resetFormState() {
                this.selectedRows = [];

                if (this.refs && this.refs['select-all']) {
                    this.refs['select-all'].checked = false;
                }

                if (this.refs && this.refs['massaction-select']) {
                    this.refs['massaction-select'].value = 0;
                }
            },

            formGridUpdate(form) {
                form = form instanceof HTMLFormElement ? form : (this.$event.target.form || this.$event.target);

                this.filterUri = this.getFormUriEncoded(form);
                this.updateGrid(this.filterUri);
            },

            getFormUriEncoded(form) {
                const url = new URL(form.action);
                Array.from(form.elements).forEach(
                    input => input.name && url.searchParams.set(input.name, input.value)
                );
                return url.toString();
            },

            updatePageSize() {
                this.updateGrid(this.$el.value);
            },

            updateGrid(url) {
                if (typeof url !== 'string') {
                    url = this.$el.dataset.gridUpdateUrl;
                }

                if (this.ajaxNavigationEnabled) {
                    this.ajaxGridUpdate(url);
                } else {
                    window.location.href = url;
                }
            },

            ajaxGridUpdate(url) {
                this.loading = true;
                fetch(url, {
                    method: 'get',
                    headers: {'Content-Type': 'application/json'}
                })
                    .then(response => response.json())
                    .then(responseBody => {
                        this.loading = false;
                        if (responseBody.message) alert(responseBody.message);
                        if (responseBody.grid_html) {
                            const tmpContainer = document.createElement('div');
                            tmpContainer.innerHTML = responseBody.grid_html;
                            this.$root.replaceWith(tmpContainer.querySelector('.hyva-admin-grid'));
                        }
                    })
                    .catch(error => {
                        this.loading = false;
                        console.error(error);
                    });
            },

            openRowAction() {
                window.location.href = this.$el.dataset.rowActionUrl;
            },

            openButtonUrl() {
                window.location.href = this.$el.dataset.url;
            },

            // The data-row-events attribute maps each event type to the event name and the event detail
            dispatchRowEvent() {
                const event = this.$event;
                const rowEvent = parseJson(this.$el.dataset.rowEvents, {})[event.type];
                if (!rowEvent) {
                    return;
                }
                const eventData = {origEvent: event, row: closest('TR', event.target, this.$root), viewModel: this};
                const detail = Object.assign({}, rowEvent.detail, eventData);

                window.dispatchEvent(new CustomEvent(rowEvent.name, {detail: detail}));
            },

            getRowCount() {
                return this.selectedRows.length;
            },

            hasRows() {
                return this.getRowCount() > 0;
            },

            toggleOpen() {
                this.open = !this.open;
            },

            toggleOpenExport() {
                this.openExport = !this.openExport;
            },

            close() {
                this.open = false;
            },

            closeExport() {
                this.openExport = false;
            },
        };
    }

    window.hyvaAdminGrid = {initDataTable: initDataTable};

    if (window.Alpine) {
        window.Alpine.data('initDataTable', initDataTable);
    } else {
        document.addEventListener('alpine:init', () => Alpine.data('initDataTable', initDataTable));
    }
})();
