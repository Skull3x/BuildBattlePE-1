<?php

namespace ImagicalGamer\BuildBattle;

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
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

class Main extends PluginBase implements Listener{
  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    $this->getServer()->getScheduler()->scheduleRepeatingTask(new Particles($this), 1);
    $this->getServer()->getScheduler()->scheduleRepeatingTask(new GameTask($this), 20);
    $this->getLogger()->info(C::GREEN . "Enabled!");
    @mkdir($this->getDataFolder());
    		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
    		$arenas = $config->get("arenas");
		foreach($arenas as $lev)
		{
			$this->getServer()->loadLevel($lev);
		}
		$config->save();
  }
  public function onBreak(BlockBreakEvent $event){
  	if($event->getBlock()->getId() == 5){
  		$event->getPlayer()->sendMessage(C::YELLOW . C::BOLD . "You can't leave the arena!");
  		$event->setCancelled(true);
  	}
  	if($event->getBlock()->getId() == 44){
  		$event->setCancelled(true);
  		$event->getPlayer()->sendMessage(C::YELLOW . C::BOLD . "You can't leave the arena!");
  	}
  }
  public function onChat(PlayerChatEvent $event){
  	$player = $event->getPlayer();
  	$event->setRecipients($player->getLevel()->getPlayers());
  }
  public function onPlace(BlockPlaceEvent $event){
  	if($event->getBlock()->getId() == 10){
  		$event->setCancelled(true);
  		$event->getPlayer()->sendMessage(C::RED . C::BOLD . "No Griefing!");
  	}
  	if($event->getBlock()->getId() == 46){
  		$event->setCancelled(true);
  		$event->getPlayer()->sendMessage(C::RED . C::BOLD . "No Griefing!");
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
		          $s->sendMessage(C::BLUE."Preparing BuildBattlePE level!");
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
					$x = $t->x;
					$y = $t->y;
					$z = $t->z;
					$level->addParticle(new HeartParticle(new Vector3($x, $y, $z))); 
					$level->setBlock(new Vector3($x,$y,$z), Block::get(0));
				}
			}
		}
	}
}
class GameTask extends PluginTask {
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
  
	public function onRun($tick)
	{
		$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
		$arenas = $config->get("arenas");
		if(!empty($arenas))
		{
			foreach($arenas as $arena)
			{
				$time = $config->get($arena . "PlayTime");
				$timeToStart = $config->get($arena . "StartTime");
				$levelArena = $this->plugin->getServer()->getLevelByName($arena);
				if($levelArena instanceof Level)
				{
					$playersArena = $levelArena->getPlayers();
					if(count($playersArena)==0)
					{
						$config->set($arena . "PlayTime", 780);
						$config->set($arena . "StartTime", 30);
					}
					else
					{
						if(count($playersArena)>=5)
						{
							if($timeToStart>0)
							{
								$timeToStart--;
								foreach($playersArena as $pl)
								{
									$pl->sendPopup(C::GRAY . "Starting in " . $timeToStart . " Seconds");
								}
								if($timeToStart == 30 || $timeToStart == 25 || $timeToStart == 15 || $timeToStart == 10 || $timeToStart ==5 || $timeToStart ==4 || $timeToStart ==3 || $timeToStart ==2 || $timeToStart ==1)
								{
									foreach($playersArena as $pl)
									{
										$pl->sendMessage($timeToStart . " Seconds until Start");
									}
								        $config->set($arena . "StartTime", $timeToStart);
							        }
								if($timeToStart<=0)
								{

									foreach($playersArena as $pl)
									{
									$theme = $this->plugin->getTheme();
									$p1->sendMessage(C::YELLOW . C::BOLD . "The game has started!");
                                                                        $p1->sendPopup(C::YELLOW . C::BOLD . "Theme: " . $theme);
								         }
								$config->set($arena . "StartTime", $timeToStart);
							}
							else
							{
								$aop = count($levelArena->getPlayers());
								if($aop==1)
								{
									foreach($playersArena as $pl)
									{
										$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
										$this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
										$pl->teleport($spawn,0,0);
										$p1->setGamemode(1);
									}
									$config->set($arena . "PlayTime", 780);
									$config->set($arena . "StartTime", 30);
								}
								$time--;
								if($time>=180)
								{
								$time2 = $time - 180;
								$minutes = $time2 / 60;
									foreach($playersArena as $pl)
									{
										$pl->sendPopup(C::YELLOW . C::BOLD . $time2 . " left in the game!");
									}
								if($time2 <= 0)
								{
									$spawn = $levelArena->getSafeSpawn();
									$levelArena->loadChunk($spawn->getX(), $spawn->getZ());
									foreach($playersArena as $pl)
									{
										$pl->teleport($spawn,0,0);
									}
								}
								}
								else
								{
									$minutes = $time / 60;
									if(is_int($minutes) && $minutes>0)
									{
										foreach($playersArena as $pl)
										{
											$pl->sendMessage(C::YELLOW . C::BOLD . $minutes . " minutes remaining!");
										}
									}
									else if($time == 30 || $time == 15 || $time == 10 || $time ==5 || $time ==4 || $time ==3 || $time ==2 || $time ==1)
									{
										foreach($playersArena as $pl)
										{
											$pl->sendMessage($time . " seconds remaining");
										}
									}
									if($time <= 780)
									{
									}
	
									if($time <= 0)
									{
										$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
										$this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
										foreach($playersArena as $pl)
										{
											$pl->teleport($spawn,0,0);
											$pl->setGamemode(1);
										}
										$time = 780;
									}
								}
								$config->set($arena . "PlayTime", $time);
							}
						}
						else
						{
							if($timeToStart<=0)
							{
								foreach($playersArena as $pl)
								{
								        $player->setNameTagVisible(true);
                                                                        $pl->sendTip(C::YELLOW . C::BOLD . "You won the match!");
									$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
									$this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
									$pl->teleport($spawn,0,0);
									foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
									$p->sendMessage($this->prefix . C::GRAY . $name . " Has won a BuildBattlePE match!");
									}
								}
								$config->set($arena . "PlayTime", 780);
								$config->set($arena . "StartTime", 30);
							}
							else
							{
								foreach($playersArena as $pl)
								{
								$pl->sendPopup(C::YELLOW . C::BOLD . "A game requires 5 players!");
								
								}
								$config->set($arena . "PlayTime", 780);
								$config->set($arena . "StartTime", 30);
							}
						}
					}
				}
			}
		}
		$config->save();
	}
	}
}
