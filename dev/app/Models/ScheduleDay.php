<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleDay extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'schedule_id',
        'date',
    ];

    /**
     * Get the schedule that owns the day.
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}