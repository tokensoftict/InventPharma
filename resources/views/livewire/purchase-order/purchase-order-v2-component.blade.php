<div>
<div
    wire:ignore
    x-data="purchaseOrderV2()"
    x-init="init()"
    x-on:keydown.window="handleGlobalKeydown($event)"
>
    {{-- Unsaved Local Backup Banner (new PO only) --}}
    <div x-show="hasSavedBackup" x-cloak class="alert alert-info d-flex align-items-center justify-content-between py-2 px-3 mb-3">
        <div>
            <i class="fa fa-history me-1"></i>
            <strong>Unsaved session found:</strong>
            <span x-text="backupSummary"></span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-primary" x-on:click="restoreBackup()">Restore</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" x-on:click="discardBackup()">Dismiss</button>
        </div>
    </div>

    {{-- Top Bar: Beta badge, Shortcuts Legend, Classic Switcher --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                <i class="fa fa-bolt me-1"></i> Fast Entry (Beta)
            </span>
            <span class="text-muted small d-none d-md-inline">
                <span class="po-v2-kbd">F2</span> Search Product
                &nbsp;&middot;&nbsp;
                <span class="po-v2-kbd">Enter</span> Next field / Add row
                &nbsp;&middot;&nbsp;
                <span class="po-v2-kbd">Ctrl+S</span> Save Draft
                &nbsp;&middot;&nbsp;
                Expiry accepts <span class="po-v2-kbd">MM/YY</span> (e.g. <code>08/28</code>)
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($purchase->exists)
                <a href="{{ route('purchase.edit', $purchase->id) }}" class="btn btn-sm btn-outline-secondary" title="Open original edit purchase order form">
                    <i class="fa fa-external-link-alt me-1"></i> Classic Edit
                </a>
            @else
                <a href="{{ route('purchase.create') }}" class="btn btn-sm btn-outline-secondary" title="Open original purchase order form">
                    <i class="fa fa-external-link-alt me-1"></i> Classic Version
                </a>
            @endif
        </div>
    </div>

    {{-- HEADER BAR: Supplier + Department + Date + Quick Loaders (NO "Go" reload!) --}}
    <div class="po-v2-header mb-3">
        <div class="row g-2 align-items-end">
            {{-- Instant Searchable Supplier Combobox --}}
            <div class="col-lg-4 col-md-6" x-on:click.outside="supplierOpen = false">
                <label class="form-label mb-1 fw-semibold small">
                    Supplier <span class="text-danger">*</span>
                </label>
                <div class="position-relative">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa fa-truck text-muted"></i></span>
                        <input
                            type="text"
                            x-ref="supplierInput"
                            x-model="supplierQuery"
                            x-on:focus="openSupplierList()"
                            x-on:input="supplierOpen = true; supplierIndex = 0; if (!supplierQuery) clearSupplier(false);"
                            x-on:keydown.arrow-down.prevent="moveSupplier(1)"
                            x-on:keydown.arrow-up.prevent="moveSupplier(-1)"
                            x-on:keydown.enter.prevent="pickActiveSupplier()"
                            x-on:keydown.escape="supplierOpen = false"
                            placeholder="Type to search supplier..."
                            autocomplete="off"
                            class="form-control"
                            :class="{'po-v2-cell-invalid': headerErrors.supplier_id}"
                        />
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            x-show="supplier_id !== ''"
                            x-on:click="clearSupplier(true)"
                            title="Clear supplier"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>

                    <div class="po-v2-dropdown" x-show="supplierOpen" x-cloak>
                        <template x-if="filteredSuppliers.length === 0">
                            <div class="p-3 text-muted small text-center">No matching supplier</div>
                        </template>
                        <template x-for="(sup, sIdx) in filteredSuppliers" :key="sup.id">
                            <div
                                class="po-v2-dropdown-item d-flex justify-content-between align-items-center"
                                :class="{'is-active': sIdx === supplierIndex}"
                                x-on:mouseenter="supplierIndex = sIdx"
                                x-on:mousedown.prevent="selectSupplier(sup)"
                            >
                                <span class="fw-medium" x-text="sup.name"></span>
                                <span x-show="supplier_id == sup.id" class="badge bg-primary">Selected</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Department --}}
            <div class="col-lg-3 col-md-4">
                <label class="form-label mb-1 fw-semibold small">
                    Department <span class="text-danger">*</span>
                </label>
                <select
                    class="form-select form-select-sm"
                    x-ref="departmentSelect"
                    x-model="department"
                    x-on:change="onDepartmentChange()"
                    :class="{'po-v2-cell-invalid': headerErrors.department}"
                >
                    <option value="">Select Department</option>
                    @foreach($departmentsList as $dept)
                        <option
                            value="{{ $dept['quantity_column'] }}"
                            {{ ($initialData['department'] ?? '') === $dept['quantity_column'] ? 'selected' : '' }}
                        >{{ $dept['label'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Purchase Date --}}
            <div class="col-lg-2 col-md-2">
                <label class="form-label mb-1 fw-semibold small">Purchase Date</label>
                <input
                    type="date"
                    class="form-control form-control-sm"
                    x-model="purchase_date"
                    x-on:change="persistBackup()"
                />
            </div>

            {{-- 1-Click Quick-Fill Tools (Load Low Stock / Copy Previous PO) --}}
            <div class="col-lg-3 col-md-12">
                <div class="d-flex gap-2 justify-content-lg-end flex-wrap">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary"
                        :disabled="!supplier_id || !department || loadingLowStock"
                        x-on:click="loadSupplierLowStock()"
                        title="Auto-load near-out-of-stock products for this supplier"
                    >
                        <span x-show="loadingLowStock" class="spinner-border spinner-border-sm me-1"></span>
                        <i x-show="!loadingLowStock" class="fa fa-layer-group me-1"></i>
                        Load Low Stock
                    </button>

                    <div class="dropdown" x-on:click.outside="recentPoOpen = false">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary dropdown-toggle"
                            :disabled="!supplier_id || loadingRecentPos"
                            x-on:click="toggleRecentPos()"
                            title="Clone items from a previous PO for this supplier"
                        >
                            <span x-show="loadingRecentPos" class="spinner-border spinner-border-sm me-1"></span>
                            <i x-show="!loadingRecentPos" class="fa fa-copy me-1"></i>
                            Copy Last PO
                        </button>
                        <div
                            class="po-v2-dropdown"
                            style="min-width: 280px; right: 0; left: auto;"
                            x-show="recentPoOpen"
                            x-cloak
                        >
                            <div class="px-3 py-2 border-bottom bg-light small fw-semibold text-muted">
                                Recent POs for this Supplier
                            </div>
                            <template x-if="recentPos.length === 0">
                                <div class="p-3 text-muted small text-center">No previous POs found</div>
                            </template>
                            <template x-for="po in recentPos" :key="po.id">
                                <div
                                    class="po-v2-dropdown-item"
                                    x-on:mousedown.prevent="cloneFromPo(po)"
                                >
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong x-text="'PO #' + po.id"></strong>
                                        <span class="badge bg-light text-dark border" x-text="po.items_count + ' items'"></span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted mt-1">
                                        <span x-text="po.date_created"></span>
                                        <span class="fw-semibold text-dark" x-text="money(po.total)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- QUICK-ADD ENTRY BAR (Single keyboard-driven pipeline) --}}
    <div class="po-v2-entry mb-3">
        <div class="row g-2 align-items-end">
            {{-- Product Search / Barcode Scanner --}}
            <div class="col-xl-4 col-lg-4 col-md-12" x-on:click.outside="searchOpen = false">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0 fw-semibold small">
                        Product Search / Barcode <span class="po-v2-kbd ms-1">F2</span>
                    </label>
                    <span x-show="draft.stock_id" x-cloak class="small text-muted">
                        Av: <strong class="text-dark" x-text="draft.allqty"></strong>
                        &nbsp;|&nbsp;
                        1M: <strong class="text-dark" x-text="draft.qty_to_buy_1m"></strong>
                    </span>
                </div>
                <div class="position-relative">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i x-show="!searching" class="fa fa-barcode text-primary"></i>
                            <span x-show="searching" x-cloak class="spinner-border spinner-border-sm text-primary"></span>
                        </span>
                        <input
                            type="text"
                            x-ref="productSearch"
                            x-model="searchQuery"
                            x-on:input="onSearchInput()"
                            x-on:focus="if (searchResults.length > 0) searchOpen = true"
                            x-on:keydown.arrow-down.prevent="moveSearch(1)"
                            x-on:keydown.arrow-up.prevent="moveSearch(-1)"
                            x-on:keydown.enter.prevent="onSearchEnter()"
                            x-on:keydown.escape="searchOpen = false"
                            placeholder="Scan barcode or type product name..."
                            autocomplete="off"
                            class="form-control"
                        />
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            x-show="searchQuery !== '' || draft.stock_id !== ''"
                            x-on:click="resetDraft(true)"
                            title="Clear selection"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>

                    {{-- Instant Rich Search Results --}}
                    <div class="po-v2-dropdown" x-show="searchOpen && (searchResults.length > 0 || searchEmpty)" x-cloak>
                        <template x-if="searchEmpty && searchResults.length === 0">
                            <div class="p-3 text-muted small text-center">No active products found</div>
                        </template>
                        <template x-for="(prod, pIdx) in searchResults" :key="prod.id">
                            <div
                                class="po-v2-dropdown-item"
                                :class="{'is-active': pIdx === searchIndex}"
                                x-on:mouseenter="searchIndex = pIdx"
                                x-on:mousedown.prevent="selectProduct(prod)"
                            >
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="fw-semibold text-dark" style="font-size: 13px;" x-text="prod.name"></div>
                                    <span
                                        x-show="isInList(prod.id)"
                                        class="badge bg-success-subtle text-success border border-success-subtle flex-shrink-0"
                                    >In PO</span>
                                </div>
                                <div class="d-flex flex-wrap gap-3 mt-1 small text-muted">
                                    <span>Cost: <strong class="text-dark" x-text="money(prod.cost_price)"></strong></span>
                                    <span>Av. Qty: <strong :class="prod.allqty <= 0 ? 'text-danger' : 'text-dark'" x-text="prod.allqty"></strong></span>
                                    <span>1M Target: <strong class="text-dark" x-text="prod.qty_to_buy_1m"></strong></span>
                                    <span x-show="prod.suggested_qty > 1" class="text-primary">
                                        Suggest: <strong x-text="prod.suggested_qty"></strong>
                                    </span>
                                    <span x-show="prod.location" x-text="'Loc: ' + prod.location"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Quantity --}}
            <div class="col-xl-1 col-lg-2 col-md-3 col-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0 fw-semibold small">Qty</label>
                    <button
                        type="button"
                        x-show="draft.stock_id && draft.suggested_qty > 0"
                        x-cloak
                        x-on:click="draft.qty = draft.suggested_qty; $refs.entryCost.focus(); $refs.entryCost.select();"
                        class="btn btn-link p-0 text-decoration-none"
                        style="font-size: 10.5px;"
                        title="Click to use 1M suggested quantity"
                        x-text="'Use ' + draft.suggested_qty"
                    ></button>
                </div>
                <input
                    type="number"
                    min="1"
                    x-ref="entryQty"
                    x-model="draft.qty"
                    x-on:keydown.enter.prevent="onEntryEnter($event, 'qty')"
                    placeholder="Qty"
                    class="form-control text-end"
                />
            </div>

            {{-- Cost Price --}}
            <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                <label class="form-label mb-1 fw-semibold small">Cost Price</label>
                <input
                    type="number"
                    step="any"
                    min="0"
                    x-ref="entryCost"
                    x-model="draft.cost_price"
                    x-on:keydown.enter.prevent="onEntryEnter($event, 'cost')"
                    placeholder="0.00"
                    class="form-control text-end"
                />
            </div>

            {{-- Smart Expiry Date (MM/YY or YYYY-MM-DD + native picker) --}}
            <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                <label class="form-label mb-1 fw-semibold small">
                    Expiry <span class="text-muted fw-normal">(MM/YY)</span>
                </label>
                <div class="input-group">
                    <input
                        type="text"
                        x-ref="entryExpiry"
                        x-model="draft.expiry_date"
                        x-on:blur="normalizeDraftExpiry()"
                        x-on:keydown.enter.prevent="onEntryEnter($event, 'expiry')"
                        placeholder="08/28"
                        class="form-control"
                    />
                    <button
                        type="button"
                        class="btn btn-outline-secondary px-2"
                        tabindex="-1"
                        x-on:click="openNativeDatePicker($refs.entryExpiryNative)"
                        title="Pick from calendar"
                    >
                        <i class="fa fa-calendar-alt"></i>
                    </button>
                    <input
                        type="date"
                        x-ref="entryExpiryNative"
                        :value="draft.expiry_date"
                        x-on:change="draft.expiry_date = $event.target.value; $refs.entryBatch.focus();"
                        style="position: absolute; right: 0; top: 0; opacity: 0; width: 36px; height: 100%; pointer-events: none;"
                        tabindex="-1"
                    />
                </div>
            </div>

            {{-- Batch Number --}}
            <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                <label class="form-label mb-1 fw-semibold small">Batch No</label>
                <input
                    type="text"
                    x-ref="entryBatch"
                    x-model="draft.batch_no"
                    x-on:keydown.enter.prevent="addCurrentItem()"
                    placeholder="Batch #"
                    class="form-control"
                />
            </div>

            {{-- Add Button --}}
            <div class="col-xl-1 col-lg-12 col-md-12 d-grid">
                <button
                    type="button"
                    class="btn btn-primary"
                    x-on:click="addCurrentItem()"
                    title="Add item to Purchase Order (Enter)"
                >
                    <i class="fa fa-plus me-1"></i> Add
                </button>
            </div>
        </div>

        {{-- Compact Entry Options Row --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 pt-2 border-top">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="poV2KeepBatch" x-model="keepLastBatchExpiry">
                    <label class="form-check-label small text-muted" for="poV2KeepBatch">
                        Keep last Expiry &amp; Batch for next item
                    </label>
                </div>

                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="poV2AutoSuggestQty" x-model="autoUseSuggestedQty">
                    <label class="form-check-label small text-muted" for="poV2AutoSuggestQty">
                        Auto-fill 1M Suggested Qty
                    </label>
                </div>

                @if($canChangeSellingPrice)
                    <button
                        type="button"
                        class="btn btn-sm btn-link p-0 text-decoration-none small"
                        x-on:click="showEntryPrices = !showEntryPrices"
                    >
                        <i class="fa" :class="showEntryPrices ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        <span x-text="showEntryPrices ? 'Hide Selling Prices' : 'Edit Selling Prices'"></span>
                    </button>
                @endif
            </div>

            <div class="small text-muted" x-show="draft.stock_id" x-cloak>
                Line preview: <strong class="text-dark" x-text="money((parseFloat(draft.qty) || 0) * (parseFloat(draft.cost_price) || 0))"></strong>
                &nbsp;&middot;&nbsp;
                <span class="po-v2-kbd">Shift+Enter</span> Quick-add
            </div>
        </div>

        {{-- Optional Selling Prices Drawer (out of normal Tab flow unless expanded) --}}
        @if($canChangeSellingPrice)
            <div class="row g-2 mt-2 pt-2 border-top bg-light rounded p-2" x-show="showEntryPrices" x-cloak>
                <div class="col-md-4">
                    <label class="form-label mb-1 small">Bulk Price</label>
                    <input type="number" step="any" class="form-control form-control-sm text-end" x-model="draft.bulk_price">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1 small">Wholesale Price</label>
                    <input type="number" step="any" class="form-control form-control-sm text-end" x-model="draft.whole_price">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1 small">Retail Price</label>
                    <input type="number" step="any" class="form-control form-control-sm text-end" x-model="draft.retail_price">
                </div>
            </div>
        @endif
    </div>

    {{-- ITEMS TABLE TOOLBAR --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h5 class="mb-0">
                Purchase Items
                <span class="badge bg-secondary ms-1" x-text="items.length"></span>
            </h5>

            <div class="form-check form-switch mb-0 ms-2">
                <input class="form-check-input" type="checkbox" id="poV2ShowPriceCols" x-model="showPriceColumns">
                <label class="form-check-label small text-muted" for="poV2ShowPriceCols">Show Selling Prices</label>
            </div>
        </div>

        {{-- Bulk Apply Expiry & Batch to Empty Rows (1 click after Load Low Stock / Copy PO!) --}}
        <div class="d-flex flex-wrap align-items-center gap-2" x-show="items.length > 0" x-cloak>
            <div class="input-group input-group-sm" style="width: auto;" x-show="emptyMetaCount > 0">
                <span class="input-group-text bg-light small">Fill empty:</span>
                <input
                    type="date"
                    class="form-control"
                    style="width: 140px;"
                    x-model="bulkExpiry"
                    title="Bulk expiry date for empty rows"
                />
                <input
                    type="text"
                    class="form-control"
                    style="width: 110px;"
                    x-model="bulkBatch"
                    placeholder="Batch #"
                />
                <button
                    type="button"
                    class="btn btn-outline-primary"
                    x-on:click="applyBulkMeta()"
                    title="Fill all empty Expiry/Batch cells in one click"
                >
                    Apply (<span x-text="emptyMetaCount"></span>)
                </button>
            </div>

            <input
                type="text"
                x-show="items.length > 6"
                x-model="tableFilter"
                placeholder="Filter table..."
                class="form-control form-control-sm"
                style="width: 170px;"
            />

            <button
                type="button"
                class="btn btn-sm btn-outline-danger"
                x-on:click="clearAllItems()"
                title="Remove all items"
            >
                <i class="fa fa-trash-alt"></i>
            </button>
        </div>
    </div>

    {{-- SPREADSHEET-STYLE INLINE EDITABLE TABLE --}}
    <div class="table-responsive border rounded">
        <table class="table table-hover table-bordered mb-0 po-v2-table">
            <thead>
            <tr>
                <th style="width: 34px;" class="text-center">#</th>
                <th style="min-width: 210px;">Product</th>
                <th class="text-center" style="width: 90px;">Qty</th>
                <th class="text-center" style="width: 75px;" title="1-Month Target / Need (1M - Av.Qty)">1M / Need</th>
                <th class="text-end" style="width: 115px;">Cost Price</th>
                <th class="text-end" style="width: 100px;" x-show="showPriceColumns" x-cloak>Bulk</th>
                <th class="text-end" style="width: 100px;" x-show="showPriceColumns" x-cloak>Wholesale</th>
                <th class="text-end" style="width: 100px;" x-show="showPriceColumns" x-cloak>Retail</th>
                <th class="text-center" style="width: 150px;">Expiry Date</th>
                <th class="text-center" style="width: 125px;">Batch No</th>
                <th class="text-end" style="width: 115px;">Total Cost</th>
                <th class="text-center" style="width: 50px;"></th>
            </tr>
            </thead>
            <tbody>
            <template x-if="items.length === 0">
                <tr>
                    <td :colspan="showPriceColumns ? 12 : 9" class="text-center py-5 text-muted">
                        <i class="fa fa-shopping-basket fa-2x mb-2 d-block opacity-50"></i>
                        <div class="fw-semibold">No items added yet</div>
                        <div class="small mt-1">
                            Select a Supplier above, then scan a barcode or press <span class="po-v2-kbd">F2</span> to search products — or click <strong>Load Low Stock</strong> / <strong>Copy Last PO</strong>.
                        </div>
                    </td>
                </tr>
            </template>

            <template x-for="(item, index) in filteredItems" :key="item.stock_id">
                <tr
                    :id="'po-v2-row-' + item.stock_id"
                    :class="{
                        'table-danger': item.qty_to_buy_1m > 0 && parseFloat(item.qty) > parseFloat(item.qty_1m_result),
                        'po-v2-row-flash': item.stock_id === highlightedStockId
                    }"
                >
                    <td class="text-center text-muted" x-text="index + 1"></td>

                    <td>
                        <div class="fw-medium text-dark" x-text="item.name"></div>
                        <div x-show="item.qty_to_buy_1m > 0 && parseFloat(item.qty) > parseFloat(item.qty_1m_result)" class="text-danger" style="font-size: 10.5px;">
                            <i class="fa fa-exclamation-triangle"></i> Exceeds 1M need (<span x-text="item.qty_1m_result"></span>)
                        </div>
                    </td>

                    <td>
                        <input
                            type="number"
                            min="1"
                            :id="'po-v2-qty-' + item.stock_id"
                            x-model="item.qty"
                            x-on:input="recalcItem(item)"
                            class="form-control po-v2-cell-input text-end"
                            :class="{'po-v2-cell-invalid': showValidation && (!(parseFloat(item.qty) > 0))}"
                        />
                    </td>

                    <td class="text-center small">
                        <span x-text="item.qty_to_buy_1m ?? 0"></span>
                        <span class="text-muted">/</span>
                        <span :class="(item.qty_1m_result ?? 0) > 0 ? 'text-primary fw-semibold' : 'text-muted'" x-text="item.qty_1m_result ?? 0"></span>
                    </td>

                    <td>
                        <input
                            type="number"
                            step="any"
                            min="0"
                            x-model="item.cost_price"
                            x-on:input="recalcItem(item)"
                            class="form-control po-v2-cell-input text-end"
                        />
                    </td>

                    <td class="text-end" x-show="showPriceColumns" x-cloak>
                        @if($canChangeSellingPrice)
                            <input type="number" step="any" x-model="item.bulk_price" x-on:input="persistBackup()" class="form-control po-v2-cell-input text-end" />
                        @else
                            <span x-text="money(item.bulk_price)"></span>
                        @endif
                    </td>

                    <td class="text-end" x-show="showPriceColumns" x-cloak>
                        @if($canChangeSellingPrice)
                            <input type="number" step="any" x-model="item.whole_price" x-on:input="persistBackup()" class="form-control po-v2-cell-input text-end" />
                        @else
                            <span x-text="money(item.whole_price)"></span>
                        @endif
                    </td>

                    <td class="text-end" x-show="showPriceColumns" x-cloak>
                        @if($canChangeSellingPrice)
                            <input type="number" step="any" x-model="item.retail_price" x-on:input="persistBackup()" class="form-control po-v2-cell-input text-end" />
                        @else
                            <span x-text="money(item.retail_price)"></span>
                        @endif
                    </td>

                    <td>
                        <input
                            type="date"
                            :id="'po-v2-exp-' + item.stock_id"
                            x-model="item.expiry_date"
                            x-on:change="persistBackup()"
                            x-on:keydown.enter.prevent="focusBatchFor(item.stock_id)"
                            class="form-control po-v2-cell-input"
                            style="min-width: 138px;"
                            :class="{'po-v2-cell-invalid': showValidation && requireMetaOnValidation && !item.expiry_date}"
                        />
                    </td>

                    <td>
                        <input
                            type="text"
                            :id="'po-v2-batch-' + item.stock_id"
                            x-model="item.batch_no"
                            x-on:input="persistBackup()"
                            placeholder="Batch #"
                            class="form-control po-v2-cell-input text-center"
                            :class="{'po-v2-cell-invalid': showValidation && requireMetaOnValidation && !item.batch_no}"
                        />
                    </td>

                    <td class="text-end fw-semibold" x-text="money(item.total)"></td>

                    <td class="text-center">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger py-0 px-2"
                            x-on:click="deleteItem(item.stock_id)"
                            title="Remove item"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>

    {{-- STICKY SUMMARY & ACTION BAR --}}
    <div class="po-v2-sticky-bar">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="po-v2-stat-pill">
                    <span class="text-muted">Items:</span>
                    <strong x-text="items.length"></strong>
                </div>
                <div class="po-v2-stat-pill">
                    <span class="text-muted">Total Units:</span>
                    <strong x-text="totalUnits"></strong>
                </div>
                <div class="po-v2-stat-pill bg-primary-subtle border-primary-subtle">
                    <span class="text-primary">Total Cost:</span>
                    <strong class="text-primary fs-6" x-text="money(netTotalRaw)"></strong>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                @if($canPreDraft)
                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        :disabled="submitting"
                        x-on:click="submitOrder('pre_draft')"
                        title="Save as Pre-Draft (Expiry & Batch optional)"
                    >
                        <span x-show="submittingAction === 'pre_draft'" x-cloak class="spinner-border spinner-border-sm me-1"></span>
                        <i x-show="submittingAction !== 'pre_draft'" class="fa fa-file-alt me-1"></i>
                        {{ $purchase->exists ? 'Update Pre-Draft' : 'Pre-Draft' }}
                    </button>
                @endif

                @if($canCreate)
                    <button
                        type="button"
                        class="btn btn-primary"
                        :disabled="submitting"
                        x-on:click="submitOrder('draft')"
                        title="Save as Draft Purchase Order (Ctrl+S)"
                    >
                        <span x-show="submittingAction === 'draft'" x-cloak class="spinner-border spinner-border-sm me-1"></span>
                        <i x-show="submittingAction !== 'draft'" class="fa fa-save me-1"></i>
                        {{ $purchase->exists ? 'Update Draft' : 'Draft Purchase' }}
                    </button>
                @endif

                @if($canComplete)
                    <button
                        type="button"
                        class="btn btn-success"
                        :disabled="submitting"
                        x-on:click="submitOrder('complete')"
                        title="Complete Purchase Order and Receive Stock (Ctrl+Enter)"
                    >
                        <span x-show="submittingAction === 'complete'" x-cloak class="spinner-border spinner-border-sm me-1"></span>
                        <i x-show="submittingAction !== 'complete'" class="fa fa-check-circle me-1"></i>
                        Complete Purchase
                    </button>
                @endif

                <a href="{{ route('purchase.index') }}" class="btn btn-outline-danger">Cancel</a>
            </div>
        </div>
    </div>

    <script>
        function purchaseOrderV2() {
            const initial = @js($initialData);
            const suppliers = @js($suppliersList);
            const departments = @js($departmentsList);
            const pageStatus = @js($pageStatus);
            const isEditMode = Boolean(initial.purchase_id);
            const backupKey = 'po_v2_draft_u{{ auth()->id() }}';
            const deptPrefKey = 'po_v2_last_dept_u{{ auth()->id() }}';

            return {
                // Reference data
                suppliers: suppliers,
                departments: departments,
                pageStatus: pageStatus,
                isEditMode: isEditMode,

                // Header state
                supplier_id: initial.supplier_id || '',
                supplierQuery: '',
                supplierOpen: false,
                supplierIndex: 0,
                department: initial.department || '',
                purchase_date: initial.date_created || '{{ todaysDate() }}',
                headerErrors: { supplier_id: false, department: false },

                // Quick-Add search state
                searchQuery: '',
                searchResults: [],
                searchOpen: false,
                searchEmpty: false,
                searchIndex: 0,
                searching: false,
                searchTimer: null,
                searchAbort: null,

                // Quick-Add entry draft
                draft: {
                    stock_id: '',
                    name: '',
                    allqty: 0,
                    qty_to_buy_1m: 0,
                    qty_1m_result: 0,
                    highest_qty_sold: 0,
                    suggested_qty: 1,
                    qty: '',
                    cost_price: '',
                    bulk_price: '',
                    whole_price: '',
                    retail_price: '',
                    expiry_date: '',
                    batch_no: '',
                },

                // Productivity toggles
                keepLastBatchExpiry: false,
                autoUseSuggestedQty: false,
                lastExpiry: '',
                lastBatch: '',
                showEntryPrices: false,
                showPriceColumns: false,

                // Table state
                items: (initial.purchaseitems || []).map(function (it) {
                    const qty = parseFloat(it.qty) || 0;
                    const cost = parseFloat(it.cost_price) || 0;
                    return {
                        stock_id: parseInt(it.stock_id, 10),
                        name: it.name || '',
                        qty: qty,
                        cost_price: cost,
                        bulk_price: it.bulk_price ?? 0,
                        whole_price: it.whole_price ?? 0,
                        retail_price: it.retail_price ?? 0,
                        highest_qty_sold: parseFloat(it.highest_qty_sold) || 0,
                        qty_to_buy_1m: parseFloat(it.qty_to_buy_1m) || 0,
                        qty_1m_result: parseFloat(it.qty_1m_result) || 0,
                        expiry_date: it.expiry_date || '',
                        batch_no: it.batch_no || '',
                        total: qty * cost,
                    };
                }),
                tableFilter: '',
                highlightedStockId: null,
                bulkExpiry: '',
                bulkBatch: '',

                // Validation & submission
                showValidation: false,
                requireMetaOnValidation: true,
                submitting: false,
                submittingAction: '',

                // Quick-loaders
                loadingLowStock: false,
                loadingRecentPos: false,
                recentPos: [],
                recentPoOpen: false,

                // Backup recovery
                hasSavedBackup: false,
                backupSummary: '',

                init() {
                    // Populate supplier display name if pre-selected (edit mode)
                    if (this.supplier_id) {
                        const found = this.suppliers.find(s => String(s.id) === String(this.supplier_id));
                        if (found) this.supplierQuery = found.name;
                    }

                    // Remember last department for brand-new POs
                    if (!this.department && !this.isEditMode) {
                        const savedDept = localStorage.getItem(deptPrefKey);
                        if (savedDept && this.departments.some(d => d.quantity_column === savedDept)) {
                            this.department = savedDept;
                        } else if (this.departments.length > 0) {
                            this.department = this.departments[0].quantity_column;
                        }
                    }

                    // Check for unsaved local backup (new PO only)
                    if (!this.isEditMode) {
                        this.checkBackup();
                    }

                    // Focus the right first field automatically
                    this.$nextTick(() => {
                        if (!this.supplier_id && this.$refs.supplierInput) {
                            this.$refs.supplierInput.focus();
                        } else if (this.$refs.productSearch) {
                            this.$refs.productSearch.focus();
                        }
                    });
                },

                /* ── Computed Getters ─────────────────────────────── */
                get filteredSuppliers() {
                    const q = (this.supplierQuery || '').trim().toLowerCase();
                    if (!q) return this.suppliers.slice(0, 40);
                    return this.suppliers
                        .filter(s => s.name.toLowerCase().includes(q))
                        .slice(0, 40);
                },

                get filteredItems() {
                    const q = (this.tableFilter || '').trim().toLowerCase();
                    if (!q) return this.items;
                    return this.items.filter(i =>
                        (i.name || '').toLowerCase().includes(q) ||
                        (i.batch_no || '').toLowerCase().includes(q)
                    );
                },

                get netTotalRaw() {
                    return this.items.reduce((sum, item) => sum + ((parseFloat(item.qty) || 0) * (parseFloat(item.cost_price) || 0)), 0);
                },

                get totalUnits() {
                    return this.items.reduce((sum, item) => sum + (parseInt(item.qty, 10) || 0), 0);
                },

                get emptyMetaCount() {
                    return this.items.filter(i => !i.expiry_date || !i.batch_no).length;
                },

                /* ── Global Keyboard Shortcuts ────────────────────── */
                handleGlobalKeydown(e) {
                    // F2 -> jump straight to Product Search
                    if (e.key === 'F2') {
                        e.preventDefault();
                        if (this.$refs.productSearch) {
                            this.$refs.productSearch.focus();
                            this.$refs.productSearch.select();
                        }
                        return;
                    }

                    // Ctrl+S / Cmd+S -> Save Draft (or Pre-Draft)
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                        e.preventDefault();
                        if (!this.submitting) {
                            this.submitOrder(this.pageStatus === '{{ status("Pre-Draft") }}' ? 'pre_draft' : 'draft');
                        }
                        return;
                    }

                    // Ctrl+Enter / Cmd+Enter -> Complete Purchase (when allowed)
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        @if($canComplete)
                        e.preventDefault();
                        if (!this.submitting) {
                            this.submitOrder('complete');
                        }
                        @endif
                    }
                },

                /* ── Supplier Combobox ────────────────────────────── */
                openSupplierList() {
                    this.supplierOpen = true;
                    this.supplierIndex = 0;
                },

                moveSupplier(delta) {
                    const list = this.filteredSuppliers;
                    if (list.length === 0) return;
                    this.supplierOpen = true;
                    this.supplierIndex = (this.supplierIndex + delta + list.length) % list.length;
                },

                pickActiveSupplier() {
                    const list = this.filteredSuppliers;
                    if (list.length === 0) return;
                    const chosen = list[this.supplierIndex] || list[0];
                    if (chosen) this.selectSupplier(chosen);
                },

                selectSupplier(sup) {
                    this.supplier_id = String(sup.id);
                    this.supplierQuery = sup.name;
                    this.supplierOpen = false;
                    this.headerErrors.supplier_id = false;
                    this.recentPos = [];
                    this.recentPoOpen = false;
                    this.persistBackup();

                    // Jump straight to product search (0 extra clicks!)
                    this.$nextTick(() => {
                        if (this.$refs.productSearch) {
                            this.$refs.productSearch.focus();
                        }
                    });
                },

                clearSupplier(refocus) {
                    this.supplier_id = '';
                    this.supplierQuery = '';
                    this.recentPos = [];
                    this.recentPoOpen = false;
                    if (refocus && this.$refs.supplierInput) {
                        this.supplierOpen = true;
                        this.$refs.supplierInput.focus();
                    }
                },

                onDepartmentChange() {
                    this.headerErrors.department = false;
                    if (this.department) {
                        localStorage.setItem(deptPrefKey, this.department);
                    }
                    this.persistBackup();
                },

                /* ── Product Search & Barcode Scanner ─────────────── */
                onSearchInput() {
                    const q = (this.searchQuery || '').trim();
                    this.searchEmpty = false;
                    if (this.searchTimer) clearTimeout(this.searchTimer);

                    if (q.length < 2) {
                        this.searchResults = [];
                        this.searchOpen = false;
                        return;
                    }

                    this.searchTimer = setTimeout(() => this.fetchProducts(q, false), 160);
                },

                async fetchProducts(query, autoPickExact) {
                    if (this.searchAbort) this.searchAbort.abort();
                    this.searchAbort = new AbortController();
                    this.searching = true;

                    try {
                        const col = this.department || 'quantity';
                        const url = '{{ route("purchase.v2.search") }}' + '?q=' + encodeURIComponent(query) + '&column=' + encodeURIComponent(col);
                        const res = await fetch(url, {
                            headers: { 'Accept': 'application/json' },
                            signal: this.searchAbort.signal
                        });
                        if (!res.ok) throw new Error('Search failed');
                        const data = await res.json();

                        this.searchResults = Array.isArray(data) ? data : [];
                        this.searchIndex = 0;
                        this.searchEmpty = this.searchResults.length === 0;

                        // Instant select on exact barcode scan or Enter with single match
                        if (this.searchResults.length === 1 && (this.searchResults[0].exact_barcode || autoPickExact)) {
                            this.selectProduct(this.searchResults[0]);
                            return;
                        }

                        if (autoPickExact && this.searchResults.length > 0) {
                            this.selectProduct(this.searchResults[0]);
                            return;
                        }

                        this.searchOpen = true;
                    } catch (err) {
                        if (err.name !== 'AbortError') {
                            this.toast('error', 'Product search failed. Please try again.');
                        }
                    } finally {
                        this.searching = false;
                    }
                },

                moveSearch(delta) {
                    if (!this.searchOpen || this.searchResults.length === 0) return;
                    this.searchIndex = (this.searchIndex + delta + this.searchResults.length) % this.searchResults.length;
                },

                onSearchEnter() {
                    if (this.searchOpen && this.searchResults.length > 0) {
                        const chosen = this.searchResults[this.searchIndex] || this.searchResults[0];
                        if (chosen) {
                            this.selectProduct(chosen);
                            return;
                        }
                    }

                    const q = (this.searchQuery || '').trim();
                    if (q.length >= 2) {
                        if (this.searchTimer) clearTimeout(this.searchTimer);
                        this.fetchProducts(q, true);
                    }
                },

                isInList(stockId) {
                    return this.items.some(i => parseInt(i.stock_id, 10) === parseInt(stockId, 10));
                },

                selectProduct(prod) {
                    this.searchOpen = false;
                    this.searchResults = [];

                    // If product is already in table: jump to its Qty cell (or increment on barcode scan)
                    const existing = this.items.find(i => parseInt(i.stock_id, 10) === parseInt(prod.id, 10));
                    if (existing) {
                        if (prod.exact_barcode) {
                            existing.qty = (parseInt(existing.qty, 10) || 0) + 1;
                            this.recalcItem(existing);
                            this.toast('info', existing.name + ' quantity incremented to ' + existing.qty);
                        } else {
                            this.toast('info', existing.name + ' is already in the list — focused its row.');
                        }
                        this.flashRow(existing.stock_id);
                        this.resetDraft(false);
                        this.$nextTick(() => {
                            const qtyEl = document.getElementById('po-v2-qty-' + existing.stock_id);
                            if (qtyEl) {
                                qtyEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                qtyEl.focus();
                                qtyEl.select();
                            }
                        });
                        return;
                    }

                    this.searchQuery = prod.name;
                    this.draft = {
                        stock_id: prod.id,
                        name: prod.name,
                        allqty: prod.allqty ?? 0,
                        qty_to_buy_1m: prod.qty_to_buy_1m ?? 0,
                        qty_1m_result: prod.qty_1m_result ?? 0,
                        highest_qty_sold: prod.highest_qty_sold ?? 0,
                        suggested_qty: prod.suggested_qty ?? 1,
                        qty: this.autoUseSuggestedQty ? (prod.suggested_qty || 1) : 1,
                        cost_price: prod.cost_price ?? 0,
                        bulk_price: prod.bulk_price ?? 0,
                        whole_price: prod.whole_price ?? 0,
                        retail_price: prod.retail_price ?? 0,
                        expiry_date: this.keepLastBatchExpiry ? this.lastExpiry : '',
                        batch_no: this.keepLastBatchExpiry ? this.lastBatch : '',
                    };

                    // Immediately focus & select Qty so user can type quantity right away
                    this.$nextTick(() => {
                        if (this.$refs.entryQty) {
                            this.$refs.entryQty.focus();
                            this.$refs.entryQty.select();
                        }
                    });
                },

                /* ── Keyboard Pipeline inside Quick-Add Bar ───────── */
                onEntryEnter(event, field) {
                    if (event.shiftKey) {
                        this.addCurrentItem();
                        return;
                    }

                    if (field === 'qty') {
                        this.$refs.entryCost.focus();
                        this.$refs.entryCost.select();
                    } else if (field === 'cost') {
                        this.$refs.entryExpiry.focus();
                        this.$refs.entryExpiry.select();
                    } else if (field === 'expiry') {
                        this.normalizeDraftExpiry();
                        this.$refs.entryBatch.focus();
                        this.$refs.entryBatch.select();
                    }
                },

                /* ── Smart Expiry Date Parser (MM/YY, MMYY, MM/YYYY, YYYY-MM-DD) ── */
                parseSmartDate(raw) {
                    const s = (raw || '').trim();
                    if (!s) return '';

                    // Already YYYY-MM-DD
                    let m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
                    if (m) {
                        const y = parseInt(m[1], 10), mo = parseInt(m[2], 10), d = parseInt(m[3], 10);
                        if (mo >= 1 && mo <= 12 && d >= 1 && d <= 31) {
                            return `${y}-${String(mo).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                        }
                    }

                    // DD/MM/YYYY or DD-MM-YYYY
                    m = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
                    if (m) {
                        const d = parseInt(m[1], 10), mo = parseInt(m[2], 10), y = parseInt(m[3], 10);
                        if (mo >= 1 && mo <= 12 && d >= 1 && d <= 31) {
                            return `${y}-${String(mo).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                        }
                    }

                    // MM/YY or MM-YY -> last day of that month
                    m = s.match(/^(\d{1,2})[\/\-](\d{2})$/);
                    if (m) {
                        const mo = parseInt(m[1], 10), y = 2000 + parseInt(m[2], 10);
                        if (mo >= 1 && mo <= 12) return this.endOfMonth(y, mo);
                    }

                    // MMYY (4 digits, e.g. 0828) -> last day of month
                    m = s.match(/^(\d{2})(\d{2})$/);
                    if (m) {
                        const mo = parseInt(m[1], 10), y = 2000 + parseInt(m[2], 10);
                        if (mo >= 1 && mo <= 12) return this.endOfMonth(y, mo);
                    }

                    // MM/YYYY or MM-YYYY
                    m = s.match(/^(\d{1,2})[\/\-](\d{4})$/);
                    if (m) {
                        const mo = parseInt(m[1], 10), y = parseInt(m[2], 10);
                        if (mo >= 1 && mo <= 12) return this.endOfMonth(y, mo);
                    }

                    // YYYY-MM
                    m = s.match(/^(\d{4})-(\d{1,2})$/);
                    if (m) {
                        const y = parseInt(m[1], 10), mo = parseInt(m[2], 10);
                        if (mo >= 1 && mo <= 12) return this.endOfMonth(y, mo);
                    }

                    return null;
                },

                endOfMonth(year, month) {
                    const lastDay = new Date(year, month, 0).getDate();
                    return `${year}-${String(month).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
                },

                normalizeDraftExpiry() {
                    if (!this.draft.expiry_date) return;
                    const parsed = this.parseSmartDate(this.draft.expiry_date);
                    if (parsed) {
                        this.draft.expiry_date = parsed;
                    } else {
                        this.toast('warning', 'Expiry format not recognized. Use MM/YY (e.g. 08/28) or YYYY-MM-DD.');
                    }
                },

                normalizeRowExpiry(item) {
                    if (!item.expiry_date) {
                        this.persistBackup();
                        return;
                    }
                    const parsed = this.parseSmartDate(item.expiry_date);
                    if (parsed) {
                        item.expiry_date = parsed;
                    } else {
                        this.toast('warning', 'Invalid expiry format on ' + item.name + '. Use MM/YY or YYYY-MM-DD.');
                    }
                    this.persistBackup();
                },

                openNativeDatePicker(el) {
                    if (!el) return;
                    if (typeof el.showPicker === 'function') {
                        el.showPicker();
                    } else {
                        el.click();
                    }
                },

                focusBatchFor(stockId) {
                    const el = document.getElementById('po-v2-batch-' + stockId);
                    if (el) { el.focus(); el.select(); }
                },

                /* ── Add / Edit / Delete Items ────────────────────── */
                addCurrentItem() {
                    if (!this.draft.stock_id) {
                        this.toast('warning', 'Search and select a product first.');
                        if (this.$refs.productSearch) this.$refs.productSearch.focus();
                        return;
                    }

                    const qty = parseInt(this.draft.qty, 10);
                    if (!(qty > 0)) {
                        this.toast('warning', 'Please enter a quantity greater than 0.');
                        if (this.$refs.entryQty) { this.$refs.entryQty.focus(); this.$refs.entryQty.select(); }
                        return;
                    }

                    if (this.draft.expiry_date) {
                        const parsed = this.parseSmartDate(this.draft.expiry_date);
                        if (!parsed) {
                            this.toast('warning', 'Invalid expiry date. Use MM/YY (e.g. 08/28) or YYYY-MM-DD.');
                            if (this.$refs.entryExpiry) { this.$refs.entryExpiry.focus(); this.$refs.entryExpiry.select(); }
                            return;
                        }
                        this.draft.expiry_date = parsed;
                    }

                    const cost = parseFloat(this.draft.cost_price) || 0;
                    const newItem = {
                        stock_id: parseInt(this.draft.stock_id, 10),
                        name: this.draft.name,
                        qty: qty,
                        cost_price: cost,
                        bulk_price: parseFloat(this.draft.bulk_price) || 0,
                        whole_price: parseFloat(this.draft.whole_price) || 0,
                        retail_price: parseFloat(this.draft.retail_price) || 0,
                        highest_qty_sold: parseFloat(this.draft.highest_qty_sold) || 0,
                        qty_to_buy_1m: parseFloat(this.draft.qty_to_buy_1m) || 0,
                        qty_1m_result: parseFloat(this.draft.qty_1m_result) || 0,
                        expiry_date: (this.draft.expiry_date || '').trim(),
                        batch_no: (this.draft.batch_no || '').trim(),
                        total: qty * cost,
                    };

                    if (newItem.expiry_date) this.lastExpiry = newItem.expiry_date;
                    if (newItem.batch_no) this.lastBatch = newItem.batch_no;

                    // Prepend to top so newly added item is immediately visible without scrolling
                    this.items.unshift(newItem);
                    this.flashRow(newItem.stock_id);
                    this.resetDraft(true);
                    this.persistBackup();
                },

                resetDraft(focusSearch) {
                    this.searchQuery = '';
                    this.searchResults = [];
                    this.searchOpen = false;
                    this.searchEmpty = false;
                    this.draft = {
                        stock_id: '',
                        name: '',
                        allqty: 0,
                        qty_to_buy_1m: 0,
                        qty_1m_result: 0,
                        highest_qty_sold: 0,
                        suggested_qty: 1,
                        qty: '',
                        cost_price: '',
                        bulk_price: '',
                        whole_price: '',
                        retail_price: '',
                        expiry_date: '',
                        batch_no: '',
                    };
                    if (focusSearch) {
                        this.$nextTick(() => {
                            if (this.$refs.productSearch) this.$refs.productSearch.focus();
                        });
                    }
                },

                recalcItem(item) {
                    item.total = (parseFloat(item.qty) || 0) * (parseFloat(item.cost_price) || 0);
                    this.persistBackup();
                },

                deleteItem(stockId) {
                    this.items = this.items.filter(i => parseInt(i.stock_id, 10) !== parseInt(stockId, 10));
                    this.persistBackup();
                },

                clearAllItems() {
                    if (this.items.length === 0) return;
                    if (!confirm('Remove all ' + this.items.length + ' item(s) from this purchase order?')) return;
                    this.items = [];
                    this.persistBackup();
                },

                flashRow(stockId) {
                    this.highlightedStockId = null;
                    this.$nextTick(() => {
                        this.highlightedStockId = stockId;
                        setTimeout(() => {
                            if (this.highlightedStockId === stockId) this.highlightedStockId = null;
                        }, 1300);
                    });
                },

                /* ── Bulk Helpers ─────────────────────────────────── */
                applyBulkMeta() {
                    let parsedExp = '';
                    if (this.bulkExpiry) {
                        parsedExp = this.parseSmartDate(this.bulkExpiry);
                        if (!parsedExp) {
                            this.toast('warning', 'Bulk expiry format invalid. Use MM/YY or YYYY-MM-DD.');
                            return;
                        }
                        this.bulkExpiry = parsedExp;
                    }

                    const batchVal = (this.bulkBatch || '').trim();
                    if (!parsedExp && !batchVal) {
                        this.toast('warning', 'Enter an Expiry or Batch value first.');
                        return;
                    }

                    let updated = 0;
                    this.items.forEach(item => {
                        let changed = false;
                        if (parsedExp && !item.expiry_date) {
                            item.expiry_date = parsedExp;
                            changed = true;
                        }
                        if (batchVal && !item.batch_no) {
                            item.batch_no = batchVal;
                            changed = true;
                        }
                        if (changed) updated++;
                    });

                    this.persistBackup();
                    this.toast('success', 'Filled ' + updated + ' row(s).');
                },

                /* ── 1-Click Load Low Stock for Supplier ──────────── */
                async loadSupplierLowStock() {
                    if (!this.supplier_id || !this.department) {
                        this.toast('warning', 'Select Supplier and Department first.');
                        return;
                    }
                    this.loadingLowStock = true;
                    try {
                        const url = '{{ route("purchase.v2.low_stock") }}'
                            + '?supplier_id=' + encodeURIComponent(this.supplier_id)
                            + '&column=' + encodeURIComponent(this.department);
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error('Failed to load low stock');
                        const list = await res.json();

                        if (!Array.isArray(list) || list.length === 0) {
                            this.toast('info', 'No near-out-of-stock products found for this supplier.');
                            return;
                        }

                        let added = 0;
                        list.forEach(prod => {
                            if (this.isInList(prod.id)) return;
                            const qty = prod.suggested_qty > 0 ? prod.suggested_qty : 1;
                            const cost = parseFloat(prod.cost_price) || 0;
                            this.items.push({
                                stock_id: parseInt(prod.id, 10),
                                name: prod.name,
                                qty: qty,
                                cost_price: cost,
                                bulk_price: parseFloat(prod.bulk_price) || 0,
                                whole_price: parseFloat(prod.whole_price) || 0,
                                retail_price: parseFloat(prod.retail_price) || 0,
                                highest_qty_sold: parseFloat(prod.highest_qty_sold) || 0,
                                qty_to_buy_1m: parseFloat(prod.qty_to_buy_1m) || 0,
                                qty_1m_result: parseFloat(prod.qty_1m_result) || 0,
                                expiry_date: '',
                                batch_no: '',
                                total: qty * cost,
                            });
                            added++;
                        });

                        this.persistBackup();
                        this.toast('success', added > 0
                            ? 'Loaded ' + added + ' low-stock product(s) with suggested quantities.'
                            : 'All low-stock products for this supplier are already in the list.');
                    } catch (e) {
                        this.toast('error', 'Could not load low-stock items.');
                    } finally {
                        this.loadingLowStock = false;
                    }
                },

                /* ── 1-Click Copy Previous PO ─────────────────────── */
                async toggleRecentPos() {
                    if (this.recentPoOpen) {
                        this.recentPoOpen = false;
                        return;
                    }
                    if (!this.supplier_id) return;

                    if (this.recentPos.length > 0) {
                        this.recentPoOpen = true;
                        return;
                    }

                    this.loadingRecentPos = true;
                    try {
                        const url = '{{ route("purchase.v2.recent_pos") }}' + '?supplier_id=' + encodeURIComponent(this.supplier_id);
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error('Failed');
                        this.recentPos = await res.json();
                        this.recentPoOpen = true;
                    } catch (e) {
                        this.toast('error', 'Could not load recent purchase orders.');
                    } finally {
                        this.loadingRecentPos = false;
                    }
                },

                async cloneFromPo(po) {
                    this.recentPoOpen = false;
                    this.loadingRecentPos = true;
                    try {
                        const url = '{{ url("/purchase/v2/api/clone-po") }}/' + po.id + '?column=' + encodeURIComponent(this.department || po.department || 'quantity');
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error('Failed');
                        const list = await res.json();

                        let added = 0;
                        list.forEach(prod => {
                            if (this.isInList(prod.id)) return;
                            const qty = prod.previous_qty > 0 ? prod.previous_qty : 1;
                            const cost = parseFloat(prod.cost_price) || 0;
                            this.items.push({
                                stock_id: parseInt(prod.id, 10),
                                name: prod.name,
                                qty: qty,
                                cost_price: cost,
                                bulk_price: parseFloat(prod.bulk_price) || 0,
                                whole_price: parseFloat(prod.whole_price) || 0,
                                retail_price: parseFloat(prod.retail_price) || 0,
                                highest_qty_sold: parseFloat(prod.highest_qty_sold) || 0,
                                qty_to_buy_1m: parseFloat(prod.qty_to_buy_1m) || 0,
                                qty_1m_result: parseFloat(prod.qty_1m_result) || 0,
                                expiry_date: '',
                                batch_no: '',
                                total: qty * cost,
                            });
                            added++;
                        });

                        this.persistBackup();
                        this.toast('success', 'Copied ' + added + ' item(s) from PO #' + po.id + '.');
                    } catch (e) {
                        this.toast('error', 'Failed to copy items from PO #' + po.id);
                    } finally {
                        this.loadingRecentPos = false;
                    }
                },

                /* ── LocalStorage Auto-Backup ─────────────────────── */
                persistBackup() {
                    if (this.isEditMode) return;
                    try {
                        if (this.items.length === 0 && !this.supplier_id) {
                            localStorage.removeItem(backupKey);
                            return;
                        }
                        localStorage.setItem(backupKey, JSON.stringify({
                            supplier_id: this.supplier_id,
                            supplierQuery: this.supplierQuery,
                            department: this.department,
                            purchase_date: this.purchase_date,
                            items: this.items,
                            savedAt: Date.now(),
                        }));
                    } catch (e) {}
                },

                checkBackup() {
                    try {
                        const raw = localStorage.getItem(backupKey);
                        if (!raw) return;
                        const data = JSON.parse(raw);
                        if (!data || !Array.isArray(data.items) || data.items.length === 0) return;
                        // Ignore backups older than 24h
                        if (Date.now() - (data.savedAt || 0) > 86400000) {
                            localStorage.removeItem(backupKey);
                            return;
                        }
                        this.hasSavedBackup = true;
                        this.backupSummary = data.items.length + ' item(s)' + (data.supplierQuery ? ' for ' + data.supplierQuery : '');
                    } catch (e) {}
                },

                restoreBackup() {
                    try {
                        const data = JSON.parse(localStorage.getItem(backupKey) || '{}');
                        if (data.supplier_id) {
                            this.supplier_id = data.supplier_id;
                            this.supplierQuery = data.supplierQuery || '';
                        }
                        if (data.department) this.department = data.department;
                        if (data.purchase_date) this.purchase_date = data.purchase_date;
                        if (Array.isArray(data.items)) this.items = data.items;
                        this.hasSavedBackup = false;
                        this.toast('success', 'Restored ' + this.items.length + ' item(s).');
                    } catch (e) {
                        this.discardBackup();
                    }
                },

                discardBackup() {
                    this.hasSavedBackup = false;
                    localStorage.removeItem(backupKey);
                },

                /* ── Validation & Submission ──────────────────────── */
                validateBeforeSubmit(requireMeta) {
                    this.showValidation = true;
                    this.requireMetaOnValidation = requireMeta;
                    this.headerErrors = { supplier_id: false, department: false };

                    if (!this.supplier_id) {
                        this.headerErrors.supplier_id = true;
                        this.toast('error', 'Please select a Supplier.');
                        if (this.$refs.supplierInput) this.$refs.supplierInput.focus();
                        return false;
                    }

                    if (!this.department) {
                        this.headerErrors.department = true;
                        this.toast('error', 'Please select a Department.');
                        return false;
                    }

                    if (this.items.length === 0) {
                        this.toast('error', 'Please add at least one product to the purchase order.');
                        if (this.$refs.productSearch) this.$refs.productSearch.focus();
                        return false;
                    }

                    // Normalize any typed MM/YY dates before validating
                    for (const item of this.items) {
                        if (item.expiry_date) {
                            const parsed = this.parseSmartDate(item.expiry_date);
                            if (parsed) item.expiry_date = parsed;
                        }
                    }

                    for (const item of this.items) {
                        if (!(parseInt(item.qty, 10) > 0)) {
                            this.toast('error', 'Quantity must be greater than 0 for ' + item.name);
                            this.focusCell('po-v2-qty-' + item.stock_id);
                            return false;
                        }
                        if (requireMeta && !item.expiry_date) {
                            this.toast('error', 'Please enter Expiry Date for ' + item.name);
                            this.focusCell('po-v2-exp-' + item.stock_id);
                            return false;
                        }
                        if (requireMeta && !item.batch_no) {
                            this.toast('error', 'Please enter Batch Number for ' + item.name);
                            this.focusCell('po-v2-batch-' + item.stock_id);
                            return false;
                        }
                    }

                    return true;
                },

                focusCell(id) {
                    this.$nextTick(() => {
                        const el = document.getElementById(id);
                        if (el) {
                            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            el.focus();
                            el.select();
                        }
                    });
                },

                async submitOrder(action) {
                    const requireMeta = (action !== 'pre_draft');
                    if (!this.validateBeforeSubmit(requireMeta)) return;

                    if (action === 'complete') {
                        const supName = this.supplierQuery || 'selected supplier';
                        const msg = 'Complete this Purchase Order for ' + supName + '?\n\n'
                            + 'Items: ' + this.items.length + ' (' + this.totalUnits + ' units)\n'
                            + 'Total Cost: ' + this.money(this.netTotalRaw) + '\n\n'
                            + 'Stock quantities and supplier credit ledger will be updated immediately.';
                        if (!confirm(msg)) return;
                    }

                    this.submitting = true;
                    this.submittingAction = action;

                    const payload = {
                        supplier_id: this.supplier_id,
                        department: this.department,
                        date_created: this.purchase_date,
                        purchaseitems: this.items.map(i => ({
                            stock_id: i.stock_id,
                            name: i.name,
                            qty: parseInt(i.qty, 10) || 0,
                            cost_price: parseFloat(i.cost_price) || 0,
                            bulk_price: i.bulk_price,
                            whole_price: i.whole_price,
                            retail_price: i.retail_price,
                            highest_qty_sold: i.highest_qty_sold,
                            qty_to_buy_1m: i.qty_to_buy_1m,
                            qty_1m_result: i.qty_1m_result,
                            expiry_date: i.expiry_date,
                            batch_no: i.batch_no,
                        })),
                    };

                    try {
                        let result;
                        if (action === 'draft') {
                            result = await @this.draftPurchase(payload);
                        } else if (action === 'pre_draft') {
                            result = await @this.preDraftPurchase(payload);
                        } else if (action === 'complete') {
                            result = await @this.completePurchase(payload);
                        }

                        if (result && result.status) {
                            localStorage.removeItem(backupKey);
                            setTimeout(() => {
                                window.location.href = result.redirect || '{{ route("purchase.index") }}';
                            }, 900);
                        } else {
                            this.toast('error', (result && result.message) ? result.message : 'Unable to save purchase order.');
                            this.submitting = false;
                            this.submittingAction = '';
                        }
                    } catch (err) {
                        this.toast('error', 'Server error while saving purchase order. Your items are preserved.');
                        this.submitting = false;
                        this.submittingAction = '';
                    }
                },

                /* ── Formatting & Notifications ───────────────────── */
                money(amount) {
                    const n = parseFloat(amount);
                    const safe = isNaN(n) ? 0 : n;
                    return typeof formatMoney === 'function' ? formatMoney(safe) : safe.toFixed(2);
                },

                toast(type, message) {
                    if (typeof notify === 'function') {
                        notify(type, message, { position: 'topRight', timeout: 3000 });
                    } else if (typeof iziToast !== 'undefined' && iziToast[type]) {
                        iziToast[type]({ message: message, position: 'topRight', timeout: 3000 });
                    } else {
                        alert(message);
                    }
                },
            };
        }
    </script>
</div>
</div>
