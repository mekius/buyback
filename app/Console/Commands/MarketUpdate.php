<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use App\Models\InvType;
use App\Models\MarketItem;
use App\Models\MapSolarSystem;
use PHPEveCentral\Requests\MarketStat;

class MarketUpdate extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'market:update';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update local copy of market data.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
        $system = config('market.system');
        $invTypes = InvType::whereIn('typeName', config('market.monitored'))->get();

        if (!$invTypes || ($invTypes->count() < 1)) {
            $this->error('Failed to find typeIds');
        }

        $system = MapSolarSystem::where(array('solarSystemName' => $system))->first();

        $this->info("Updating market data based on {$system->solarSystemName} ({$system->solarSystemID})");
        $this->info("------------------------------------------------");

        $types = array();
        /** @var $type InvType */
        foreach($invTypes as $type) {
            $types[$type->typeID] = $type->typeName;
        }

        $request = new MarketStat(array_keys($types));
        $request->setUseSystem($system->solarSystemID);
        $results = $request->send()->getAllTypes();

        MarketItem::unguard();
        foreach ($results as $typeId => $data) {
            $this->info("Updating {$types[$typeId]} ($typeId)");

            $marketItem = MarketItem::firstOrNew(array('typeId' => $typeId));
            $marketItem->updateFromEveCentral($data);
            $marketItem->save();
        }
        MarketItem::reguard();
	}
}
