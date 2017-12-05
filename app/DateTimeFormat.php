<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\EventListener\ValidateRequestListener;

class DateTimeFormat extends Model
{
    use SoftDeletes;

    protected $table = 'datetime_format';

    protected $fillable = [
        'id',
        'format',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * @param $inputs
     * @return \Illuminate\Validation\Validator
     */
    public function validateDateTimeFormat($inputs)
    {
        $rules = [
            'format' => 'required'
        ];

        $messages = [
            'format.required' => 'The datetime format field is required.'
        ];

        return \Validator::make($inputs, $rules, $messages);
    }

    /**
     * @param $input
     * @param null $id
     * @return mixed|null
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
     * @param null $search
     * @return mixed
     */
    public function getDateTimeFormat($search = null, $skip, $perPage)
    {
        $fields = [
            'id',
            'format',
            'status'
        ];

        $sortBy = [
            'format' => 'format',
            'status' => 'status'
        ];
        $take = ((int)$perPage > 0) ? $perPage : '20';

        $filter = 1;

        $orderEntity = 'id';
        $orderAction = 'desc';
        if (isset($search['sort_action']) && $search['sort_action'] != "" && $search['sort_action'] != 0) {
            $orderAction = ($search['sort_action'] == 1) ? 'desc' : 'asc';
        }

        if (isset($search['sort_entity']) && $search['sort_entity'] != "") {
            $orderEntity = (array_key_exists($search['sort_entity'], $sortBy)) ? $sortBy[$search['sort_entity']] : $orderEntity;
        }

        if (is_array($search) && count($search) > 0) {
            $format = (array_key_exists('keyword', $search)) ? " AND format like '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $format;
        }

        return $this->whereRaw($filter)
            ->orderBy($orderEntity, $orderAction)
            ->skip($skip)->take($take)->get($fields);
    }

    /**
     * Method is used to get total bank search wise.
     *
     * @param array $search
     *
     * @return mixed
     */
    public function totalDateTimeFormat($search = null)
    {
        $filter = 1;

        if (is_array($search) && count($search) > 0) {
            $status = (array_key_exists('status', $search)) ? " AND status = '" .
                addslashes(trim($search['status'])) . "' " : "";
            $filter .= $status;
        }

        if (is_array($search) && count($search) > 0) {
            $format = (array_key_exists('format', $search)) ? " AND format like '%" .
                addslashes(trim($search['format'])) . "%' " : "";
            $filter .= $format;
        }

        $result = $this->select(\DB::raw('count(*) as total'))->whereRaw($filter);
        return $result->get()->first();
    }

    /**
     * @return mixed
     */
    public function getDateTimeFormatService()
    {
        $result = $this->active()->pluck('format', 'id')->toArray();
        return ['' => '-Select Datetime Format-'] + $result;
    }

    public function drop($id)
    {
        $fields = [
            'deleted_at' => convertToUtc(),
            'deleted_by' => authUserId()
        ];

        $this->find($id)->update($fields);
    }
}
