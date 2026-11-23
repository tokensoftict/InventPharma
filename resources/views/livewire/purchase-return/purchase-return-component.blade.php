<div>
<div
    wire:ignore
    x-data="purchaseReturnFast()"
    x-init="init()"
    x-on:keydown.window="handleGlobalKeydown($event)"
>
    {{-- Unsaved Local Backup Banner (new return only) --}}
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

    {{-- Top Shortcut Hint Strip --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                <i class="fa fa-undo me-1"></i> Fast Return Entry
            </span>
            <span class="text-muted small d-none d-md-inline">
                <span class="pr-fast-kbd">F2</span> Search Product
                &nbsp;&middot;&nbsp;
                <span class="pr-fast-kbd">Enter</span> Next field / Add row
                &nbsp;&middot;&nbsp;
                <span class="pr-fast-kbd">Ctrl+S</span> Save Draft
                &nbsp;&middot;&nbsp;
                Expiry accepts <span class="pr-fast-kbd">MM/YY</span> (e.g. <code>08/28</code>)
            </span>
        </div>
    </div>

    {{-- HEADER BAR: Supplier + Department + Return Date + Linked PO + Notes (Zero Reload!) --}}
    <div class="pr-fast-header mb-3">
        <div class="row g-2 align-items-end">
            {{-- Instant Searchable Supplier Combobox --}}
            <div class="col-lg-5 col-md-6" x-on:click.outside="supplierOpen = false">
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
                            :class="{'pr-fast-cell-invalid': headerErrors.supplier_id}"
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

                    <div class="pr-fast-dropdown" x-show="supplierOpen" x-cloak>
                        <template x-if="filteredSuppliers.length === 0">
                            <div class="p-3 text-muted small text-center">No matching supplier</div>
                        </template>
                        <template x-for="(sup, sIdx) in filteredSuppliers" :key="sup.id">
                            <div
                                class="pr-fast-dropdown-item d-flex justify-content-between align-items-center"
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

            {{-- Department (Server-rendered options so edit ALWAYS pre-selects) --}}
            <div class="col-lg-4 col-md-4">
                <label class="form-label mb-1 fw-semibold small">
                    Department <span class="text-danger">*</span>
                </label>
                <select
                    class="form-select form-select-sm"
                    x-ref="departmentSelect"
                    x-model="department"
                    x-on:change="onDepartmentChange()"
                    :class="{'pr-fast-cell-invalid': headerErrors.department}"
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

            {{-- Return Date --}}
            <div class="col-lg-3 col-md-2">
                <label class="form-label mb-1 fw-semibold small">
                    Return Date <span class="text-danger">*</span>
                </label>
                <input
                    type="date"
                    class="form-control form-control-sm"
                    x-model="return_date"
                    x-on:change="headerErrors.return_date = false; persistBackup()"
                    :class="{'pr-fast-cell-invalid': headerErrors.return_date}"
                />
            </div>
        </div>

        {{-- Row 2: Linked Purchase Order (filtered to supplier, searchable by PO #) + Notes --}}
        <div class="row g-2 align-items-end mt-2 pt-2 border-top">
            <div class="col-lg-6 col-md-6" x-on:click.outside="poOpen = false">
                <label class="form-label mb-1 fw-semibold small">
                    Linked Purchase Order
                    <span class="text-muted fw-normal">(Optional — filtered to supplier, search by PO #)</span>
                </label>
                <div class="position-relative">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white">
                            <span x-show="loadingPos" x-cloak class="spinner-border spinner-border-sm text-primary"></span>
                            <i x-show="!loadingPos" class="fa fa-file-invoice text-muted"></i>
                        </span>
                        <input
                            type="text"
                            x-model="poQuery"
                            :disabled="!supplier_id"
                            x-on:focus="openPoList()"
                            x-on:input="poOpen = true; poIndex = 0; if (!poQuery) { purchase_id = ''; persistBackup(); }"
                            x-on:keydown.arrow-down.prevent="movePo(1)"
                            x-on:keydown.arrow-up.prevent="movePo(-1)"
                            x-on:keydown.enter.prevent="pickActivePo()"
                            x-on:keydown.escape="poOpen = false"
                            :placeholder="supplier_id ? 'Search by PO # or date...' : 'Select a supplier first...'"
                            autocomplete="off"
                            class="form-control"
                        />
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            x-show="purchase_id !== ''"
                            x-on:click="clearPo()"
                            title="Unlink PO"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-primary"
                            :disabled="!purchase_id || loadingPoItems"
                            x-on:click="loadItemsFromLinkedPo()"
                            title="Load all items from this Purchase Order into the return table"
                        >
                            <span x-show="loadingPoItems" x-cloak class="spinner-border spinner-border-sm me-1"></span>
                            <i x-show="!loadingPoItems" class="fa fa-download me-1"></i>
                            Load PO Items
                        </button>
                    </div>

                    <div class="pr-fast-dropdown" x-show="poOpen && supplier_id" x-cloak>
                        <template x-if="filteredPos.length === 0">
                            <div class="p-3 text-muted small text-center">No completed purchase order found for this supplier</div>
                        </template>
                        <template x-for="(po, idx) in filteredPos" :key="po.id">
                            <div
                                class="pr-fast-dropdown-item"
                                :class="{'is-active': idx === poIndex}"
                                x-on:mouseenter="poIndex = idx"
                                x-on:mousedown.prevent="selectPo(po)"
                            >
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong x-text="'PO #' + po.id"></strong>
                                    <span class="badge bg-light text-dark border" x-text="po.items_count + ' item' + (po.items_count === 1 ? '' : 's')"></span>
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

            <div class="col-lg-6 col-md-6">
                <label class="form-label mb-1 fw-semibold small">
                    Notes <span class="text-muted fw-normal">(Optional)</span>
                </label>
                <input
                    type="text"
                    class="form-control form-control-sm"
                    x-model="remark"
                    x-on:input="persistBackup()"
                    placeholder="Additional notes about this return..."
                />
            </div>
        </div>
    </div>

    {{-- QUICK-ADD ENTRY BAR (Single keyboard-driven pipeline) --}}
    <div class="pr-fast-entry mb-3">
        <div class="row g-2 align-items-end">
            {{-- Product Search / Barcode Scanner --}}
            <div class="col-xl-3 col-lg-4 col-md-12" x-on:click.outside="searchOpen = false">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0 fw-semibold small">
                        Stock / Barcode <span class="pr-fast-kbd ms-1">F2</span>
                    </label>
                    <span x-show="draft.stock_id" x-cloak class="small text-muted">
                        Dept Av: <strong :class="draft.dept_qty <= 0 ? 'text-danger' : 'text-success'" x-text="draft.dept_qty"></strong>
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
                            placeholder="Scan barcode or search stock..."
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

                    <div class="pr-fast-dropdown" x-show="searchOpen && (searchResults.length > 0 || searchEmpty)" x-cloak>
                        <template x-if="searchEmpty && searchResults.length === 0">
                            <div class="p-3 text-muted small text-center">No active products found</div>
                        </template>
                        <template x-for="(prod, pIdx) in searchResults" :key="prod.id">
                            <div
                                class="pr-fast-dropdown-item"
                                :class="{'is-active': pIdx === searchIndex}"
                                x-on:mouseenter="searchIndex = pIdx"
                                x-on:mousedown.prevent="selectProduct(prod)"
                            >
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="fw-semibold text-dark" style="font-size: 13px;" x-text="prod.name"></div>
                                    <span
                                        x-show="isInList(prod.id)"
                                        class="badge bg-success-subtle text-success border border-success-subtle flex-shrink-0"
                                    >In List</span>
                                </div>
                                <div class="d-flex flex-wrap gap-3 mt-1 small text-muted">
                                    <span>Dept Avail: <strong :class="prod.dept_qty <= 0 ? 'text-danger' : 'text-success'" x-text="prod.dept_qty"></strong></span>
                                    <span>Cost: <strong class="text-dark" x-text="money(prod.cost_price)"></strong></span>
                                    <span x-show="prod.batch_no">Batch: <strong class="text-dark" x-text="prod.batch_no"></strong></span>
                                    <span x-show="prod.expiry_date">Exp: <strong class="text-dark" x-text="prod.expiry_date"></strong></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Quantity to Return --}}
            <div class="col-xl-1 col-lg-2 col-md-3 col-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0 fw-semibold small">Qty</label>
                    <button
                        type="button"
                        x-show="draft.stock_id && draft.dept_qty > 0"
                        x-cloak
                        x-on:click="draft.qty = draft.dept_qty; $refs.entryCost.focus(); $refs.entryCost.select();"
                        class="btn btn-link p-0 text-decoration-none"
                        style="font-size: 10.5px;"
                        title="Return all available stock in this department"
                        x-text="'Max ' + draft.dept_qty"
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
            <div class="col-xl-1 col-lg-2 col-md-3 col-6">
                <label class="form-label mb-1 fw-semibold small">Batch No</label>
                <input
                    type="text"
                    x-ref="entryBatch"
                    x-model="draft.batch_no"
                    x-on:keydown.enter.prevent="onEntryEnter($event, 'batch')"
                    placeholder="Batch #"
                    class="form-control"
                />
            </div>

            {{-- Reason for Return (Searchable combobox + custom free-text) --}}
            <div class="col-xl-2 col-lg-8 col-md-9" x-on:click.outside="reasonOpen = false">
                <label class="form-label mb-1 fw-semibold small">Reason for Return</label>
                <div class="position-relative">
                    <input
                        type="text"
                        x-ref="entryReason"
                        x-model="draft.reason"
                        x-on:focus="reasonOpen = true; reasonIndex = 0"
                        x-on:input="reasonOpen = true; reasonIndex = 0"
                        x-on:keydown.arrow-down.prevent="moveReason(1)"
                        x-on:keydown.arrow-up.prevent="moveReason(-1)"
                        x-on:keydown.enter.prevent="onReasonEnter()"
                        x-on:keydown.escape="reasonOpen = false"
                        placeholder="Select or type reason..."
                        autocomplete="off"
                        class="form-control"
                    />
                    <div class="pr-fast-dropdown" x-show="reasonOpen && filteredReasons.length > 0" x-cloak>
                        <template x-for="(r, rIdx) in filteredReasons" :key="r">
                            <div
                                class="pr-fast-dropdown-item py-1 px-2 small"
                                :class="{'is-active': rIdx === reasonIndex}"
                                x-on:mouseenter="reasonIndex = rIdx"
                                x-on:mousedown.prevent="selectReason(r)"
                                x-text="r"
                            ></div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Add Button --}}
            <div class="col-xl-1 col-lg-4 col-md-3 d-grid">
                <button
                    type="button"
                    class="btn btn-primary"
                    x-on:click="addCurrentItem()"
                    title="Add item to return list (Enter)"
                >
                    <i class="fa fa-plus me-1"></i> Add
                </button>
            </div>
        </div>

        {{-- Entry Options Strip --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 pt-2 border-top">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="prKeepReason" x-model="keepLastReason">
                    <label class="form-check-label small text-muted" for="prKeepReason">
                        Keep last Reason for next item
                    </label>
                </div>
            </div>

            <div class="small text-muted" x-show="draft.stock_id" x-cloak>
                Line value: <strong class="text-dark" x-text="money((parseFloat(draft.qty) || 0) * (parseFloat(draft.cost_price) || 0))"></strong>
                &nbsp;&middot;&nbsp;
                <span class="pr-fast-kbd">Shift+Enter</span> Quick-add
            </div>
        </div>
    </div>

    {{-- Reusable datalist for inline Reason editing in table --}}
    <datalist id="pr-reasons-datalist">
        <option value="Expired"></option>
        <option value="Damaged"></option>
        <option value="Overstocked"></option>
        <option value="Wrong Item Delivered"></option>
        <option value="Poor Quality"></option>
        <option value="Defective"></option>
        <option value="Recalled by Manufacturer"></option>
        <option value="Returned by Customer"></option>
        <option value="Supplier Request"></option>
        <option value="Other"></option>
    </datalist>

    {{-- ITEMS TABLE TOOLBAR --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0">
                Purchase Return Items
                <span class="badge bg-secondary ms-1" x-text="items.length"></span>
            </h5>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2" x-show="items.length > 0" x-cloak>
            {{-- 1-click Bulk Reason filler --}}
            <div class="input-group input-group-sm" style="width: auto;" x-show="emptyReasonCount > 0">
                <span class="input-group-text bg-light small">Fill empty reason:</span>
                <input
                    type="text"
                    list="pr-reasons-datalist"
                    class="form-control"
                    style="width: 150px;"
                    x-model="bulkReason"
                    placeholder="Reason..."
                />
                <button
                    type="button"
                    class="btn btn-outline-primary"
                    x-on:click="applyBulkReason()"
                >
                    Apply (<span x-text="emptyReasonCount"></span>)
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
        <table class="table table-hover table-bordered mb-0 pr-fast-table">
            <thead>
            <tr>
                <th style="width: 34px;" class="text-center">#</th>
                <th style="min-width: 210px;">Product Name</th>
                <th class="text-center" style="width: 90px;">Qty</th>
                <th class="text-end" style="width: 115px;">Cost Price</th>
                <th class="text-center" style="width: 150px;">Expiry Date</th>
                <th class="text-center" style="width: 125px;">Batch Number</th>
                <th class="text-start" style="min-width: 160px;">Reason</th>
                <th class="text-end" style="width: 115px;">Total</th>
                <th class="text-center" style="width: 48px;"></th>
            </tr>
            </thead>
            <tbody>
            <template x-if="items.length === 0">
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="fa fa-undo fa-2x mb-2 d-block opacity-50"></i>
                        <div class="fw-semibold">No return items added yet</div>
                        <div class="small mt-1">
                            Select a Supplier above, then scan a barcode or press <span class="pr-fast-kbd">F2</span> to search stock — or select a <strong>Linked PO</strong> and click <strong>Load PO Items</strong>.
                        </div>
                    </td>
                </tr>
            </template>

            <template x-for="(item, index) in filteredItems" :key="item.stock_id">
                <tr
                    :id="'pr-fast-row-' + item.stock_id"
                    :class="{
                        'table-warning': item.dept_qty !== null && item.dept_qty !== undefined && parseInt(item.qty, 10) > parseInt(item.dept_qty, 10),
                        'pr-fast-row-flash': item.stock_id === highlightedStockId
                    }"
                >
                    <td class="text-center text-muted" x-text="index + 1"></td>

                    <td>
                        <div class="fw-medium text-dark" x-text="item.name"></div>
                        <div
                            x-show="item.dept_qty !== null && item.dept_qty !== undefined && parseInt(item.qty, 10) > parseInt(item.dept_qty, 10)"
                            class="text-danger"
                            style="font-size: 10.5px;"
                        >
                            <i class="fa fa-exclamation-triangle"></i> Only <span x-text="item.dept_qty"></span> available in department
                        </div>
                    </td>

                    <td>
                        <input
                            type="number"
                            min="1"
                            :id="'pr-fast-qty-' + item.stock_id"
                            x-model="item.qty"
                            x-on:input="recalcItem(item)"
                            class="form-control pr-fast-cell-input text-end"
                            :class="{'pr-fast-cell-invalid': showValidation && !(parseInt(item.qty, 10) > 0)}"
                        />
                    </td>

                    <td>
                        <input
                            type="number"
                            step="any"
                            min="0"
                            x-model="item.cost_price"
                            x-on:input="recalcItem(item)"
                            class="form-control pr-fast-cell-input text-end"
                        />
                    </td>

                    <td>
                        <input
                            type="date"
                            :id="'pr-fast-exp-' + item.stock_id"
                            x-model="item.expiry_date"
                            x-on:change="persistBackup()"
                            x-on:keydown.enter.prevent="focusBatchFor(item.stock_id)"
                            class="form-control pr-fast-cell-input"
                            style="min-width: 138px;"
                        />
                    </td>

                    <td>
                        <input
                            type="text"
                            :id="'pr-fast-batch-' + item.stock_id"
                            x-model="item.batch_no"
                            x-on:input="persistBackup()"
                            placeholder="Batch #"
                            class="form-control pr-fast-cell-input text-center"
                        />
                    </td>

                    <td>
                        <input
                            type="text"
                            list="pr-reasons-datalist"
                            x-model="item.reason"
                            x-on:input="persistBackup()"
                            placeholder="Reason..."
                            class="form-control pr-fast-cell-input"
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
    <div class="pr-fast-sticky-bar">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="pr-fast-stat-pill">
                    <span class="text-muted">Items:</span>
                    <strong x-text="items.length"></strong>
                </div>
                <div class="pr-fast-stat-pill">
                    <span class="text-muted">Total Units:</span>
                    <strong x-text="totalUnits"></strong>
                </div>
                <div class="pr-fast-stat-pill bg-primary-subtle border-primary-subtle">
                    <span class="text-primary">Total Return Value:</span>
                    <strong class="text-primary fs-6" x-text="money(netTotalRaw)"></strong>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                @if($canSave)
                    <button
                        type="button"
                        class="btn btn-primary"
                        :disabled="submitting"
                        x-on:click="submitReturn('draft')"
                        title="Save as Draft Return (Ctrl+S)"
                    >
                        <span x-show="submittingAction === 'draft'" x-cloak class="spinner-border spinner-border-sm me-1"></span>
                        <i x-show="submittingAction !== 'draft'" class="fa fa-save me-1"></i>
                        {{ $purchaseReturn->exists ? 'Update Draft' : 'Draft Return' }}
                    </button>
                @endif

                @if($canComplete)
                    <button
                        type="button"
                        class="btn btn-success"
                        :disabled="submitting"
                        x-on:click="submitReturn('complete')"
                        title="Complete Return — deduct stock & reduce supplier balance (Ctrl+Enter)"
                    >
                        <span x-show="submittingAction === 'complete'" x-cloak class="spinner-border spinner-border-sm me-1"></span>
                        <i x-show="submittingAction !== 'complete'" class="fa fa-check-circle me-1"></i>
                        Complete Return
                    </button>
                @endif

                <a href="{{ route('purchasereturn.index') }}" class="btn btn-outline-danger">Cancel</a>
            </div>
        </div>
    </div>

    <script>
        function purchaseReturnFast() {
            const initial = @js($initialData);
            const suppliers = @js($suppliersList);
            const departments = @js($departmentsList);
            const initialPos = @js($initialSupplierPos);
            const isEditMode = Boolean(initial.return_id);
            const backupKey = 'pr_fast_draft_u{{ auth()->id() }}';
            const deptPrefKey = 'pr_fast_last_dept_u{{ auth()->id() }}';

            const PREDEFINED_REASONS = [
                'Expired',
                'Damaged',
                'Overstocked',
                'Wrong Item Delivered',
                'Poor Quality',
                'Defective',
                'Recalled by Manufacturer',
                'Returned by Customer',
                'Supplier Request',
                'Other',
            ];

            return {
                suppliers: suppliers,
                departments: departments,
                isEditMode: isEditMode,
                predefinedReasons: PREDEFINED_REASONS,

                // Header state
                supplier_id: initial.supplier_id || '',
                supplierQuery: '',
                supplierOpen: false,
                supplierIndex: 0,
                department: initial.department || '',
                return_date: initial.date_created || '{{ todaysDate() }}',
                purchase_id: initial.purchase_id || '',
                poQuery: '',
                poOpen: false,
                poIndex: 0,
                supplierPos: initialPos || [],
                loadingPos: false,
                loadingPoItems: false,
                remark: initial.remark || '',
                headerErrors: { supplier_id: false, department: false, return_date: false },

                // Quick-Add search state
                searchQuery: '',
                searchResults: [],
                searchOpen: false,
                searchEmpty: false,
                searchIndex: 0,
                searching: false,
                searchTimer: null,
                searchAbort: null,

                // Reason combobox state
                reasonOpen: false,
                reasonIndex: 0,

                // Quick-Add draft
                draft: {
                    stock_id: '',
                    name: '',
                    dept_qty: 0,
                    allqty: 0,
                    qty: '',
                    cost_price: '',
                    expiry_date: '',
                    batch_no: '',
                    reason: '',
                },

                // Productivity toggles
                keepLastReason: true,
                lastReason: '',

                // Table state
                items: (initial.items || []).map(function (it) {
                    const qty = parseInt(it.qty, 10) || 0;
                    const cost = parseFloat(it.cost_price) || 0;
                    return {
                        stock_id: parseInt(it.stock_id, 10),
                        name: it.name || '',
                        dept_qty: null,
                        qty: qty,
                        cost_price: cost,
                        expiry_date: it.expiry_date || '',
                        batch_no: it.batch_no || '',
                        reason: it.reason || '',
                        total: qty * cost,
                    };
                }),
                tableFilter: '',
                highlightedStockId: null,
                bulkReason: '',

                // Validation & submit state
                showValidation: false,
                submitting: false,
                submittingAction: '',

                // Backup recovery
                hasSavedBackup: false,
                backupSummary: '',

                init() {
                    if (this.supplier_id) {
                        const sup = this.suppliers.find(s => String(s.id) === String(this.supplier_id));
                        if (sup) this.supplierQuery = sup.name;
                    }

                    if (this.purchase_id && this.supplierPos.length > 0) {
                        const po = this.supplierPos.find(p => String(p.id) === String(this.purchase_id));
                        if (po) this.poQuery = this.formatPoLabel(po);
                    }

                    if (!this.department && !this.isEditMode) {
                        const savedDept = localStorage.getItem(deptPrefKey);
                        if (savedDept && this.departments.some(d => d.quantity_column === savedDept)) {
                            this.department = savedDept;
                        } else if (this.departments.length > 0) {
                            this.department = this.departments[0].quantity_column;
                        }
                    }

                    if (!this.isEditMode) {
                        this.checkBackup();
                    }

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
                    return this.suppliers.filter(s => s.name.toLowerCase().includes(q)).slice(0, 40);
                },

                get filteredPos() {
                    const q = (this.poQuery || '').trim().toLowerCase().replace(/^#/, '');
                    if (!q) return this.supplierPos.slice(0, 30);
                    return this.supplierPos.filter(po =>
                        String(po.id).includes(q) ||
                        ('po ' + po.id).includes(q) ||
                        (po.date_created || '').toLowerCase().includes(q)
                    ).slice(0, 30);
                },

                get filteredReasons() {
                    const q = (this.draft.reason || '').trim().toLowerCase();
                    if (!q) return this.predefinedReasons;
                    return this.predefinedReasons.filter(r => r.toLowerCase().includes(q));
                },

                get filteredItems() {
                    const q = (this.tableFilter || '').trim().toLowerCase();
                    if (!q) return this.items;
                    return this.items.filter(i =>
                        (i.name || '').toLowerCase().includes(q) ||
                        (i.batch_no || '').toLowerCase().includes(q) ||
                        (i.reason || '').toLowerCase().includes(q)
                    );
                },

                get netTotalRaw() {
                    return this.items.reduce((sum, item) => sum + ((parseFloat(item.qty) || 0) * (parseFloat(item.cost_price) || 0)), 0);
                },

                get totalUnits() {
                    return this.items.reduce((sum, item) => sum + (parseInt(item.qty, 10) || 0), 0);
                },

                get emptyReasonCount() {
                    return this.items.filter(i => !(i.reason || '').trim()).length;
                },

                /* ── Global Keyboard Shortcuts ────────────────────── */
                handleGlobalKeydown(e) {
                    if (e.key === 'F2') {
                        e.preventDefault();
                        if (this.$refs.productSearch) {
                            this.$refs.productSearch.focus();
                            this.$refs.productSearch.select();
                        }
                        return;
                    }

                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                        e.preventDefault();
                        if (!this.submitting) {
                            this.submitReturn('draft');
                        }
                        return;
                    }

                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        @if($canComplete)
                        e.preventDefault();
                        if (!this.submitting) {
                            this.submitReturn('complete');
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
                    const changed = String(this.supplier_id) !== String(sup.id);
                    this.supplier_id = String(sup.id);
                    this.supplierQuery = sup.name;
                    this.supplierOpen = false;
                    this.headerErrors.supplier_id = false;

                    if (changed) {
                        this.purchase_id = '';
                        this.poQuery = '';
                        this.supplierPos = [];
                        this.fetchSupplierPos(this.supplier_id);
                    }

                    this.persistBackup();

                    this.$nextTick(() => {
                        if (this.$refs.productSearch) this.$refs.productSearch.focus();
                    });
                },

                clearSupplier(refocus) {
                    this.supplier_id = '';
                    this.supplierQuery = '';
                    this.purchase_id = '';
                    this.poQuery = '';
                    this.supplierPos = [];
                    this.poOpen = false;
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

                /* ── Linked PO Combobox & Loader ──────────────────── */
                formatPoLabel(po) {
                    return 'PO #' + po.id + ' — ' + po.date_created + ' (' + this.money(po.total) + ')';
                },

                async fetchSupplierPos(supplierId) {
                    if (!supplierId) return;
                    this.loadingPos = true;
                    try {
                        const url = '{{ route("purchasereturn.api.supplier_pos") }}' + '?supplier_id=' + encodeURIComponent(supplierId);
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error('Failed');
                        this.supplierPos = await res.json();
                    } catch (e) {
                        this.supplierPos = [];
                    } finally {
                        this.loadingPos = false;
                    }
                },

                openPoList() {
                    if (!this.supplier_id) return;
                    this.poOpen = true;
                    this.poIndex = 0;
                    if (this.supplierPos.length === 0 && !this.loadingPos) {
                        this.fetchSupplierPos(this.supplier_id);
                    }
                },

                movePo(delta) {
                    const list = this.filteredPos;
                    if (list.length === 0) return;
                    this.poOpen = true;
                    this.poIndex = (this.poIndex + delta + list.length) % list.length;
                },

                pickActivePo() {
                    const list = this.filteredPos;
                    if (list.length === 0) return;
                    const chosen = list[this.poIndex] || list[0];
                    if (chosen) this.selectPo(chosen);
                },

                selectPo(po) {
                    this.purchase_id = String(po.id);
                    this.poQuery = this.formatPoLabel(po);
                    this.poOpen = false;
                    this.persistBackup();
                },

                clearPo() {
                    this.purchase_id = '';
                    this.poQuery = '';
                    this.persistBackup();
                },

                async loadItemsFromLinkedPo() {
                    if (!this.purchase_id) return;
                    this.loadingPoItems = true;
                    try {
                        const url = '{{ url("/purchase-returns/api/load-po") }}/' + encodeURIComponent(this.purchase_id)
                            + '?column=' + encodeURIComponent(this.department || 'quantity');
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error('Failed');
                        const list = await res.json();

                        let added = 0;
                        list.forEach(prod => {
                            if (this.isInList(prod.id)) return;
                            const qty = prod.po_qty > 0 ? prod.po_qty : 1;
                            const cost = parseFloat(prod.cost_price) || 0;
                            this.items.push({
                                stock_id: parseInt(prod.id, 10),
                                name: prod.name,
                                dept_qty: prod.dept_qty,
                                qty: qty,
                                cost_price: cost,
                                expiry_date: prod.expiry_date || '',
                                batch_no: prod.batch_no || '',
                                reason: this.keepLastReason ? this.lastReason : '',
                                total: qty * cost,
                            });
                            added++;
                        });

                        this.persistBackup();
                        this.toast('success', added > 0
                            ? 'Loaded ' + added + ' item(s) from PO #' + this.purchase_id + '.'
                            : 'All items from PO #' + this.purchase_id + ' are already in the return list.');
                    } catch (e) {
                        this.toast('error', 'Could not load items from the selected Purchase Order.');
                    } finally {
                        this.loadingPoItems = false;
                    }
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
                        const url = '{{ route("purchasereturn.api.search") }}' + '?q=' + encodeURIComponent(query) + '&column=' + encodeURIComponent(col);
                        const res = await fetch(url, {
                            headers: { 'Accept': 'application/json' },
                            signal: this.searchAbort.signal
                        });
                        if (!res.ok) throw new Error('Search failed');
                        const data = await res.json();

                        this.searchResults = Array.isArray(data) ? data : [];
                        this.searchIndex = 0;
                        this.searchEmpty = this.searchResults.length === 0;

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

                    const existing = this.items.find(i => parseInt(i.stock_id, 10) === parseInt(prod.id, 10));
                    if (existing) {
                        if (prod.exact_barcode) {
                            existing.qty = (parseInt(existing.qty, 10) || 0) + 1;
                            this.recalcItem(existing);
                            this.toast('info', existing.name + ' quantity incremented to ' + existing.qty);
                        } else {
                            this.toast('info', existing.name + ' is already in the return list — focused its row.');
                        }
                        this.flashRow(existing.stock_id);
                        this.resetDraft(false);
                        this.$nextTick(() => {
                            const qtyEl = document.getElementById('pr-fast-qty-' + existing.stock_id);
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
                        dept_qty: prod.dept_qty ?? 0,
                        allqty: prod.allqty ?? 0,
                        qty: 1,
                        cost_price: prod.cost_price ?? 0,
                        expiry_date: prod.expiry_date || '',
                        batch_no: prod.batch_no || '',
                        reason: this.keepLastReason ? this.lastReason : '',
                    };

                    this.$nextTick(() => {
                        if (this.$refs.entryQty) {
                            this.$refs.entryQty.focus();
                            this.$refs.entryQty.select();
                        }
                    });
                },

                /* ── Keyboard Pipeline in Quick-Add Bar ───────────── */
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
                    } else if (field === 'batch') {
                        this.$refs.entryReason.focus();
                        this.$refs.entryReason.select();
                    }
                },

                moveReason(delta) {
                    const list = this.filteredReasons;
                    if (list.length === 0) return;
                    this.reasonOpen = true;
                    this.reasonIndex = (this.reasonIndex + delta + list.length) % list.length;
                },

                selectReason(reason) {
                    this.draft.reason = reason;
                    this.reasonOpen = false;
                    this.addCurrentItem();
                },

                onReasonEnter() {
                    if (this.reasonOpen && this.filteredReasons.length > 0 && !(this.draft.reason || '').trim()) {
                        this.draft.reason = this.filteredReasons[this.reasonIndex] || this.filteredReasons[0];
                    }
                    this.reasonOpen = false;
                    this.addCurrentItem();
                },

                /* ── Smart Expiry Date Parser ─────────────────────── */
                parseSmartDate(raw) {
                    const s = (raw || '').trim();
                    if (!s) return '';

                    let m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
                    if (m) {
                        const y = parseInt(m[1], 10), mo = parseInt(m[2], 10), d = parseInt(m[3], 10);
                        if (mo >= 1 && mo <= 12 && d >= 1 && d <= 31) {
                            return `${y}-${String(mo).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                        }
                    }

                    m = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
                    if (m) {
                        const d = parseInt(m[1], 10), mo = parseInt(m[2], 10), y = parseInt(m[3], 10);
                        if (mo >= 1 && mo <= 12 && d >= 1 && d <= 31) {
                            return `${y}-${String(mo).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                        }
                    }

                    m = s.match(/^(\d{1,2})[\/\-](\d{2})$/);
                    if (m) {
                        const mo = parseInt(m[1], 10), y = 2000 + parseInt(m[2], 10);
                        if (mo >= 1 && mo <= 12) return this.endOfMonth(y, mo);
                    }

                    m = s.match(/^(\d{2})(\d{2})$/);
                    if (m) {
                        const mo = parseInt(m[1], 10), y = 2000 + parseInt(m[2], 10);
                        if (mo >= 1 && mo <= 12) return this.endOfMonth(y, mo);
                    }

                    m = s.match(/^(\d{1,2})[\/\-](\d{4})$/);
                    if (m) {
                        const mo = parseInt(m[1], 10), y = parseInt(m[2], 10);
                        if (mo >= 1 && mo <= 12) return this.endOfMonth(y, mo);
                    }

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

                openNativeDatePicker(el) {
                    if (!el) return;
                    if (typeof el.showPicker === 'function') {
                        el.showPicker();
                    } else {
                        el.click();
                    }
                },

                focusBatchFor(stockId) {
                    const el = document.getElementById('pr-fast-batch-' + stockId);
                    if (el) { el.focus(); el.select(); }
                },

                /* ── Add / Edit / Delete Items ────────────────────── */
                addCurrentItem() {
                    if (!this.draft.stock_id) {
                        this.toast('warning', 'Search and select a stock product first.');
                        if (this.$refs.productSearch) this.$refs.productSearch.focus();
                        return;
                    }

                    const qty = parseInt(this.draft.qty, 10);
                    if (!(qty > 0)) {
                        this.toast('warning', 'Quantity to return must be greater than 0.');
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
                    const reasonVal = (this.draft.reason || '').trim();
                    if (reasonVal) this.lastReason = reasonVal;

                    const newItem = {
                        stock_id: parseInt(this.draft.stock_id, 10),
                        name: this.draft.name,
                        dept_qty: this.draft.dept_qty,
                        qty: qty,
                        cost_price: cost,
                        expiry_date: (this.draft.expiry_date || '').trim(),
                        batch_no: (this.draft.batch_no || '').trim(),
                        reason: reasonVal,
                        total: qty * cost,
                    };

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
                    this.reasonOpen = false;
                    this.draft = {
                        stock_id: '',
                        name: '',
                        dept_qty: 0,
                        allqty: 0,
                        qty: '',
                        cost_price: '',
                        expiry_date: '',
                        batch_no: '',
                        reason: '',
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
                    if (!confirm('Remove all ' + this.items.length + ' item(s) from this return list?')) return;
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

                applyBulkReason() {
                    const val = (this.bulkReason || '').trim();
                    if (!val) {
                        this.toast('warning', 'Enter or select a reason first.');
                        return;
                    }
                    let updated = 0;
                    this.items.forEach(item => {
                        if (!(item.reason || '').trim()) {
                            item.reason = val;
                            updated++;
                        }
                    });
                    this.lastReason = val;
                    this.persistBackup();
                    this.toast('success', 'Applied reason to ' + updated + ' row(s).');
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
                            return_date: this.return_date,
                            purchase_id: this.purchase_id,
                            poQuery: this.poQuery,
                            remark: this.remark,
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
                            this.fetchSupplierPos(this.supplier_id);
                        }
                        if (data.department) this.department = data.department;
                        if (data.return_date) this.return_date = data.return_date;
                        if (data.purchase_id) {
                            this.purchase_id = data.purchase_id;
                            this.poQuery = data.poQuery || '';
                        }
                        if (data.remark) this.remark = data.remark;
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
                validateBeforeSubmit() {
                    this.showValidation = true;
                    this.headerErrors = { supplier_id: false, department: false, return_date: false };

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

                    if (!this.return_date) {
                        this.headerErrors.return_date = true;
                        this.toast('error', 'Please select a Return Date.');
                        return false;
                    }

                    if (this.items.length === 0) {
                        this.toast('error', 'Please add at least one item to return.');
                        if (this.$refs.productSearch) this.$refs.productSearch.focus();
                        return false;
                    }

                    for (const item of this.items) {
                        if (!(parseInt(item.qty, 10) > 0)) {
                            this.toast('error', 'Quantity must be greater than 0 for ' + item.name);
                            const el = document.getElementById('pr-fast-qty-' + item.stock_id);
                            if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.focus(); el.select(); }
                            return false;
                        }
                    }

                    return true;
                },

                async submitReturn(action) {
                    if (!this.validateBeforeSubmit()) return;

                    if (action === 'complete') {
                        const supName = this.supplierQuery || 'selected supplier';
                        const msg = 'Complete this Purchase Return for ' + supName + '?\n\n'
                            + 'Items: ' + this.items.length + ' (' + this.totalUnits + ' units)\n'
                            + 'Total Return Value: ' + this.money(this.netTotalRaw) + '\n\n'
                            + 'Stock will be deducted and the supplier balance will be reduced. This cannot be reversed.';
                        if (!confirm(msg)) return;
                    }

                    this.submitting = true;
                    this.submittingAction = action;

                    const payload = {
                        supplier_id: this.supplier_id,
                        department: this.department,
                        date_created: this.return_date,
                        purchase_id: this.purchase_id || '',
                        remark: this.remark || '',
                        items: this.items.map(i => ({
                            stock_id: i.stock_id,
                            qty: parseInt(i.qty, 10) || 0,
                            cost_price: parseFloat(i.cost_price) || 0,
                            expiry_date: i.expiry_date || '',
                            batch_no: i.batch_no || '',
                            reason: i.reason || '',
                        })),
                    };

                    try {
                        let result;
                        if (action === 'draft') {
                            result = await @this.draftReturn(payload);
                        } else if (action === 'complete') {
                            result = await @this.completeReturn(payload);
                        }

                        if (result && result.status) {
                            localStorage.removeItem(backupKey);
                            setTimeout(() => {
                                window.location.href = result.redirect || '{{ route("purchasereturn.index") }}';
                            }, 900);
                        } else {
                            this.toast('error', (result && result.message) ? result.message : 'Unable to save purchase return.');
                            this.submitting = false;
                            this.submittingAction = '';
                        }
                    } catch (err) {
                        this.toast('error', 'Server error while saving purchase return. Your items are preserved.');
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
