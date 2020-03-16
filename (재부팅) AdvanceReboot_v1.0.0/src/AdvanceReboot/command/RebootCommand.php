<?php


namespace AdvanceReboot\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use AdvanceReboot\Reboot;

class RebootCommand extends Command
{
	
	protected $plugin = null;
	
	
	public function __construct (Reboot $plugin)
	{
		$this->plugin = $plugin;
		parent::__construct ("재부팅", "재부팅 명령어 입니다. || 아바스", "/재부팅", [
			"reboot"
		]);
	}
	
	public function execute (CommandSender $player, string $label, array $args): bool
	{
		switch ($args [0] ?? "x") {
			case "투표":
				if (!isset ($this->plugin->vote ["status"])) {
					$this->plugin->vote ["status"] = $player->getName ();
					$this->plugin->vote ["player"] = [];
					$this->plugin->vote ["player"] [$player->getName ()] = true;
					$this->plugin->vote ["maxVote"] = (int) count ($this->plugin->getServer ()->getOnlinePlayers ()) / 2;
					
					foreach ($this->plugin->getServer ()->getOnlinePlayers () as $players) {
						Reboot::message ($players, "§a" . $player->getName () . "§7 님께서 §a' 재부팅 투표 '§7 를 시작하였습니다.");
						Reboot::message ($players, "재부팅 투표를 동의하시는 분께서는 §a/재부팅 투표§7 명령어를 입력해주세요.");
						Reboot::message ($players, "투표 수가 §a{$this->plugin->vote ["maxVote"]} 명§7이 동의를 하셔야 재부팅이 됩니다.");
					}
				} else {
					if (!isset ($this->plugin->vote ["player"] [$player->getName ()])) {
						$this->plugin->vote ["player"] [$player->getName ()] = true;
						Reboot::message ($player, "재부팅 투표에 참가하셨습니다.");
						
						$calulture = $this->plugin->vote ["maxVote"] - count ($this->plugin->vote ["player"]);
						foreach ($this->plugin->getServer ()->getOnlinePlayers () as $players) {
							Reboot::message ($players, "§a" . $player->getName () . "§7 님께서 재부팅 투표를 동의하셨습니다. 남은 투표수 : §a{$calulture}");
						}
					} else {
						Reboot::message ($player, "당신은 이미 동의하셨습니다.");
					}
				}
				break;
			case "실행":
				if ($player->isOp ()) {
					$this->plugin->reboot ("{$player->getName ()} 님에 의한 강제 재부팅");
				} else {
					Reboot::message ($player, "당신은 권한이 없습니다.");
}
				break;
			case "시간설정":
				if ($player->isOp ()) {
					if (!isset ($args [1]) or !is_numeric ($args [1])) {
						Reboot::message ($player, "/재부팅 시간설정 (분)");
						return true;
					}
					$this->plugin->db ["rebootTime"] = (60 * $args [1]);
					Reboot::message ($player, "재부팅 시간이 §a{$args [1]}§7 분으로 설정되었습니다.");
				} else {
					Reboot::message ($player, "당신은 권한이 없습니다.");
				}
				break;
			default:
				Reboot::message ($player, "/재부팅 투표 - 재부팅 투표를 시작하거나 동의를 합니다.");
				if ($player->isOp ()) {
					Reboot::message ($player, "/재부팅 실행 - 강제로 재부팅을 합니다.");
					Reboot::message ($player, "/재부팅 시간설정 (분) - 재부팅 시간을 설정합니다.");
				}
				break;
		}
		return true;
	}
}