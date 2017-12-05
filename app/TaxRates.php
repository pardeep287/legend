<?php
namespace App;
/**
 * :: Tax Rates Model ::
 * To manage Tax Rates CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;

class TaxRates extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tax_rates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'tax_id',
        'cgst_rate',
        'sgst_rate',
        'igst_rate',
        'cgst_account_id',
        'sgst_account_id',
        'igst_account_id',
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
            unset($input['cgst_rate']);
            unset($input['sgst_rate']);
            unset($input['igst_rate']);
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
    public function getEffectedTax($active = true, $search = [])
    {
        $filter = 1;
        if (is_array($search) && count($search) > 0) {
            $tax = (array_key_exists('tax', $search)) ? " AND tax_id = '" .
                addslashes(trim($search['tax'])) . "'" : "";
            $filter .= $tax;

            $from = (array_key_exists('from', $search)) ? " AND wef = '" .
                addslashes(trim($search['from'])) . "' " : "";
            $filter .= $from;
        }

        if ($active) {
            $active = " AND is_active = 1";
            $filter .= $active;
        }
        return $this->leftJoin('tax', 'tax_rates.tax_id', '=', 'tax.id')
                ->whereRaw($filter)
                ->first(['tax_rates.*', 'tax.company_id']);
    }

    /**
     * @param $id
     * @param $date
     * @return mixed
     */
    public function getEffectedTaxRate($id, $date)
    {
        return $this->leftJoin('tax', 'tax_rates.tax_id', '=', 'tax.id')
                ->where('tax_id', $id)
                ->where(function($query) use ($date) {
                        $query->where(function($inner) use ($date) {
                            $inner->where('wef', '<=', $date)
                            ->where('wet', '>=', $date);
                        });
                        $query->oRWhere(function($inner) use ($date) {
                            $inner->where('wef', '<=', $date)
                            ->whereNull('wet');
                        });
                })->first(['tax_rates.*', 'tax.company_id']);
    }
}
