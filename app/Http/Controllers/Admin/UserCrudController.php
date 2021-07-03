<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\BulkDeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class UserCrudController.
 * @property-read CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;
    use ShowOperation;
    use BulkDeleteOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(User::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/user');
        CRUD::setEntityNameStrings('user', 'users');
    }

    public function setupShowOperation()
    {
        $this->crud->set('show.setFromDb', false);

        // example logic
        $this->crud->addColumns([
            [
                'name' => 'name',
                'label' => __('username'),
                'type' => 'text',
            ],
            [
                'name' => 'email',
                'label' => __('email'),
                'type' => 'email',
            ],
            [
                // n-n relationship (with pivot table)
                'label' => __('roles'), // Table column heading
                'type' => 'select_multiple',
                'name' => 'roles', // the method that defines the relationship in your Model
                'entity' => 'roles', // the method that defines the relationship in your Model
                'attribute' => 'name', // foreign key attribute that is shown to user
                'model' => 'App\Models\Role', // foreign key model
            ],
        ]);
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->addColumn([
            'type' => 'checkbox',
            'name' => 'bulk_actions',
            'label' => ' <input type="checkbox" class="crud_bulk_actions_main_checkbox" style="width: 16px; height: 16px;" />',
            'priority' => 1,
            'searchLogic' => false,
            'orderable' => false,
            'visibleInModal' => false,
        ])->makeFirstColumn();

        $this->crud->addColumn([
            'name' => 'name',
            'label' => __('username'),
            'type' => 'text',
        ]);
        $this->crud->addColumn([
            'name' => 'email',
            'label' => __('email'),
            'type' => 'email',
        ]);
        $this->crud->addColumn([
            'name' => 'roles',
            'label' => __('roles'),
            'model'     => Role::class,
            'attribute' => 'name',
            'type' => 'relationship',
        ]);
        $this->crud->addColumn([
            'name' => 'created_at',
            'label' => __('created at'),
            'type' => 'date',
        ]);
        $this->crud->addFilter(
            [
                'name' => 'role',
                'type' => 'dropdown',
                'label' => __('roles'),
            ],
            Role::all()->pluck('name', 'id')->toArray(),
            function ($value) { // if the filter is active
                $this->crud->addClause('whereHas', 'roles', function ($query) use ($value) {
                    $query->where('role_id', '=', $value);
                });
            }
        );
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
        CRUD::setValidation(UserRequest::class);

        $this->crud->addFields([
            [
                'label' => __('username'),
                'type' => 'text',
                'name' => 'name',
            ],
            [
                'label' => __('email'),
                'type' => 'email',
                'name' => 'email',
            ],
            [
                'label' => __('roles'),
                'type' => 'checklist',
                'name' => 'roles',
                'entity' => 'roles',
                'model' => Role::class, // foreign key model
                'attribute' => 'name', // foreign key attribute that is shown to user
                'pivot' => true,
            ],
        ]);

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number']));.
         */
    }
}
