<?php

namespace App\Services;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class DataTableService
{
    protected $table;
    protected $columns;
    protected $orderField;
    protected $orderDirection;
    protected $searchFields;
    protected $whereConditions;
    protected $joins;

    /**
     * Create a new DataTableService instance.
     *
     * @param string|EloquentModel $table           The name of the database table OR an Eloquent model (or model class) instance.
     * @param array|null           $columns         An array defining the columns or null to select all fields. For example:
     *                                              [
     *                                                  ['data' => 'id_secure', 'name' => 'id_secure', 'alias' => 'user_id_secure'],
     *                                                  ['data' => 'name', 'name' => 'name']
     *                                              ]
     *                                              If null or an empty array is passed, all columns from the parent table will be selected.
     * @param array                $searchFields    The fields to use for search filtering.
     * @param array                $whereConditions Default WHERE conditions.
     * @param array                $joins           JOIN conditions if needed.
     */
    public function __construct($table, $datatableConfig, $whereConditions = [], $joins = [])
    {
        $this->table = $table;
        $this->orderField = null;
        $this->orderDirection = null;
        $this->whereConditions = $whereConditions;
        $this->joins = $joins;
        $this->columns = $datatableConfig['columns'] ?? [];
        $this->searchFields = $datatableConfig['search_field'] ?? [];
        $this->defaultOrder = $datatableConfig['order'] ?? [];
        $this->filters = $datatableConfig['filters'] ?? []; // Initializing filters configuration
        $this->status_filter = $datatableConfig['status_filter'] ?? []; // Initializing status filter
    }

    /**
     * Process the DataTable query and return paginated data.
     *
     * @param \Illuminate\Http\Request $request
     * @return array An array including "recordsTotal", "recordsFiltered", and "data"
     */
    public function getData($request)
    {
        try {
            $data = [];
            $start    = (int)$request->input('start', 0);
            $per_page = (int)$request->input('length', 10);
            $current_page = (int) floor($start / $per_page) + 1;
            $order  = $request->input('order');
            $search = $request->input('search');

            // Set the current page for pagination.
            Paginator::currentPageResolver(function () use ($current_page) {
                return $current_page;
            });

            $tableName = $this->resolveTableName();

            // Build the query.
            $query = $this->buildQuery();

            // Apply status_filter dynamically.
            if (!empty($this->status_filter)) {
                if ($request->has('status') && $request->input('status') != -1) {
                    $query->where($tableName . '.status', $request->input('status'));
                }
            }

            // Apply filters dynamically.
            if (!empty($this->filters)) {
                foreach ($this->filters as $filter) {
                    $filterKey = str_replace('datatable_filter[', '', rtrim($filter['name'], ']'));
                    if ($request->has($filterKey) && $request->input($filterKey) != -1) {
                        $colCheck = str_replace("{$tableName}.", "", $filterKey);
                        if (strpos($colCheck, '.') === false) {
                            try {
                                if (!Schema::hasColumn($tableName, $colCheck)) {
                                    continue;
                                }
                            } catch (\Throwable $e) {}
                            $filterKey = "{$tableName}.{$colCheck}";
                        }
                        $query->where($filterKey, (int)$request->input($filterKey));
                    }
                }
            }

            // Apply any default WHERE conditions.
            if (!empty($this->whereConditions)) {
                foreach ($this->whereConditions as $column => $value) {
                    $query->where($column, $value);
                }
            }

            // Add JOIN conditions if specified.
            if (!empty($this->joins)) {
                foreach ($this->joins as $join) {
                    $query->join(
                        $join['table'],
                        $join['first'],
                        '=',
                        $join['second'],
                        $join['type'] ?? 'inner'
                    );
                }
            }

            // Apply search criteria safely.
            if (!empty($search) && !empty($this->searchFields)) {
                $query->where(function ($q) use ($search, $tableName) {
                    foreach ($this->searchFields as $field) {
                        $fieldCol = $field;
                        if (strpos($fieldCol, '.') === false) {
                            try {
                                if (!Schema::hasColumn($tableName, $fieldCol)) {
                                    continue;
                                }
                            } catch (\Throwable $e) {}
                            $fieldCol = "{$tableName}.{$fieldCol}";
                        }
                        $q->orWhere($fieldCol, 'like', '%' . $search . '%');
                    }
                });
            }

            // Apply ordering criteria safely.
            if (!empty($order)) {
                $order_index = $order[0]['column'] ?? 0;
                $this->orderDirection = (isset($order[0]['dir']) && $order[0]['dir'] === "desc") ? "desc" : "asc";
                if (isset($this->columns[$order_index])) {
                    $this->orderField = $this->columns[$order_index]['alias'] ?? ($this->columns[$order_index]['name'] ?? "id");
                } else {
                    $this->orderField = "id";
                }
            } else {
                $this->orderField = $this->defaultOrder[0] ?? "id";
                $this->orderDirection = $this->defaultOrder[1] ?? "desc";
            }

            // Prevent ambiguous column ordering error when tables are joined
            $orderCol = $this->orderField;
            if (is_string($orderCol) && strpos($orderCol, '.') === false) {
                $isAlias = false;
                foreach ($this->columns as $col) {
                    if (isset($col['alias']) && $col['alias'] === $orderCol) {
                        $isAlias = true;
                        break;
                    }
                }
                if (!$isAlias) {
                    $orderCol = "{$tableName}.{$orderCol}";
                }
            }

            $query->orderBy($orderCol, $this->orderDirection);

            // Paginate the query results.
            $pagination = $query->paginate($per_page);

            // Process each returned row according to the defined columns.
            foreach ($pagination as $row) {
                $dataItem = [];
                // If columns were provided, build output per column definition.
                if (!empty($this->columns)) {
                    foreach ($this->columns as $column) {
                        if (isset($column['data'])) {
                            $field = isset($column['alias']) ? $column['alias'] : ($column['name'] ?? $column['data']);
                            if (is_string($field) && strpos($field, '.') !== false) {
                                $field = substr($field, strrpos($field, '.') + 1);
                            }

                            if (isset($column['type']) && isset($row->$field)) {
                                $dataItem[$column['data']] = FormatData($column['type'], $row->$field) ?? null;
                            } else {
                                $dataItem[$column['data']] = $row->$field ?? null;
                            }
                        }
                    }
                } else {
                    // Otherwise, if columns is empty or null, return all fields.
                    $dataItem = (array)$row;
                }
                $data[] = $dataItem;
            }

            return [
                "recordsTotal"    => $pagination->total(),
                "recordsFiltered" => $pagination->total(),
                "data"            => $data
            ];
        } catch (\Throwable $e) {
            \Log::error("DataTableService getData exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                "recordsTotal"    => 0,
                "recordsFiltered" => 0,
                "data"            => []
            ];
        }
    }


    private function buildSelectColumns()
    {
        // Get the correct table name based on the type of $this->table
        $tableName = $this->resolveTableName();

        // If $this->columns is empty, select all fields from the resolved table name
        if (empty($this->columns)) {
            return ["{$tableName}.*"];
        }

        // Otherwise, ensure each column is properly qualified with the table name
        $result = array_map(function ($column) use ($tableName) {
            // Virtual / computed column
            if (!empty($column['virtual'])) {
                $alias = $column['alias'] ?? ($column['data'] ?? 'virtual_col');
                return DB::raw("NULL AS {$alias}");
            }

            if( isset($column['name']) && $column['name'] != ""){
                $name = $column['name'];
                // If the column is not already qualified with a table, prepend the table name
                if (strpos($name, '.') === false && strpos($name, '(') === false) {
                    $name = "{$tableName}.{$name}";
                }

                // If column belongs to $tableName, verify it exists before selecting to avoid 1054 Unknown column
                $colClean = str_replace("{$tableName}.", "", $name);
                if (strpos($colClean, '.') === false && strpos($colClean, '(') === false && !empty($tableName)) {
                    try {
                        if (!Schema::hasColumn($tableName, $colClean)) {
                            $alias = $column['alias'] ?? $colClean;
                            return DB::raw("NULL AS {$alias}");
                        }
                    } catch (\Throwable $e) {}
                }

                // Check if alias exists
                if (isset($column['alias'])) {
                    return DB::raw("{$name} AS {$column['alias']}");
                }

                return $name;
            }

        }, $this->columns);

        // Ensure the table.* is included to avoid missing fields
        $result[] = "{$tableName}.*";

        return array_filter($result);
    }

    private function resolveTableName()
    {
        if (is_string($this->table)) {
            if (class_exists($this->table) && is_subclass_of($this->table, EloquentModel::class)) {
                return (new $this->table)->getTable(); // Trả về tên bảng của Model
            }
            return $this->table; // Trả về tên bảng trực tiếp
        }

        if ($this->table instanceof EloquentModel) {
            return $this->table->getTable(); // Trả về tên bảng từ instance Model
        }

        throw new \Exception("Invalid table reference in resolveTableName()");
    }

    /**
     * Build a query builder instance based on the table parameter.
     *
     * This method checks whether $this->table is:
     *  - A string representing a table name (and then uses DB::table())
     *  - An Eloquent Model instance or a model class (and then uses the model's newQuery())
     *
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    private function buildQuery()
    {
        // Case 1: $this->table is an object and an instance of Eloquent Model.
        if (is_object($this->table) && $this->table instanceof EloquentModel) {
            return $this->table->newQuery()->select($this->buildSelectColumns());
        }

        // Case 2: $this->table is a string that is a valid model class.
        if (is_string($this->table) && class_exists($this->table) && is_subclass_of($this->table, EloquentModel::class)) {
            $modelInstance = new $this->table();
            return $modelInstance->newQuery()->select($this->buildSelectColumns());
        }

        // Default: treat $this->table as a table name.
        return DB::table($this->table)->select($this->buildSelectColumns());
    }
}
