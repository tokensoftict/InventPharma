<?php
namespace App\Traits;

use App\Classes\Settings;
use Illuminate\Database\Eloquent\Model;

trait SimpleComponentTrait
{

    public $model;

    protected $cacheModel = null;

    public array $data;

    public array $formData = [];

    public String $modalTitle = "New";

    public String $saveButton = "Save";

    public String $modalName = "";

    public $modelId;

    public array $newValidateRules;

    public array $editcallback;

    public array $updateValidateRules;


    public function boot()
    {
        $this->listeners = [
            'editData' => 'edit',
            'toggleData' => 'toggle',
            'destoryData' => 'destroy',
        ];

    }



    public function initControls()
    {
        $validateRules = [];
        $updateValidationRules = [];
        foreach ($this->data as $key => $value) {

            if($value['type'] == "hidden"){
                $this->formData[$key] = $value['value'];
            }else {
                $this->formData[$key] = "";
            }

            if(isset($this->newValidateRules[$key])) {
                $validateRules['formData.' . $key] = $this->newValidateRules[$key];
            }

            if(isset($this->updateValidateRules[$key])) {
                $updateValidationRules['formData.' . $key] = $this->updateValidateRules[$key];
            }
        }

        $this->newValidateRules = $validateRules;
        $this->updateValidateRules = $updateValidationRules;
    }

    public function save()
    {
        $this->validate($this->newValidateRules);

        $model = new $this->model();
        $model = $this->parseData($model);
        $model->save();
        $this->dispatch('refreshData',[]);
        $this->dispatch("closeModal", []);
    }


    protected function parseData($model)
    {
        foreach($this->data as $key=>$value)
        {
            if($key === "password") {
                if(!empty($this->formData[$key])){
                    $model->{$key} = bcrypt($this->formData[$key]);
                    continue;
                }else {
                    continue;
                }
            }
            $model->{$key} = $this->formData[$key] === "" ? NULL : $this->formData[$key];
        }
        return $model;
    }

    public function update($id)
    {
        $this->modelId = $id;

        $this->validate($this->updateValidateRules);

        $model = $this->model::find($id);
        $model = $this->parseData($model);
        $model->save();

        $this->dispatch('refreshData',[]);
        $this->dispatch(":refresh");
        $this->dispatch("closeModal", []);
    }



    public function get()
    {
       return $this->cacheModel === null ? $this->model::paginate(Settings::$pagination) : call_user_func($this->cacheModel);
    }

    protected function loadEdit($id)
    {
        return $this->get()->filter(function($item) use($id){
            return $item->id === $id;
        })->first();
    }

    public function edit($id)
    {
        $this->modelId = $id;

        $data = $this->loadEdit($id);

        //$data = $this->model::find($id);

        foreach($this->data as $key=>$value)
        {
           if(empty($data->{$key})){
               $this->formData[$key] = "";
           }else {
               $this->formData[$key] = $data->{$key};
           }
        }

        if(isset($this->formData['password'])){
            $this->formData['password']= "";
        }


        $this->modalTitle = "Update";

        $this->saveButton = "Update";

        if(isset($this->editcallback) && count($this->editcallback) > 0)
        {
            foreach ($this->editcallback as $callback)
            {
                $this->$callback();
            }

        }


        $this->dispatch('refreshData',[]);
        $this->dispatch("openModal", []);
    }


    public function new()
    {
        foreach($this->data as $key=>$value)
        {
            $this->formData[$key] = "";
        }

        $this->modalTitle = "New";

        $this->saveButton = "Save";

        $this->dispatch("openModal", []);
    }

    public function toggle($id)
    {
        $this->modelId = $id;
        $model = $this->model::find($id);
        $model->status = !$model->status;
        $model->save();
        $this->dispatch('refreshData',[]);
    }

    public function destroy($id)
    {
        $this->modelId = $id;
        $this->model::find($id)->delete();
        $this->dispatch('refreshData',[]);
    }



}
