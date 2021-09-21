<?php

namespace MulqiGaming64\BetterHub;

use pocketmine\plugin\PluginBase;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as C;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\scheduler\ClosureTask;

use MulqiGaming64\BetterHub\commands\HubCommands;
use MulqiGaming64\BetterHub\commands\LobbyCommands;
use MulqiGaming64\BetterHub\commands\SetHubCommands;
use MulqiGaming64\BetterHub\commands\SetLobbyCommands;
use MulqiGaming64\BetterHub\commands\SetVIPHubCommands;

class BetterHub extends PluginBase implements Listener{
    
    private $hubrequest = [];
    private $coordinates;
		
	public function onEnable(): void{
		$this->saveDefaultConfig();
		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
        	function(int $currentTick): void{
            	foreach($this->getServer()->getOnlinePlayers() as $player){
					$name = strtolower($player->getName());
					if(isset($this->hubrequest[$name])){
						if(time() >= $this->hubrequest[$name]){
							$this->teleportHub($player);
						}
					}
				}
            }
        ), 20);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->registerCommands();
        $this->registerConfig();
	}
	
	private function registerConfig(): void{
		$x = Server::getInstance()->getDefaultLevel()->getSafeSpawn()->getFloorX();
		$y = Server::getInstance()->getDefaultLevel()->getSafeSpawn()->getFloorY();
		$z = Server::getInstance()->getDefaultLevel()->getSafeSpawn()->getFloorZ();
		$level = Server::getInstance()->getDefaultLevel()->getFolderName();
		$coor = [
			"lobby" => [
				"x" => $x,
				"y" => $y,
				"z" => $z,
				"level" => $level
			],
			"vip" => [
				"x" => $x,
				"y" => $y,
				"z" => $z,
				"level" => $level
			]
		];
		$this->coordinates = new Config($this->getDataFolder() . "coordinates.yml", Config::YAML, $coor);
	}
	
	private function registerCommands(): void{
		$this->getServer()->getCommandMap()->register("BetterHub",  new HubCommands("hub", $this));
		$this->getServer()->getCommandMap()->register("BetterHub",  new SetHubCommands("sethub", $this));
		$this->getServer()->getCommandMap()->register("BetterHub",  new LobbyCommands("lobby", $this));
		$this->getServer()->getCommandMap()->register("BetterHub",  new SetLobbyCommands("setlobby", $this));
		if($this->getConfig()->get("vip-enabled", true)){
			$this->getServer()->getCommandMap()->register("BetterHub",  new SetVIPHubCommands("setviphub", $this));
		}
	}
	
	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$this->teleportHub($player, false);
		return true;
	}
	
	public function onMove(PlayerMoveEvent $event){
		if($event->isCancelled()) return;
		$player = $event->getPlayer();
		$name = strtolower($player->getName());
		if(isset($this->hubrequest[$name])){
			// Check when player move, Request Canceled
			unset($this->hubrequest[$name]);
			$player->sendMessage($this->replaceTag($player, $this->getConfig()->get("cancel-message")));
		}
		return true;
	}
	
	public function hubRequest(Player $player){
		$name = strtolower($player->getName());
		if(!isset($this->hubrequest[$name])){
			$delay = $this->getConfig()->get("delay", 5);
			$player->sendMessage($this->replaceTag($player, $this->getConfig()->get("hub-message")));
			$this->hubrequest[$name] = time() + $delay;
		} else {
			$player->sendMessage($this->replaceTag($player, $this->getConfig()->get("teleporting-message")));
		}
		return true;
	}
	
	private function teleportHub(Player $player, $msg = true){
		$name = strtolower($player->getName());
		unset($this->hubrequest[$name]);
		if($player->hasPermission("betterhub.vip") && $this->getConfig()->get("vip-enable", true)){
			$this->getServer()->loadLevel($this->coordinates->getAll()["vip"]["level"]); // load world before tp
			$player->teleport(new Position($this->coordinates->getAll()["vip"]["x"], $this->coordinates->getAll()["vip"]["y"], $this->coordinates->getAll()["vip"]["z"], $this->getServer()->getLevelByName($this->coordinates->getAll()["vip"]["level"])));
			if($msg){
				$player->sendMessage($this->replaceTag($player, $this->getConfig()->get("teleport-message")));
			}
		} else {
			$this->getServer()->loadLevel($this->coordinates->getAll()["lobby"]["level"]); // load world before tp
			$player->teleport(new Position($this->coordinates->getAll()["lobby"]["x"], $this->coordinates->getAll()["lobby"]["y"], $this->coordinates->getAll()["lobby"]["z"], $this->getServer()->getLevelByName($this->coordinates->getAll()["lobby"]["level"])));
			if($msg){
				$player->sendMessage($this->replaceTag($player, $this->getConfig()->get("teleport-message")));
			}
		}
		return true;
	}
	
	public function setCoordinatesHub(Player $player): bool{
		$x = $player->getX();
		$y = $player->getY();
		$z = $player->getZ();
		$level = $player->getLevel()->getFolderName();
		$this->coordinates->setNested("lobby.x", $x);
		$this->coordinates->setNested("lobby.y", $y);
		$this->coordinates->setNested("lobby.z", $z);
		$this->coordinates->setNested("lobby.level", $level);
		$this->coordinates->save();
        $this->coordinates->reload();
        return true;
    }
    
    public function setCoordinatesVIP(Player $player): bool{
		$x = $player->getX();
		$y = $player->getY();
		$z = $player->getZ();
		$level = $player->getLevel()->getFolderName();
		$this->coordinates->setNested("vip.x", $x);
		$this->coordinates->setNested("vip.y", $y);
		$this->coordinates->setNested("vip.z", $z);
		$this->coordinates->setNested("vip.level", $level);
		$this->coordinates->save();
        $this->coordinates->reload();
        return true;
    }
	
	private function replaceTag(Player $player, string $msg): string{
		$msg = str_replace("{name}", $player->getName(), $msg);
		return $msg;
	}
}
