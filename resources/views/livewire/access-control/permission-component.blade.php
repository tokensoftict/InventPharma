<div>

    <div class="row  border-bottom">
        <div class="col-12">
            <div class="card" >
                <div class="card-header">
                    <h2 class="card-title">Assign Privileges to User Group {{ $usergroup->name }}</h2>
                </div>

            </div>
        </div>

        @foreach($this->modules->chunk(2) as $chunkModule)
            <div class="row">
                @foreach($chunkModule as $module)
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">  {{ $module->label }}</h2>
                            </div>
                            <div class="card-body" style="height: 180px; overflow: auto">
                                <div id="" class="col-12">
                                    <div class="row">
                                        @foreach($module->tasks->chunk(2) as $chunkTask)

                                            @foreach($chunkTask as $task)
                                                <div class="col-md-6">
                                                    <div class="checkbox">
                                                        <label class="i-checks">
                                                            <input  wire:model="privileges.{{ $task->id }}"  value="1" type="checkbox">
                                                            {{ $task->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach

        <button type="button" wire:click="syncPermission" wire:target="syncPermission" wire:loading.attr="disabled" class="btn btn-lg btn-primary btn-block">
            <i wire:loading.remove wire:target="syncPermission" class="fa fa-check"></i>
            <span wire:loading wire:target="syncPermission" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Save Privileges
        </button>

    </div>

</div>
