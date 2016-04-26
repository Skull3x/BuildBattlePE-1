<?php

namespace ImagicalGamer\BuidBattle;

use pocketmine\plugin\PluginBase;
use pocketmine\plugin\Plugin;

use pocketmine\scheduler\PluginTask;

use pocketmine\Server;
use pocketmine\Player;

use pocketmine\command\{Command, CommandSender, ConsoleCommandSender};

use pocketmine\utils\TextFormat as C;
use pocketmine\utils\Config;

use pocketmine\math\Vector3;

use pocketmine\level\particle\HeartParticle;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

class Main extends PluginBase implements Listener{
  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    $this->getServer()->getScheduler()->scheduleRepeatingTask(new Particles($this), 1);
    $this->getLogger()->info(C::GREEN . "Enabled!");
  }
  public function onBreak(BlockBreakEvent $event){
  	if($event->getBlock()->getId() == 5){
  		$event->setCancelled(true);
  	}
  	if($event->getBlock()->getId() == 44){
  		$event->setCancelled(true);
  	}
  }
  public function onPlace(BlockPlaceEvent $event){
  	if($event->getBlock()->getId() == 10){
  		$event->setCancelled(true);
  	}
  }
  public function getTheme(){
  	 $theme = rand(1,2);
  	 switch($theme){
  	 	case 1:
  	 		$bunny = "bunny";
  	 	case 2:
  	 		$wolf = "Wolf";
  	 }
  	 return $theme;
  }
  public function onCommand(CommandSender $s, Command $cmd, $label, array $args){
    if(strtolower($cmd->getName() == "bb")){
      if($s instanceof Player){
        if(!isset($args[0])){
          $s->sendMessage(C::RED."/bb create <world>");
        }else{
          if($args[0] == "create"){
            if(!isset($args[1])){
              $s->sendMessage(C::RED."/bb create <world>");
            }else{
              $world = $args[1];
              $this->level = $args[1];
              if($world instanceof Level){
                
                if(!$this->config->exists("arenas")){
                  $this->config->set("arenas", array($world));
                }else{
                  array_push($this->config->get("arenas",$args[1]));
                }
                
              $this->getServer()->loadLevel($world);
		          $this->getServer()->getLevelByName($world)->loadChunk($this->getServer()->getLevelByName($world)->getSafeSpawn()->getFloorX(), $this->getServer()->getLevelByName($world)->getSafeSpawn()->getFloorZ());
		          $s->teleport($this->getServer()->getLevelByName($world)->getSafeSpawn());
		          $this->spawn = 1;
		          $s->sendMessage(C::BLUE."Preparing BuildBattlePE level!")
              }else{
                $s->sendMessage(C::RED."World not found!");
              }
            }
          }
        }
      }else{
        $s->sendMessage(C::RED."Please run this command in-game!");
      }
    }
    return true;
  }
}
class Particles extends PluginTask {
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
  
	public function onRun($tick)
	{
		$level = $this->plugin->getServer()->getLevelByName("BuildBattlePE");
		$tiles = $level->getTiles();
		$prefix = "[heart]";
		foreach($tiles as $t) {
			if($t instanceof Sign) {	
				$text = $t->getText();
				if($text[0]==$prefix)
				{
					$x = $t->getX();
					$y = $t->getY();
					$z = $t->getX();
					$level->addParticle(new HeartParticle(new Vector3($x, $y, $z))); 
					$t->setBlock(new Vector3($x,$y,$z), Block::get(0));
				}
			}
		}
	}
}
