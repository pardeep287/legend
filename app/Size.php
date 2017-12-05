<?php
namespace App;
/**
 * :: Size Model ::
 * To manage Size CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Size extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'sizes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'size',
        '_order',
        'description',
        'company_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Scope a query to only include active users.
     *
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
    public function validateSize($inputs, $id = null)
    {
        // validation rule
        if ($id) {
            $rules['name'] = 'required|unique:sizes,name,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        } else {
            $rules['name'] = 'required|unique:sizes,name,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        }
        $rules['size'] = 'required|numeric';
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
            return $this->find($id)->update($input);
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
    public function getSizes($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        // default filter if no search
        $filter = 1;

        $fields = [
            'id',
            'name',
            'size',
            'description',
            'status',
        ];

        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('keyword', $search)) ? " AND name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $f1;
        }

        return $this->whereRaw($filter)
            ->orderBy('id', 'ASC')->skip($skip)->take($take)->get($fields);
    }

    /**
     * Method is used to get total results.
     *
     * @param array $search
     *
     * @return mixed
     */
    public function totalSizes($search = null)
    {
        $filter = 1; // if no search add where

        // when search
        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('name', $search)) ? " AND name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->select(\DB::raw('count(*) as total'))
                    ->whereRaw($filter)->first();
    }

    /**
     * @return mixed
     */
    public function getSizeService()
    {
        $data = $this->active()->pluck('name', 'id');
        $result = [];
        foreach($data as $id => $name) {
            $result[$id] = $name;
        }
        return $result;
    }

    public function getNewOrdernumber()
    {
        $orderNumber = 0;
        $result = $this->orderBy('id', 'desc')->take(1)->first(['_order']);
        if(!empty($result->_order))
        {
            $orderNumber = ($result->_order + 1);
        }else{
            $orderNumber = 1;
        }
        return $orderNumber;
    }

    /**
     * @param $productId
     * @param $orderId
     * @param string $isPendingAndApproved
     * @return array
     */
    public function getCustomerProductSizeService($productId, $orderId, $isPendingAndApproved = '1')
    {
        $fields = [
            'sizes.id as size_id',
            'sizes.name'
        ];

        $where = [
            'customer_purchase_order_items.product_id' => addslashes($productId),
            'customer_purchase_order_items.customer_purchase_order_id' => addslashes($orderId)
        ];

        if($isPendingAndApproved == '1') {
            $where['customer_purchase_order_items.is_pending'] = '1';
            $where['customer_purchase_order_items.is_approved'] = '1';
        }

        $data = $this->leftJoin('customer_purchase_order_items', 'sizes.id', '=', 'customer_purchase_order_items.size_id')
            ->where($where)
            ->get($fields);

        $result = [];
        foreach($data as $detail) {
            $result[$detail->size_id] = $detail->name;
        }
        return ['' => '-Select Size-'] + $result;
    }
}
