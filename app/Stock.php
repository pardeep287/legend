<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'stock_master';

    protected $fillable = [
        'type',
        'type_id',
        'type_item_id',
        'product_id',
        'stock_in',
        'stock_out',
        'stock_date'
    ];

    public $timestamps = false;

    public function store($inputs, $id = null)
    {
        if($id) {
            $this->find($id)->update($inputs);
        }
        else {
            return $this->create($inputs)->id;
        }
    }

    public function delete($search = []) {
        $filter = 1;
        if(is_array($search) && count($search) > 0) {
            $f1 = (isset($search['type']) && $search['type'] != '') ?
                " and type='" . $search['type'] . "'": '';

            $f2 = (isset($search['type_id']) && $search['type_id'] != '') ?
                " and type_id='" . $search['type_id'] . "'": '';

            $f3 = (isset($search['type_item_id']) && $search['type_item_id'] != '') ?
                " and type_item_id='" . $search['type_item_id'] . "'": '';

            $f4 = (isset($search['product_id']) && $search['product_id'] != '') ?
                " and product_id='" . $search['product_id'] . "'": '';

            $filter .= $f1 . $f2 . $f3 . $f4;
        }

        $this->whereRaw($filter)->forceDelete();
    }

    /**
     * @param array $search
     * @return mixed
     */
    public function itemGroupReport($search = [])
    {
        $fields = [
            'stock_master.type',
            'product_group.id as group_id',
            'product_group.name as group_name',
            \DB::raw('count(*) as total'),
            \DB::raw('IFNULL(sum(stock_in), 0) as stock_in'),
            \DB::raw('IFNULL(sum(stock_out), 0) as stock_out'),
        ];

        $filter = "1 and product_group.company_id = '" . loggedInCompanyId() . "'";

        if(is_array($search) && count($search) > 0) {
            $f1 = (isset($search['product_group']) && $search['product_group'] != '') ?
                " and product_group.id = '" . $search['product_group'] ."'" : '';

            $filter .= $f1;
        }

        $result = $this
            ->leftJoin('product', 'product.id', '=', 'stock_master.product_id')
            ->leftJoin('product_group', 'product_group.id', '=', 'product.product_group_id')
            ->whereRaw($filter)
            ->groupBy('product_group.id')
            ->get($fields);

        return $result;
    }

    public function itemWiseReport($search = []) {
        $fields = [
            'stock_master.type',
            'product.product_name',
            'hsn_master.hsn_code',
            'product_group.name as group_name',
            \DB::raw('IFNULL(sum(stock_in), 0) as stock_in'),
            \DB::raw('IFNULL(sum(stock_out), 0) as stock_out'),
        ];

        $filter = "1  and product.company_id = '" . loggedInCompanyId() . "'";

        if(is_array($search) && count($search) > 0) {
            $f1 = (isset($search['product_group']) && $search['product_group'] != '') ?
                " and product.product_group_id = '" . $search['product_group'] ."'" : '';

            $f2 = (isset($search['hsn_code']) && $search['hsn_code'] != '') ?
                " and product.hsn_id = '" . $search['hsn_code'] ."'" : '';

            $filter .= $f1 . $f2;
        }

        $result = $this
            ->leftJoin('product', 'product.id', '=', 'stock_master.product_id')
            ->leftJoin('hsn_master', 'hsn_master.id', '=', 'product.hsn_id')
            ->leftJoin('product_group', 'product_group.id', '=', 'product.product_group_id')
            ->whereRaw($filter)
            ->groupBy('product.id')
            ->get($fields);

        return $result;
    }
}