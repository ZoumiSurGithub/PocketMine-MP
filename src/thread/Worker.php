<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\thread;

use pocketmine\scheduler\AsyncTask;
use const PTHREADS_INHERIT_NONE;

/**
 * Specialized Worker class for PocketMine-MP-related use cases. It handles setting up autoloading and error handling.
 *
 * Workers are a special type of thread which execute tasks passed to them during their lifetime. Since creating a new
 * thread has a high resource cost, workers can be kept around and reused for lots of short-lived tasks.
 *
 * As a plugin developer, you'll rarely (if ever) actually need to use this class directly.
 * If you want to run tasks on other CPU cores, check out AsyncTask first.
 * @see AsyncTask
 */
abstract class Worker extends \Worker{
	use CommonThreadPartsTrait;

	public function start(int $options = PTHREADS_INHERIT_NONE) : bool{
		//this is intentionally not traitified
		ThreadManager::getInstance()->add($this);

		if($this->getClassLoaders() === null){
			$this->setClassLoaders();
		}
		return parent::start($options);
	}

	/**
	 * Stops the thread using the best way possible. Try to stop it yourself before calling this.
	 */
	public function quit() : void{
		$this->isKilled = true;

		if(!$this->isShutdown()){
			$this->synchronized(function() : void{
				while($this->unstack() !== null);
			});
			$this->notify();
			$this->shutdown();
		}

		ThreadManager::getInstance()->remove($this);
	}
}
