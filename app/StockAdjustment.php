<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustment extends Model
{
    use SoftDeletes;

    protected $table = 'stock_adjustment';

    protected $fillable = [
                    'date',
                    'quantity',
                    'type',
                    'product_id',
                    'stock_id',
                    'created_by',
                    'updated_by',
                    'deleted_by'
                ];

    /**
     * @param $inputs
     * @return \Illuminate\Validation\Validator
     */
    public function validateStockAdjustment($inputs)
    {
        $rules = [
            'date' => 'required',
            'product' => 'required',
            'quantity' => 'required|numeric',
            'type' => 'required'
        ];
        return \Validator::make($inputs, $rules);
    }

    /**
     * @param $input
     * @param null $id
     * @return mixed
     */
    public function store($input, $id = null)
    {
        if ($id) {
            return $this->find($id)->update($input);
        } else {
            return $this->create($input)->id;
        }
    }

    /**
     * @param null $search
     * @param $skip
     * @param $perPage
     * @return mixed
     */
    public function getStockAdjustment($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'stock_adjustment.id',
            'stock_adjustment.quantity',
            'stock_adjustment.type',
            'stock_adjustment.date',
            'product.product_name',
            'product.item_code',
            'product.product_code',
            'product.company_id'
        ];

        $sortBy = [
            'product' => 'product.product_name',
            'date' => 'stock_adjustment.date',
        ];

        $orderEntity = 'id';
        $orderAction = 'desc';

        if (isset($search['sort_action']) && $search['sort_action'] != "") {
            $orderAction = ($search['sort_action'] == 1) ? 'desc' : 'asc';
        }

        if (isset($search['sort_entity']) && $search['sort_entity'] != "") {
            $orderEntity = (array_key_exists($search['sort_entity'], $sortBy)) ? $sortBy[$search['sort_entity']] : $orderEntity;
        }

        if (is_array($search) && count($search) > 0) {
            $keyword = (array_key_exists('keyword', $search)) ?
                " AND (product.product_name LIKE '%" .addslashes(trim($search['keyword'])) . "%')"
                : "";

            $filter .= $keyword;
        }

        return $this->leftJoin('product', 'product.id', '=', 'stock_adjustment.product_id')
            ->whereRaw($filter)
            ->where('product.company_id', loggedInCompanyId())
            ->orderBy($orderEntity, $orderAction)
            ->skip($skip)->take($take)->get($fields);
    }

    /**
     * @param null $search
     * @return mixed
     */
    public function totalStockAdjustment($search = null)
    {
        $filter = 1; // if no search add where

        // when search
        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND quantity LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->leftJoin('product', 'product.id', '=', 'stock_adjustment.product_id')
            ->select(\DB::raw('count(*) as total'))
            ->whereRaw($filter)->where('product.company_id', loggedInCompanyId())->first();
    }

    /**
     * @param $id
     * @return int
     */
    public function drop($id)
    {
        return $this->destroy($id);
    }
}
