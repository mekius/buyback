<?php namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
        'App\Console\Commands\MarketUpdate',
        'App\Console\Commands\SDEUpdate',
        'App\Console\Commands\AssetUpdate'
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		$schedule->command('market:update')->dailyAt('11:00')->timezone('UTC');
        $schedule->command('sde:update')->dailyAt('11:01')->timezone('UTC');
	}

}
