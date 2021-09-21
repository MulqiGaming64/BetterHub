<?php

namespace MulqiGaming64\BetterHub\commands;

use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\utils\TextFormat as TF;
use MulqiGaming64\BetterHub\BetterHub;

class LobbyCommands extends PluginCommand {
	
	/** @var BetterHub $plugin */
	private $plugin;
	
	public function __construct(string $name, BetterHub $plugin){
        parent::__construct($name, $plugin);
		$this->setDescription("Teleport To Lobby");
        $this->plugin = $plugin;
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player){
			$sender->sendMessage("Use Commands In Game Please");
            return true;
        }
        $this->plugin->hubRequest($sender);
	}
}