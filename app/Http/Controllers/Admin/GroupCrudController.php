<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GroupTypeEnum;
use App\Http\Requests\Auth\GroupRequest;
use App\Models\Group;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class GroupCrudController.
 * @property-read CrudPanel $crud
 */
class GroupCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;
    use ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Group::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/group');
        CRUD::setEntityNameStrings('group', 'groups');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name');
        CRUD::column('type');
        CRUD::column('logo');
        CRUD::column('created_at');
        CRUD::column('updated_at');

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']);.
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(GroupRequest::class);

        $this->crud->addFields([
            [
                'name' => 'logo',
                'type' => 'image',
                'label' => __('logo'),
                'crop' => true,
                'aspect_ratio' => 1,
            ],
            [
                'name' => 'type',
                'type' => 'select_from_array',
                'label' => __('grouptype'),
                'options' => [
                    GroupTypeEnum::DEFAULT->value => __(GroupTypeEnum::DEFAULT->value),
                    GroupTypeEnum::SYSTEM->value => __(GroupTypeEnum::SYSTEM->value),
                    GroupTypeEnum::DEPARTMENT->value => __(GroupTypeEnum::DEPARTMENT->value),
                ],
                'allows_null' => false,
                'default' => 'none',
            ],
            [
                'name' => 'internal_name',
                'type' => 'text',
                'allows_null' => false,
                'aspect_ratio' => 1,
            ],
            [
                'name' => 'name',
                'type' => 'text',
                'label' => __('name'),
            ],
            [
                'name' => 'description',
                'type' => 'wysiwyg',
                'label' => __('description'),
            ],
        ]);

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number']));.
         */
    }
}
