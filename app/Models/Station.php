<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Station extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'staStations';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'stationID';

    /**
     *
     */
    public function solarSystem()
    {
        return $this->hasOne('MapSolarSystem', 'solarSystemID', 'solarSystemID');
    }
}