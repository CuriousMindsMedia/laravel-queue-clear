<?php namespace Morrislaptop\LaravelQueueClear;

use Illuminate\Queue\QueueManager;
use Illuminate\Contracts\Queue\Factory as FactoryContract;
use Morrislaptop\LaravelQueueClear\Contracts\Clearer as ClearerContract;

class Clearer implements ClearerContract
{
	/**
	 * @var QueueManager
	 */
	protected $manager;

	/**
	 * {@inheritDoc}
	 */
	function __construct(FactoryContract $manager)
	{
		$this->manager = $manager;
	}

	/**
	 * {@inheritDoc}
	 */
	public function clear($connection, $queue)
	{
		$count = 0;
		$connection = $this->manager->connection($connection);

		while ($job = $connection->pop($queue)) {
			$job->delete();
			$count++;
		}


		$pheanstalkInstance = \Queue::getPheanstalk();
		while ($job = $this->tryToGetDelayed($pheanstalkInstance, $queue)) {
			$pheanstalkInstance->delete($job);
			$count++;
		}

		return $count;
	}

	private function tryToGetDelayed($pheanstalkInstance, $queue)
	{
		try {
			return $pheanstalkInstance->peekDelayed($queue);
		} catch (\Pheanstalk\Exception\ServerException $e) {
			return false;
		}
	}

}
