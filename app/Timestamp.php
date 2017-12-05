<?php
namespace App;
/**
 * :: Timestamp Model ::
 * To manage Timestamp CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;

class Timestamp extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'timezones';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'timestamp'
    ];

    /**
     * @return mixed
     */
    public function getTimeStampsService()
    {
        $result = $this->pluck('name', 'id')->toArray();
        return ['' => '-Select Time Zone-'] + $result;
    }
}
