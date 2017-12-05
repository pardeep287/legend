<?php
namespace App;
/**
 * :: Price List Model ::
 * To manage Price List CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceList extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'price_list';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'company_id',
        'discount_applicable',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Scope a query to only include active users.
     *
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Method is used to validate roles
     *
     * @param $inputs
     * @param int $id
     * @return Response
     */
    public function validatePriceList($inputs, $id = null)
    {
        // validation rule
        if ($id) {
            $rules['name'] = 'required|unique:price_list,name,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
            $rules['code'] = 'required|unique:price_list,code,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        } else {
            $rules['name'] = 'required|unique:price_list,name,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
            $rules['code'] = 'required|unique:price_list,code,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        }
        return \Validator::make($inputs, $rules);
    }

    /**
     * Method is used to save/update resource.
     *
     * @param   array $input
     * @param   int $id
     * @return  Response
     */
    public function store($input, $id = null)
    {
        if ($id) {
            $this->find($id)->update($input);
            return $id;
        } else {
            return $this->create($input)->id;
        }
    }

    /**
     * Method is used to search news detail.
     *
     * @param array $search
     * @param int $skip
     * @param int $perPage
     *
     * @return mixed
     */
    public function getPriceLists($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'price_list.id',
            'price_list.name',
            'price_list.code',
            'price_list.status',
        ];

        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('keyword', $search)) ? " AND price_list.name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $f1;
        }
        return $this->whereRaw($filter)
            ->orderBy('id', 'ASC')
            ->skip($skip)->take($take)->get($fields);
    }

    /**
     * Method is used to get total results.
     *
     * @param array $search
     *
     * @return mixed
     */
    public function totalPriceLists($search = null)
    {
        $filter = 1; // if no search add where

        // when search
        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('keyword', $search)) ? " AND name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $f1;
        }
        return $this->select(\DB::raw('count(*) as total'))
                ->whereRaw($filter)->first();
    }

    /**
     * @return mixed
     */
    public function getPriceListService()
    {
        $result = $this->active()->lists('name', 'id')->toArray();
        return ['' => '-Select Price List-'] + $result;
    }


    /**
     * @param null $id
     * @return mixed
     */
    public function getPriceListProductService($id = null)
    {
        $data = $this->leftJoin('product', 'product.brand_id', '=', 'price_list.brand_id')
            ->where('price_list.id', $id)
            ->where('product.status', 1)
            ->get(['product_name', 'product_code', 'product.id']);
        $result = ['' => '-Select Products-'];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->product_name . ' (' . $detail->product_code  . ')';
        }
        return $result;
    }
    /**
     * @param not null $id
     * @return mixed
     */
    public function findById($id)
    {
        return $this->active()->where('id', $id)
               ->first();
    }
}
