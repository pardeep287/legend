<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockMaster extends Model
{
    protected $table = 'stock_master';

    protected $fillable = [
        'id',
        'type',
        'type_id',
        'type_item_id',
        'product_id',
        'stock_in',
        'stock_out',
        'stock_date'
    ];

    public $timestamps = false;

    /**
     * @param $input
     * @param null $id
     *
     * @return mixed
     */
    public function store($input, $id = null)
    {
        if ($id)
        {
            return $this->find($id)->update($input);
        }
        else
        {
            return $this->create($input)->id;
        }
    }
    /**
     * @param array $inputs
     * @param $id
     * @param $type
     */
    public function saveStock($inputs = [], $id, $type)
    {
        foreach ($inputs as $data) {
            $field = ($type == 1) ? 'stock_in' : 'stock_out';
            $insert[] = [
                'type_item_id' => $data->id,
                'product_id' => $data->product_id,
                $field => $data->quantity,
                'stock_date' => convertToUtc(),
                'type' =>  $type,
                'type_id' => $id,
            ];
        }
        if (count($inputs) > 0) {
            $this->insert($insert);
            if ($type == 1) {
                (new SupplierOrder)->store(['is_received' => 1], $id);
            }
        }
    }

    /**
     * @param array $id
     */
    public function deletePermanently($id)
    {
        return $this->find($id)->forceDelete();
    }

    /**
     * @param $typeId
     * @param $type
     * @param null $typeItemId
     * @return mixeds
     */
    public function deleteStock($typeId, $type, $typeItemId = null)
    {
        if($typeItemId) {
            return $this->where('type', $type)->where('type_id', $typeId)->where('type_item_id', $typeItemId)->forceDelete();
        }
        return $this->where('type', $type)->where('type_id', $typeId)->forceDelete();
    }
}