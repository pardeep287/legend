<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timezone extends Model
{

    protected $table = 'timezones';

    protected $fillable = [
        'timestamp',
        'name',
        'status',
        'created_at',
        'updated_at'
    ];

    /**
     * @param null $search
     * @param $skip
     * @param $perPage
     * @return mixed
     */
    public function getTimezones($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'id',
            'name',
            'timestamp',
            'status',
        ];

        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->whereRaw($filter)
            ->orderBy('id', 'ASC')->skip($skip)->take($take)->get($fields);
    }

    /**
     * @param null $search
     * @return mixed
     */
    public function totalTimezones($search = null)
    {
        $filter = 1; // if no search add where

        // when search
        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->select(\DB::raw('count(*) as total'))
            ->whereRaw($filter)->first();
    }

    public function updateStatusAll()
    {
        return $this->where('status', '=' ,1)->update([ 'status' => 0 ]);
    }


    /**
     * @return array
     */
    public function getTimezoneService()
    {
        $data = $this->get([\DB::raw("concat(timestamp, ' (', name) as name"), 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name .')';
        }
        return ['' => '-Select Timezone-'] + $result;
    }
}