<?php

class Users extends Illuminate\Database\Eloquent\Model {
    protected $table = "usuarios";

    public static function get_author($id) {
        $user = Users::find($id);
        return $user->email;
    }

    public static function get_administrador_empresa($id) {
        $user = Users::find($id);
        return $user->empresa_admin;
    }	
	
    public static function get_id($user) {
        $user = Users::where('email', '=', $user)->first();
        return $user->id;
    }

}