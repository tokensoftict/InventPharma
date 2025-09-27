<div>
   <div class="modal-dialog modal-xl">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Stock(s) In {{ $this->stockgroup->name }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <div class="table-responsive">
            <table class="table table-bordered table-striped">
               <thead>
                  <tr>
                     <th>#</th>
                     <th>Product ID</th>
                     <th>Name</th>
                     <th>Box</th>
                     <th>Carton</th>
                     <th>Category Name</th>
                     <th>Qty To Buy</th>
                     <th>Supplier</th>
                     <th>Threshold Type</th>
                     <th>Stock Quantity</th>
                     <th>Total Sold</th>
                     <th>Last Qty Pur</th>
                     <th>Last Pur. Date</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach($this->nearos as $nearos)
                      <tr>
                         <td>{{ $loop->iteration }}</td>
                         <td>{{ $nearos->stock_id }}</td>
                         <td>{{ $nearos->stock_name }}</td>
                         <td>{{ $nearos->box }}</td>
                         <td>{{ $nearos->carton }}</td>
                         <td>{{ $nearos->category_name }}</td>
                         <td>{{ $nearos->qty_to_buy }}</td>
                         <td>{{ $nearos->supplier_name }}</td>
                         <td>{{ $nearos->threshold_type == "" ? "THRESHOLD" : $nearos->threshold_type }}</td>
                         <td>{{ $nearos->current_qty }}</td>
                         <td>{{ $nearos->current_sold }}</td>
                         <td>{{ $nearos->last_qty_purchased }}</td>
                         <td>{{ $nearos->last_purchase_date == NULL ? "" : $nearos->last_purchase_date->format('d/m/Y') }}</td>
                      </tr>
                  @endforeach
               </tbody>
            </table>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         </div>
      </div>
   </div>
</div>
