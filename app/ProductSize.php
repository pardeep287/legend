<?php
namespace App;
/**
 * :: Product Type Model ::
 * To manage Product Type CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSize extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'product_sizes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id',
        'size_id',
        //'company_id',
        //'status',
        'created_by',
        'updated_by',
        'deleted_by',

    ];


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
     * Method is used to search detail.
     *
     * @param array $search
     * @param int $skip
     * @param int $perPage
     *
     * @return mixed
     */
    public function getProductTypes($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        
        $filter = 1;

        $fields = [
            'id',
            'name',
            'description',
            'status',
        ];

        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('name', $search)) ? " AND name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->whereRaw($filter)
            ->orderBy('id', 'ASC')->skip($skip)->take($take)->get($fields);
    }

    /**
     * @param bool $active
     * @param array $search
     * @return mixed
     */
    public function findByIdProductId($productId = false){

        $fields = [
            'product_sizes.id as  product_size_id',
            'product_sizes.size_id as  product_master_size_id',
            'sizes.name',
            'sizes.size',
        ];

        return $this->leftJoin('sizes', 'sizes.id', '=', 'product_sizes.size_id')
            ->where('product_id',$productId )
            ->get($fields);

    }

    /**
     * @param bool $active
     * @param array $search
     * @return mixed
     */
    public function getProductSizeService($productId){
        $fields = [
            'product_sizes.product_id as p_id',
            'product_sizes.size_id as s_id',
            'sizes.name as size_name',
            'sizes.size',
        ];
        return $this->leftJoin('sizes', 'sizes.id', '=', 'product_sizes.size_id')
            ->where('product_id', $productId )
            ->get($fields);
    }



    /**
     * @param $id
     * @param $sizes
     */
    public function deletedSizes($id, $sizes)
    {
        $update = [
            'deleted_by' => authUserId(),
            'deleted_at' => convertToUtc()
        ];
        $this->where('product_id', $id)
            ->whereIn('size_id', $sizes)
            ->update($update);
    }


}
