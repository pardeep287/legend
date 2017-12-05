<?php
namespace App;
/**
 * :: Currency Rates Model ::
 * To manage Currency Rates CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;

class CurrencyRates extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'currency_rates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'currency_id',
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
        return $query->where('status', 1);
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
            return $this->create($input)->id;
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
    public function getEffectedCurrency($active = true, $search = [])
    {
        $filter = 1;
        if (is_array($search) && count($search) > 0) {
            $currency = (array_key_exists('currency', $search)) ? " AND currency_id = '" .
                addslashes(trim($search['currency'])) . "'" : "";
            $filter .= $currency;

            $from = (array_key_exists('from', $search)) ? " AND wef = '" .
                addslashes(trim($search['from'])) . "' " : "";
            $filter .= $from;
        }

        if ($active) {
            $active = " AND is_active = 1";
            $filter .= $active;
        }

        return $this->whereRaw($filter)->first();
    }
}
