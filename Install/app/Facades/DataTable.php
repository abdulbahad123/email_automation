<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Services\DataTableService;

class DataTable extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'datatable';
    }

    /**
     * Factory method to create a new instance of DataTableService.
     *
     * @param string|object $table
     * @param array         $columns
     * @param array         $whereConditions
     * @param array         $joins
     * @return \App\Services\DataTableService
     */
    public static function make($table, $columns, $whereConditions = [], $joins = [])
    {
        if (func_num_args() >= 5) {
            $args = func_get_args();
            return new DataTableService($args[0], $args[1], $args[3] ?? [], $args[4] ?? []);
        }
        return new DataTableService($table, $columns, $whereConditions, $joins);
    }
}
