<?php

declare(strict_types=1);

namespace Terpz710\EnchantmentShop;

use pocketmine\plugin\PluginBase;
use Terpz710\EnchantmentShop\Command\EShopCommand;
use davidglitch04\libEco\libEco;

class Main extends PluginBase {

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->saveResource("Shop.yml");
        $libEco = new libEco();
        $this->getServer()->getCommandMap()->register("eshop", new EShopCommand($this, $libEco));
    }
}
