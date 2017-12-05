<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $table = 'themes';

    protected $fillable = [
        'theme_name',
        'header_color',
        'sidebar_color',
        'panel_color',
        'font_color',
        'hover_color',
        'active_color',
        'button_color',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
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
     * @return mixed
     */
    public function getThemeService()
    {
        $data = $this->active()->get([\DB::raw("concat(theme_name, ' (', file_name) as name"), 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name .')';
        }
        return ['' => '-Select Theme-'] + $result;
    }
}
