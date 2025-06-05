<?php




namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleHour extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'schedule_id',
        'hour',
    ];

    /**
     * Get the schedule that owns the hour.
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}