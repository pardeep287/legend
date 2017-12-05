<?php

namespace App;
/**
 * :: Invoice Setting Model ::
 * To manage Invoice Setting CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;

class InvoiceSetting extends Model
{

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'invoice_setting';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'company_id',
        'header_required',
        'header_top_space',
        'footer_space',
        'footer_required',
        'invoice_label',
        'invoice_format',
        'invoice_prefix',
        'round_off_type',
        'gst_display',
        'pan_display',
        'deal_in',
        'terms',
        'auth_signature_show',
        'auth_text',
        'customer_signature_show',
        'signature_text',
        'print_options',
        'created_by',
        'updated_by',
        'updated_at',
        'updated_ip`',
    ];
    
    public function validateSetting($inputs = [])
    {
        $rules = [
            'invoice_label'     => 'required'
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
            unset($inputs['_method']);
            unset($inputs['_token']);
            return $this->where('company_id', loggedInCompanyId())->update($inputs);
        } else {
            return $this->create($inputs);
        }
    }

    /**
     * Method is used to get company information
     *
     * @param null $id
     *
     * @return mixed
     */
    public function getInvoiceSetting($id = null)
    {
        $result = null;
        if($id) {
            $result = $this->where('company_id', $id)->first();
        }
        return $result;
    }
}
