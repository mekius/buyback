<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketItem extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'marketItem';

    public function type()
    {
        return $this->belongsTo('\App\Models\InvType', 'typeId', 'typeID');
    }

    /**
     * Pull data from object received from Eve Central
     *
     * @param $data stdClass Market data from Eve Central
     */
    function updateFromEveCentral($data)
    {
        $this->buyVolume = $data->buy->volume;
        $this->buyAvg = $data->buy->avg;
        $this->buyMax = $data->buy->max;
        $this->buyMin = $data->buy->min;
        $this->buyStddev = $data->buy->stddev;
        $this->buyMedian = $data->buy->median;
        $this->buyPercentile = $data->buy->percentile;

        $this->sellVolume = $data->sell->volume;
        $this->sellAvg = $data->sell->avg;
        $this->sellMax = $data->sell->max;
        $this->sellMin = $data->sell->min;
        $this->sellStddev = $data->sell->stddev;
        $this->sellMedian = $data->sell->median;
        $this->sellPercentile = $data->sell->percentile;

        $this->allVolume = $data->all->volume;
        $this->allAvg = $data->all->avg;
        $this->allMax = $data->all->max;
        $this->allMin = $data->all->min;
        $this->allStddev = $data->all->stddev;
        $this->allMedian = $data->all->median;
        $this->allPercentile = $data->all->percentile;
    }
}