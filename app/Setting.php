<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'setting_master';

    protected $fillable = [
        'company_id',
        'currency_id',
        'timezone_id',
        'datetime_format_id',
        'theme_id',
        'is_email_enable',
        'is_sms_enable',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * @param $query
     * @return mixed
     */
    public function scopeCompany($query)
    {
        return $query->where('setting_master.company_id', loggedInCompanyId());
    }

    /**
     * @param $inputs
     * @return \Illuminate\Validation\Validator
     */
    public function validateSetting($inputs)
    {
        $rules = [
            'currency_id' => 'required',
            'timezone_id' => 'required',
            'theme_id' => 'required'
        ];

        return \Validator::make($inputs, $rules);
    }

    /**
     * @param $inputs
     * @param null $id
     * @return mixed
     */
    public function store($inputs, $id = null)
    {
        if ($id) {
            //dd($inputs);
            unset($inputs['_token']);
            unset($inputs['tab']);
            return $this->where('company_id', $id)
                ->update($inputs);
        } else {
            return $this->create($inputs)->id;
        }
    }

    /**
     * @param null $companyID
     * @return mixed
     */
    public function getSettingByCompanyId($companyID = null)
    {
        return $this->where('company_id', $companyID)
            ->first();
    }

    public function getSettingService($inputs = []) {
        $fields = [
                'company.id as company_id',
                'currency.name as currency_name',
                'currency.symbol as currency_symbol',
                'currency.id as currency_id',
                'datetime_format.format as date_time_format',
                'setting_master.datetime_format_id as datetime_format_id',
                'timezones.timestamp',
                'timezones.name'
        ];
        $result = $this
                    ->leftJoin('company', 'company.id', '=', 'setting_master.company_id')
                    ->leftJoin('currency', 'currency.id', '=', 'setting_master.currency_id')
                    ->leftJoin('timezones', 'timezones.id', '=', 'setting_master.timezone_id')
                    ->leftJoin('datetime_format', 'datetime_format.id', '=', 'setting_master.datetime_format_id')
                    ->company()
                    ->first($fields);
        return $result;
    }

}
