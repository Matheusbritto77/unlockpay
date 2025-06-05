<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'description',
        'image_path',
        'mode',
        'frequency',
        'status',
    ];

    /**
     * Get the user that owns the schedule.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the hours associated with the schedule.
     */
    public function hours()
    {
        return $this->hasMany(ScheduleHour::class);
    }

    /**
     * Get the specific days associated with the schedule.
     */
    public function days()
    {
        return $this->hasMany(ScheduleDay::class);
    }

    /**
     * Get the external groups associated with the schedule.
     */
    public function externalGroups()
    {
        return $this->hasMany(ScheduleExternalGroup::class);
    }

    public function periodo()
{
    return $this->hasOne(SchedulesPeriodo::class, 'schedules_id', 'id');
}
}