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

class EShopCommand extends Command {
    private $plugin;
    private $enchantments = [];

    public function __construct(Plugin $plugin) {
        parent::__construct("eshop", "Open the Enchantment Shop");
        $this->setPermission("enchantmentshop.cmd");
        $this->plugin = $plugin;
        $this->loadEnchantments(); // Load enchantments from the config file in the constructor.
    }

    private function loadEnchantments() {
        $config = new Config($this->plugin->getDataFolder() . "Shop.yml", Config::YAML);
        $enchantmentData = $config->get("enchantments", []);

        foreach ($enchantmentData as $enchantment) {
            if (isset($enchantment["name"], $enchantment["price"])) {
                $this->enchantments[] = [
                    "name" => $enchantment["name"],
                    "price" => (float) $enchantment["price"]
                ];
            }
        }
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if ($sender instanceof Player) {
            $form = new SimpleForm(function (Player $player, ?int $data) use ($args) {
                if ($data === null) {
                    return;
                }

                if (isset($this->enchantments[$data])) {
                    $selectedEnchantment = $this->enchantments[$data];
                    $enchantmentName = $selectedEnchantment["name"];
                    $enchantmentPrice = $selectedEnchantment["price"];

                    if (isset($args[0]) && is_numeric($args[0]) && $args[0] > 0) {
                        $amount = (float) $args[0];
                        $this->applyEnchantment($player, $enchantmentName, $selectedEnchantment, $amount);
                    } else {
                        $player->sendMessage("Invalid amount provided. Please enter a valid numeric value greater than 0.");
                    }
                }
            });

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

    private function applyEnchantment(Player $player, string $enchantmentName, array $selectedEnchantment, float $amount) {
        $item = $player->getInventory()->getItemInHand();

        $enchantment = StringToEnchantmentParser::getInstance()->parse($selectedEnchantment["name"]);

        if ($enchantment !== null) {
            $enchantInstance = EnchantInstance::getEnchantInstance($enchantment, 1);

            if ($enchantInstance !== null) {
                $item->addEnchantment($enchantInstance);
                $player->getInventory()->setItemInHand($item);
                $player->sendMessage("You applied $enchantmentName to your item.");
            } else {
                $player->sendMessage("Failed to apply the enchantment. Please try again later.");
            }
        } else {
            $player->sendMessage("Invalid enchantment selected. Please try again.");
        }
    }
}
