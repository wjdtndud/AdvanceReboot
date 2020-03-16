<?php


namespace AdvanceReboot;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\scheduler\Task;
use AdvanceReboot\command\RebootCommand;

class Reboot extends PluginBase
{
	
	private static $instance = null;
	
	public static $prefix = "§l§b[재부팅]§r§7 ";
	
	public $config, $db;
	
	public $vote = [];
	
	
	public static function getInstance (): Reboot
	{
		return self::$instance;
	}
	
	public function onLoad (): void
	{
		if (self::$instance === null)
			self::$instance = $this;
	}
	
	public function onEnable (): void
	{
		if (!file_exists ($this->getDataFolder ())) {
			@mkdir ($this->getDataFolder ());
		}
		$this->config = new Config ($this->getDataFolder () . "config.yml", Config::YAML, [
			"rebootTime" => 3600,
			"nowRebootTime" => 1200,
			"memory" => 1500
		]);
		$this->db = $this->config->getAll ();
		$this->getScheduler ()->scheduleRepeatingTask (new class ($this) extends Task{
			protected $plugin;
			
			public function __construct (Reboot $plugin)
			{
				$this->plugin = $plugin;
			}
			
			public function onRun (int $currentTick)
			{
				$nowMemory = number_format (round((memory_get_usage()/1024)/1024,2));
				if ($nowMemory === number_format ($this->plugin->db ["memory"])) {
					$this->plugin->reboot ("메모리 과부화");
				}
				if (isset ($this->plugin->vote ["status"])) {
					$nowVote = count ($this->plugin->vote ["player"]);
					$maxVote = $this->plugin->vote ["maxVote"];
					if ($nowVote >= $maxVote) {
						$this->plugin->reboot ("투표");
      $this->plugin->vote ["maxVote"] = PHP_INT_MAX;
					}
				}
				$this->plugin->db ["nowRebootTime"] --;
				if ($this->plugin->db ["nowRebootTime"] <= 0) {
					$this->plugin->reboot ("재부팅 시간");
					$this->plugin->db ["nowRebootTime"] = 0;
				}
			}
		}, 25);
		$this->getServer ()->getCommandMap ()->registerAll ("avas", [
			new RebootCommand ($this)
		]);
	}
	
	public function onDisable (): void
	{
		$this->db ["nowRebootTime"] = $this->getRebootTime ();
		if ($this->config instanceof Config) {
			$this->config->setAll ($this->db);
			$this->config->save ();
		}
	}
	
	public function getRebootTime (): int
	{
		return intval ($this->db ["rebootTime"]);
	}
	
	public function getRebootTimeArray (): array
	{
		$time = $this->getNowRebootTime ();

		$hour = (int) ($time / 60 / 60);

		$minute = ((int)($time / 60)) - ($hour*60);

		$second = (int)$time - (($hour*60*60)+($minute*60));

		return [

			$hour,

			$minute,

			$second

		];
	}
	
	public function getNowRebootTime (): int
	{
		return intval ($this->db ["nowRebootTime"]);
	}
	
	public function reboot (string $reason)
	{
		$this->getServer ()->broadcastMessage (self::$prefix . "서버가 §a{$reason}§7 이유로 3초뒤 재부팅이 됩니다.");
		$this->getScheduler ()->scheduleDelayedTask (new class ($this) extends Task{
			protected $plugin;
			
			public function __construct (Reboot $plugin)
			{
				$this->plugin = $plugin;
			}
			
			public function onRun (int $currentTick)
			{
				foreach ($this->plugin->getServer ()->getOnlinePlayers () as $players) {
					$players->save ();
					$players->kick ("§l§6서버가 재부팅되었습니다. 다시 접속해주세요!");
				}
				foreach ($this->plugin->getServer ()->getLevels () as $level) {
					$level->save (true);
				}
				$this->plugin->getServer ()->shutdown ();
			}
		}, 25 * 3);
	}
	
	public static function message ($player, string $msg): void
	{
		$player->sendMessage (self::$prefix . $msg);
	}
	
}