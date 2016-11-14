<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvType extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'invTypes';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'typeID';

    /**
     *
     */
    public function marketItem()
    {
        return $this->hasOne('MarketItem', 'typeId', 'typeID');
    }
}