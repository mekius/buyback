<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapSolarSystem extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'mapSolarSystems';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'solarSystemID';

    /**
     *
     */
    public function stations()
    {
        return $this->hasMany('Station', 'solarSystemID', 'solarSystemID');
    }
}