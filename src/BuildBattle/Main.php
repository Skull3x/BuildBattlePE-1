
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
}
