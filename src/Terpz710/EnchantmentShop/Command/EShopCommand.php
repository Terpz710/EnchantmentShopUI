<?php

declare(strict_types=1);

namespace Terpz710\EnchantmentShop\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\enchantment\EnchantInstance;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use davidglitch04\libEco\libEco;

class EShopCommand extends Command {
    private $plugin;
    private $enchantments = [];
    private $libEco;

    public function __construct(Plugin $plugin, libEco $libEco) {
        parent::__construct("eshop", "Open the Enchantment Shop");
        $this->setPermission("enchantmentshop.cmd");
        $this->plugin = $plugin;
        $this->libEco = $libEco;
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if ($sender instanceof Player) {
            $form = new SimpleForm(function (Player $player, ?int $data) {
                if ($data === null) {
                    return;
                }

                if (isset($this->enchantments[$data])) {
                    $selectedEnchantment = $this->enchantments[$data];
                    $enchantmentName = $selectedEnchantment["name"];
                    $enchantmentPrice = $selectedEnchantment["price"];

                    if ($this->libEco->isInstall()) {
                        $this->libEco->myMoney($player, function ($money) use ($player, $enchantmentName, $enchantmentPrice, $selectedEnchantment) {
                            if ($money >= $enchantmentPrice) {
                                $levelForm = new CustomForm(function (Player $player, array $data) use ($enchantmentName, $selectedEnchantment) {
                                    if (isset($data[0])) {
                                        $selectedLevel = (int)$data[0];
                                        $this->applyEnchantment($player, $enchantmentName, $selectedEnchantment, $selectedLevel, $enchantmentPrice);
                                    }
                                });
                                $levelForm->setTitle("Enchantment Level");
                                $levelForm->addLabel("Select the level for $enchantmentName:");
                                $levelForm->addSlider("Level", 1, 10, 1, 1);
                                $player->sendForm($levelForm);
                            } else {
                                $player->sendMessage("You don't have enough money to purchase $enchantmentName.");
                            }
                        });
                    } else {
                        $player->sendMessage("Economy plugin is not installed. Unable to make purchases.");
                    }
                }
            });

            $config = new Config($this->plugin->getDataFolder() . "Shop.yml", Config::YAML);
            $enchantmentData = $config->get("enchantments", []);

            foreach ($enchantmentData as $enchantment) {
                if (isset($enchantment["name"], $enchantment["price"])) {
                    $this->enchantments[] = [
                        "name" => $enchantment["name"],
                        "price" => (float)$enchantment["price"]
                    ];
                }
            }

            $form->setTitle("Enchantment Shop");
            $form->setContent("Choose an enchantment to purchase:");
            foreach ($this->enchantments as $enchantment) {
                $enchantmentName = $enchantment["name"];
                $form->addButton("$enchantmentName - {$enchantment["price"]} coins");
            }

            $sender->sendForm($form);
        } else {
            $sender->sendMessage("You must run this command in-game.");
        }

        return true;
    }

    private function applyEnchantment(Player $player, string $enchantmentName, array $selectedEnchantment, int $selectedLevel, float $enchantmentPrice) {

        $item = $player->getInventory()->getItemInHand();
        $enchantment = StringToEnchantmentParser::getInstance()->parse($enchantmentName);
        $enchantInstance = EnchantInstance::getEnchantInstance($enchantment, $selectedLevel);

        if ($enchantInstance !== null) {
            $item->addEnchantment($enchantInstance);
            $player->getInventory()->setItemInHand($item);

            $this->libEco->reduceMoney($player, $enchantmentPrice, function ($success) use ($player, $enchantmentName) {
                if ($success) {
                    $player->sendMessage("You purchased $enchantmentName for {$enchantmentPrice} coins.");
                } else {
                    $player->sendMessage("Failed to purchase $enchantmentName. Please try again later.");
                }
            });
        } else {
            $player->sendMessage("Failed to apply the enchantment. Please try again later.");
        }
    }
}
