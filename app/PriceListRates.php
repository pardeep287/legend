<?php
namespace App;
/**
 * :: Price List Rates Model ::
 * To manage Price List Rates CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;

class PriceListRates extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'price_list_rates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'price_list_id',
        'product_id',
        'size_id',
        'rate',
        'wef',
        'wet',
        'is_active'
    ];

    public $timestamps = false;

    /**
     * Scope a query to only include active users.
     *
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
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
            unset($input['rate']);
            unset($input['wef']);
            return $this->find($id)->update($input);
        } else {
            return $this->insert($input);
        }
    }

    /**
     * Method is used to search news detail.
     *
     * @param array $search
     *
     * @param bool $active
     * @return mixed
     */
    public function getEffectedPriceListRate($active = true, $search = [])
    {
        $filter = 1;
        if (is_array($search) && count($search) > 0) {
            $product = (array_key_exists('product_id', $search)) ? " AND size_id = '" .
                addslashes(trim($search['product_id'])) . "'" : "";
            $filter .= $product;

            $product = (array_key_exists('size_id', $search)) ? " AND size_id = '" .
                addslashes(trim($search['size_id'])) . "'" : "";
            $filter .= $product;
        }

        if ($active) {
            $active = " AND is_active = 1";
            $filter .= $active;
        }

        return $this->whereRaw($filter)->first();
    }

    /**
     * @param $id
     * @param $field
     * @return mixed
     */
    public function getPriceListRates($id, $field = false)
    {
        $rates = [];
        if ($id > 0) {
            if ($field) {
                $fields = ['id'];
            } else {
                $fields = ['rate', 'product_id', 'size_id'];
            }

            $result = $this->active()->where('price_list_id', $id)->get($fields);
            if ($field) {
                foreach ($result as $detail) {
                    $rates[] = $detail->id;
                }
            } else {
                foreach ($result as $detail) {
                    $rates[$detail->product_id][$detail->size_id] = $detail->rate;
                }
            }
        }
        return $rates;
    }

    /**
     * @param array $search
     * @return mixed
     */
    public function updatePriceListIds($search = [])
    {
        if (count($search) > 0) {
            $filter = 1;
            if (is_array($search) && count($search) > 0) {
                $f1 = (array_key_exists('product_id', $search)) ? " AND product_id = '" .
                    addslashes(trim($search['product_id'])) . "'" : "";
                $filter .= $f1;

                $f2 = (array_key_exists('size_id', $search)) ? " AND size_id = '" .
                    addslashes(trim($search['size_id'])) . "'" : "";
                $filter .= $f2;

                $f2 = (array_key_exists('id', $search)) ? " AND price_list_id    = '" .
                    addslashes(trim($search['id'])) . "'" : "";
                $filter .= $f2;
            }
        }
        $result = $this->active()->whereRaw($filter)->first(['id']);
        if ($result) {
            $data = [
                'is_active' => 0,
                'wet' => convertToUtc(),
            ];
            $result->update($data);
        }
    }

    /**
     * @param array $search
     * @return bool
     */
    public function getActivePriceRate($search = [])
    {
        $filter = 1;
        if (count($search) > 0) {
            if (is_array($search) && count($search) > 0) {
                $f1 = (array_key_exists('product_id', $search)) ? " AND product_id = '" .
                    addslashes(trim($search['product_id'])) . "'" : "";
                $filter .= $f1;

                $f2 = (array_key_exists('size_id', $search)) ? " AND size_id = '" .
                    addslashes(trim($search['size_id'])) . "'" : "";
                $filter .= $f2;

                $f2 = (array_key_exists('price_list_id', $search)) ? " AND price_list_id = '" .
                    addslashes(trim($search['price_list_id'])) . "'" : "";
                $filter .= $f2;
            }
            return $this->active()->whereRaw($filter)->first(['rate']);
        }
        return false;
    }
}
