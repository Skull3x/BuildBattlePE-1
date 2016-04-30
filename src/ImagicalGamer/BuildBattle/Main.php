<?php
namespace ImagicalGamer\BuildBattle;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\Listener;
use pocketmine\level\sound\TNTPrimeSound;
use pocketmine\level\sound\PopSound;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat as C;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\tile\Sign;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\entity\Effect;
use pocketmine\event\entity\EntityLevelChangeEvent ; 
use pocketmine\tile\Chest;
use pocketmine\inventory\ChestInventory;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\entity\Entity;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class Main extends PluginBase implements Listener {
	
    public $prefix = C::YELLOW . C::BOLD . "[BuildBattle] " . C::RESET . C::WHITE;
	public $mode = 0;
	public $arenas = array();
	public $currentLevel = "";
	
	public function onEnable()
	{
	    $this->getServer()->setAutoSave(false);
        $this->getServer()->getPluginManager()->registerEvents($this ,$this);
		$this->getLogger()->info(C::YELLOW . " Enabled!");
		$this->saveResource("rank.yml");
		$this->saveResource("config.yml");
		@mkdir($this->getDataFolder());
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		if($config->get("arenas")!=null)
		{
			$this->arenas = $config->get("arenas");
		}
		foreach($this->arenas as $lev)
		{
			$this->getServer()->loadLevel($lev);
		}
		$config->save();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new GameTask($this), 20);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), 10);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new ParticleSigns($this), 1);
	}
	public function onMove(PlayerMoveEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$sofar = $config->get($level . "StartTime");
			if($sofar > 0)
			{
				$f = $event->getFrom();
                                $t = $event->getTo();
                                if($f->x != $t->x or $f->y != $t->x or $f->z != $t->z){
                                   $event->setCancelled();
                       }
		}
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
	
	public function onCommand(CommandSender $player, Command $cmd, $label, array $args) {
        switch($cmd->getName()){
			case "bb":
				if($player->isOp())
				{
					if(!empty($args[0]))
                                       
					{
						if($args[0]=="create")
						{
							if(!empty($args[1]))
							{
								if(file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1]))
								{
									$this->getServer()->loadLevel($args[1]);
									$this->getServer()->getLevelByName($args[1])->loadChunk($this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorX(), $this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorZ());
									array_push($this->arenas,$args[1]);
									$this->currentLevel = $args[1];
									$this->mode = 1;
									$player->sendMessage($this->prefix . "You are about to register an match. Tap a block to set a spawn point there!");
									$player->setGamemode(1);
									$player->teleport($this->getServer()->getLevelByName($args[1])->getSafeSpawn(),0,0);
								}
								else
								{
									$player->sendMessage($this->prefix . "There is no world with this name.");
								}
							}
							else
							{
                                             $player->sendMessage($this->prefix . "BuildBattle Commands!");
                                             $player->sendMessage($this->prefix . "/bb create <world> Creates an arena in the specified world!");
							}
						}
						else
						{
							$player->sendMessage($this->prefix . "There is no such command.");
						}
					}
					else
					{
                                             $player->sendMessage($this->prefix . "BuildBattle Commands!");
                                             $player->sendMessage($this->prefix . "/bb create <world> Creates an arena in the specified world!");
					}
				}
		
	}
}	
  public function onChat(PlayerChatEvent $event){
  	$player = $event->getPlayer();
  	$event->setRecipients($player->getLevel()->getPlayers());
  }
	
	public function onInteract(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$tile = $player->getLevel()->getTile($block);
		
		if($tile instanceof Sign) 
		{
			if($this->mode==12)
			{
				$tile->setText("§b§l[Join]","§f0 §c/ §f10",$this->currentLevel,$this->prefix);
				$this->refreshArenas();
				$this->currentLevel = "";
				$this->mode = 0;
				$player->sendMessage($this->prefix . "The arena has been registered successfully!");
			}
			else
			{
				$text = $tile->getText();
				if($text[3] == $this->prefix)
				{
					if($text[0]=="§b§l[Join]")
					{
						$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
						$level = $this->getServer()->getLevelByName($text[2]);
						$aop = count($level->getPlayers());
						$thespawn = $config->get($text[2] . "Spawn" . ($aop+1));
						$spawn = new Position($thespawn[0]+0.5,$thespawn[1],$thespawn[2]+0.5,$level);
						$level->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
						$player->teleport($spawn,0,0);
						$player->setGamemode(1);
						$player->setNameTag(C::BOLD . C::RED . $player->getName());
						$player->getInventory()->clearAll();
						$player->setNameTagVisible(false);
						$player->setGamemode(1);
                        $player->sendMessage($this->prefix . "You have Joined a battle!");	
						$levelplayers = $level->getPlayers();
						if($levelplayers==5){
							   $spawn1 = $this->getServer()->getDefaultLevel()->getSafeSpawn(); 
                               $this->getServer()->getDefaultLevel()->loadChunk($spawn1->getFloorX(), 
                               $spawn1->getFloorZ()); $player->teleport($spawn1,0,0);
							   $player->sendMessage($this->prefix . "The battle is full!");
						}
					}
					else
					{	
					$player->sendMessage($this->prefix . "Theres been an error joining the match! Please contact an Admin");			
					}
					
				}
			}
		}
		else if($this->mode>=1&&$this->mode<=10)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$config->set($this->currentLevel . "Spawn" . $this->mode, array($block->getX(),$block->getY()+1,$block->getZ()));
			$player->sendMessage($this->prefix . "Spawn " . $this->mode . " has been registered!");
			$this->mode++;
			if($this->mode==11)
			{
				$player->sendMessage($this->prefix . "Now tap on a lobby spawn.");
			}
			$config->save();
		}
		else if($this->mode==11)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$level = $this->getServer()->getLevelByName($this->currentLevel);
			$level->setSpawn = (new Vector3($block->getX(),$block->getY()+1,$block->getZ()));
			$config->set("arenas",$this->arenas);
			$player->sendMessage($this->prefix . "You've been teleported back. Tap a sign to register it for the arena!");
			$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
			$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
			$player->teleport($spawn,0,0);
			$config->save();
			$this->mode=12;
		}
	}
	
	public function refreshArenas()
	{
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		$config->set("arenas",$this->arenas);
		foreach($this->arenas as $arena)
		{
			$config->set($arena . "PlayTime", 780);
			$config->set($arena . "StartTime", 60);
			$config->set($arena . "VoteTime", 120);
		}
		$config->save();
	}
	
	public function onDisable()
	{
		$this->saveResource("config.yml");
		$this->saveResource("rank.yml");
	}
}
class RefreshSigns extends PluginTask {
    public $prefix = C::YELLOW . C::BOLD . "[BuildBattle] " . C::RESET . C::WHITE;
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
  
	public function onRun($tick)
	{
		$allplayers = $this->plugin->getServer()->getOnlinePlayers();
		$level = $this->plugin->getServer()->getDefaultLevel();
		$tiles = $level->getTiles();
		foreach($tiles as $t) {
			if($t instanceof Sign) {	
				$text = $t->getText();
				if($text[3]==$this->prefix)
				{
					$aop = 0;
					foreach($allplayers as $player){if($player->getLevel()->getFolderName()==$text[2]){$aop=$aop+1;}}
					$ingame = "§b§l[Join]";
					$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
					if($config->get($text[2] . "PlayTime")!=780)
					{
						$ingame = "§l§a[Running]";
					}
					else if($aop>=24)
					{
						$ingame = "§l§c[Full]";
					}
					$t->setText($ingame,"§c" . $aop . " §f/§c 10",$text[2],$this->prefix);
				}
			}
		}
	}
}
class GameTask extends PluginTask {
	
    public $prefix = C::YELLOW . C::BOLD . "[BuildBattle] " . C::RESET . C::WHITE;
    
	public function __construct($plugin){
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
  
	public function onRun($tick){
		$config = new Config($this->getDataFolder()."/config.yml");
		$arenas = $config->get("arenas");
		if(!empty($arenas)){
			foreach($arenas as $arena){
				$time = $config->get($arena . "PlayTime");
				$wait = $config->get($arena . "StartTime");
				$vote = $config->get($arena . "VoteTime");
				$level = $this->plugin->getServer()->getLevelByName($arena);
				if($level instanceof Level){
					$players = $level->getPlayers();
					if(count($players) == 0){
						$config->set($arena . "PlayTime", 780);
						$config->set($arena . "StartTime", 60);
						$config->get($arena . "VoteTime", 120);
					}else{
						if(count($players) > 2){
							if($wait > 0){
								
								$wait--;
								foreach($players as $p){
									$p->sendPopup(C::YELLOW . C::BOLD . "Starting in " . $wait . " Seconds");
									$level = $p->getLevel();
									$level->addSound(new PopSound($p));
									$config->set($arena . "StartTime", $wait);
								}
								if($wait <= 0){
									foreach($players as $p){
										$level = $p->getLevel();
										$level->addSound(new TNTPrimeSound($p));
                                                                        	$p->sendMessage("§b-------------------------------§r");
                                                                        	$p->sendMessage($this->prefix . C::GRAY . "Start" . C::RED . C::BOLD . " Building!");
                                                                        	$p->sendMessage("§b-------------------------------§r");
									}
								}
							}
						}
					}
				}
			}
		}
	}
	
}
	
class ParticleSigns extends PluginTask {
  
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
  
	public function onRun($tick)
	{
		$level = $this->plugin->getServer()->getLevelByName("BuildBattlePE");
		$tiles = $level->getTiles();
		$heart = "[Heart]";
		foreach($tiles as $t) {
			if($t instanceof Sign) {	
				$text = $t->getText();
				$world = $text[1];
				if($text[0]==$heart)
				{
	          			$x = $t->x;
					$y = $t->y;
					$z = $t->x;
					$level->addParticle(new HeartParticle(new Vector3($x+1, $y, $z+1)));
					$level->addParticle(new HeartParticle(new Vector3($x-1, $y, $z-1)));
					$level->setBlock(new Vector3($x,$y,$z), Block::get(0));
				}
			}
		}
	}
}
