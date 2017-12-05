<?php
namespace App;
/**
 * :: Product Type Model ::
 * To manage Product Type CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBom extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'product_bom';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'bom_number',
        'parent_product_id',
        'parent_size_id',
        'raw_product_id',
        'raw_size_id',
        'quantity',
        'status',

        'created_at',
        'updated_at',
        'deleted_at',

    ];

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('product_bom.status', 1);
    }


    /**
     * Method is used to save/update resource.
     *
     * @param   array $input
     * @param   int $id
     * @return  Response
     */
    public function store($input, $id = null,$isArray=false)
    {
        if ($id) {
            //dd($id, $input);
            return $this->find($id)->update($input);
        }
        else {
            if ($isArray)
            {
                $this->insert($input);
            }
            else {
                return $this->create($input)->id;
            }
        }
    }



    /**
     * @param bool $active
     * @param array $search
     * @return mixed
     */
    public function findByIdProductId($productId = false){

        $fields = [
            'product_bom.id',
            //'product_bom.bom_number',
            'product_bom.parent_product_id',
            //'parent_size_id',
            'product_bom.raw_product_id',
            'product.product_name',
            'product.cost',
            //'raw_size_id',
            'product_bom.quantity',
            //'status',
            'hsn_code',
            'unit.name as unit_name',
            'unit.code as unit_code',
            'tax.name as tax_name',
            'tax.code as tax_code',
        ];

        return $this->active()
            ->leftJoin('product', 'product_bom.raw_product_id', '=', 'product.id')
            ->leftJoin('hsn_master', 'product.hsn_id', '=', 'hsn_master.id')
            ->leftJoin('tax', 'product.tax_id', '=', 'tax.id')
            ->leftJoin('unit', 'product.unit_id', '=', 'unit.id')
            ->where('parent_product_id',$productId )
            ->get($fields);

    }

    /**
     * @param $id
     * @param $sizes
     */
    public function deletedBoms($id, $rawProductid)
    {
        $update = [
            //'deleted_by' => authUserId(),
            'status' => 0,
            'deleted_at' => convertToUtc()
        ];
        $this->where('parent_product_id', $id)
            ->whereIn('raw_product_id', $rawProductid)
            ->update($update);
    }

    /**
     * @param array|int $id
     */
    public function deleteBom($id)
    {
        //$this->where('id', $id)->get()->delete();
        return $this->where('parent_product_id', $id)->forceDelete();
    }

    /**
     * @param bool $active
     * @param array $search
     * @return mixed
     */
    /*public function findByIdProductBomId($productBomId = false){

        $fields = [
            //'product_bom.company_id',
            //'product_bom.bom_number',
            'product_bom.parent_product_id',
            //'parent_size_id',
            'product_bom.raw_product_id',
            'product.product_name',
            'product.cost',
            //'raw_size_id',
            'product_bom.quantity',
            //'status',
            'hsn_code',
            'unit.name as unit_name',
            'unit.code as unit_code',
            'tax.name as tax_name',
            'tax.code as tax_code',
        ];

        return $this->active()
            ->leftJoin('product', 'product_bom.raw_product_id', '=', 'product.id')
            ->leftJoin('hsn_master', 'product.hsn_id', '=', 'hsn_master.id')
            ->leftJoin('tax', 'product.tax_id', '=', 'tax.id')
            ->leftJoin('unit', 'product.unit_id', '=', 'unit.id')
            ->where('parent_product_id',$productId )
            ->get($fields);

    }*/

}
