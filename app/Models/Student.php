<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        's_firstname',
        's_lastname',
        's_middlename',
        's_birthdate',
        's_address',
        's_contact',
        's_email',
        's_guardianfirstname',
        's_guardianlastname',
        's_guardiancontact',
        's_guardianemail',
        's_gradelvl',
        'gradelvl_id',
        's_tuition_sum',
        'tuition_id',
        'enrollment_status',
        'payment_status',
    ];
}
