
<?php
namespace ImagicalGamer\BuidBattle;

use pocketmine\event\Listener;

use pocketmine\plugin\PluginBase;
use pocketmine\plugin\Plugin;

use pocketmine\scheduler\PluginTask;

use pocketmine\Server;
use pocketmine\Player;

use pocketmine\utils\TextFormat as C;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener{
  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    $this->getLogger()->info(C::GREEN . "Enabled!");
  }
  
<?php
namespace ImagicalGamer\SwaggyHUD;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\utils\TextFormat as C;
use pocketmine\utils\Config;
class Main extends PluginBase implements Listener{
  public function onEnable(){
    $this->saveDefaultConfig();
    $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
    $format = $config->get("Format");
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    $this->getLogger()->info(C::GREEN . "Enabled!");
    $this->getLogger()->notice(C::AQUA . "Message Format: " . $format);
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
