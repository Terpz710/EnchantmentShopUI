<?php

declare(strict_types=1);

namespace Terpz710\EnchantmentShop;

use pocketmine\plugin\PluginBase;
use Terpz710\EnchantmentShop\Command\EShopCommand;
use davidglitch04\libEco\libEco;

class Main extends PluginBase {

    public function onEnable(): void {
        $libEco = new libEco();

        if ($libEco->isInstall()) {
            $this->getLogger()->info("libEco is installed and working.");
        } else {
            $this->getLogger()->error("libEco is not properly installed or configured.");
            return;
        }
        $this->saveDefaultConfig();
        $this->saveResource("Shop.yml");
        $this->getServer()->getCommandMap()->register("eshop", new EShopCommand($this, $libEco));
    }
}
