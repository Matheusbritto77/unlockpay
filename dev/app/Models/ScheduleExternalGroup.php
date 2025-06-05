<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleExternalGroup extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'schedule_id',
        'group_id',
    ];

    /**
     * Get the schedule that owns the external group.
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}