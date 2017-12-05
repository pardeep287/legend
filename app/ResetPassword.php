<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ResetPassword extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'password',
        'is_reset_password'
    ];


    /**
     * @param $input
     *
     * @return mixed
     */
    public function validateResetPassword($inputs)
    {
        $rules = ['email' => 'required'];
        return \Validator::make($inputs, $rules);
    }

    /**
     * @param $email
     *
     * @return mixed
     */
    public function getUserDetail($email)
    {
        return $this->where('username', $email)
            ->orWhere('email', $email)->first();
    }

    /**
     * @param $input
     * @param null $id
     *
     * @return mixed
     */
    public function updatePassword($input, $id)
    {
        return $this->find($id)->update($input);
    }
}
