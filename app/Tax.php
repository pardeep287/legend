<?php
namespace App;
/**
 * :: Tax Model ::
 * To manage Tax CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tax';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'account_id',
        'company_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_ip',
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
     * @param $query
     * @return mixed
     */
    public function scopeCompany($query)
    {
        /* orWhere is written because we have 1 tax default */
        /* tax rate are not applied by company */
        return $query->where('tax.company_id', loggedInCompanyId());
    }

    /**
     * Method is used to validate roles
     *
     * @param $inputs
     * @param int $id
     * @return Response
     */
    public function validateTax($inputs, $id = null)
    {
        // validation rule
        if ($id) {
            $rules['name'] = 'unique:tax,name,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
            //$rules['code'] = 'unique:tax,code,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        } else {
            $rules['name'] = 'required|unique:tax,name,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
            //$rules['code'] = 'required|unique:tax,code,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        }
        $rules['cgst_rate'] = 'required|numeric';
        $rules['sgst_rate'] = 'required|numeric';
        $rules['igst_rate'] = 'required|numeric';

        $rules['cgst_account_id'] = 'required|numeric';
        $rules['sgst_account_id'] = 'required|numeric';
        $rules['igst_account_id'] = 'required|numeric';

        $messages = [
            'cgst_account_id.required' => 'The cgst account field is required.',
            'cgst_account_id.numeric' => 'The cgst account field must be numeric.',

            'sgst_account_id.required' => 'The sgst account field is required.',
            'sgst_account_id.numeric' => 'The sgst account field must be numeric.',

            'igst_account_id.required' => 'The igst account field is required.',
            'igst_account_id.numeric' => 'The igst account field must be numeric.'
        ];
        return \Validator::make($inputs, $rules, $messages);
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
            //dd($input);
            $this->find($id)->update($input);
            $rate = 0;
            $rates = (new TaxRates)->getEffectedTax(true, ['tax' => $id]);
            if ($rates) {
                //dd($input);
                $cgst = $rates->cgst_rate;
                $sgst = $rates->sgst_rate;
                $igst = $rates->igst_rate;
                if (($cgst != $input['cgst_rate'] ||
                        $sgst != $input['sgst_rate'] ||
                        $igst != $input['igst_rate'])) {
                    //update older rate
                    $input['is_active'] = 0;
                    $input['wet'] = convertToUtc();
                    (new TaxRates)->store($input, $rates->id);
                    unset($input['wet']);

                    //add new rate
                    $input['is_active'] = 1;
                    $input['tax_id'] = $id;
                    $input['wef'] = convertToUtc();
                    (new TaxRates)->store($input);
                }
            } else {
                //add new rate
                $input['is_active'] = 1;
                $input['tax_id'] = $id;
                $input['wef'] = convertToUtc();
                (new TaxRates)->store($input);
            }
        } else {
            $id = $this->create($input)->id;
            $input['tax_id'] = $id;
            $input['is_active'] = 1;
            (new TaxRates)->store($input);
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
    public function getTaxes($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'id',
            'name',
            'code',
            'status',
        ];

        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('keyword', $search) && $search['keyword'] != "") ? " AND name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $f1;
        }
        return $this->whereRaw($filter)->company()
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
    public function totalTaxes($search = null)
    {
        $filter = 1; // if no search add where

        // when search
        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('keyword', $search) && $search['keyword'] != "") ? " AND name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $f1;
        }
        return $this->select(\DB::raw('count(*) as total'))->company()
                ->whereRaw($filter)->first();
    }

    /**
     * @return mixed
     */
    public function getTaxService()
    {
        $result = $this->active()->company()->pluck('name', 'id')->toArray();
        return ['' => '-Select Tax Category-'] + $result;
    }
}
