<?php

declare(strict_types=1);

namespace Terpz710\EnchantmentShop\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Armor;
use pocketmine\item\Tool;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;

class EShopCommand extends Command {
    private $plugin;
    private $enchantments = [];
    private $globalLevel;

    public function __construct(Plugin $plugin) {
        parent::__construct("eshop", "Open the Enchantment Shop");
        $this->setPermission("enchantmentshop.cmd");
        $this->plugin = $plugin;
        $this->loadEnchantments();
    }

    private function loadEnchantments() {
        $config = new Config($this->plugin->getDataFolder() . "Shop.yml", Config::YAML);

        $this->globalLevel = $config->get("level", 10);

        $enchantmentData = $config->get("enchantments", []);

        foreach ($enchantmentData as $enchantment) {
            if (isset($enchantment["name"], $enchantment["button"])) {
                $this->enchantments[] = [
                    "name" => $enchantment["name"],
                    "button" => $enchantment["button"],
                ];
            }
        }
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

                    $this->showLevelSelectionUI($player, $enchantmentName, $this->globalLevel);
                }
            });

            $form->setTitle("Enchantment Shop");
            $form->setContent("Choose an enchantment to apply:");
            foreach ($this->enchantments as $key => $enchantment) {
                $enchantmentName = $enchantment["button"];
                $form->addButton($enchantmentName);
            }

            $sender->sendForm($form);
        } else {
            $sender->sendMessage("You must run this command in-game.");
        }

        return true;
    }

    public function showLevelSelectionUI(Player $player, string $enchantmentName, int $defaultLevel) {
        $form = new CustomForm(function (Player $player, ?array $data) use ($enchantmentName) {
            if ($data !== null && isset($data[0])) {
                $selectedLevel = (int)$data[0];
                $this->confirmEnchantmentUI($player, $enchantmentName, $selectedLevel);
            }
        });

        $form->setTitle("Enchantment Level");
        $form->addLabel("Select the level for $enchantmentName:");
        $form->addSlider("Level", 1, 10, 1, $defaultLevel);

        $player->sendForm($form);
    }

    public function confirmEnchantmentUI(Player $player, string $enchantmentName, int $selectedLevel) {
        $confirmationForm = new CustomForm(function (Player $player, ?array $data) use ($enchantmentName, $selectedLevel) {
            if ($data !== null && isset($data[0]) && $data[0] === true) {
                $this->applyEnchantment($player, $enchantmentName, $selectedLevel);
            } else {
                $player->sendMessage("Enchantment canceled.");
            }
        });

        $confirmationForm->setTitle("Confirm Enchantment");
        $confirmationForm->addLabel("Confirm applying $enchantmentName (Level $selectedLevel)?");
        $confirmationForm->addToggle("Confirm", true);

        $player->sendForm($confirmationForm);
    }

    public function applyEnchantment(Player $player, string $enchantmentName, int $level) {
        $item = $player->getInventory()->getItemInHand();

        if ($item instanceof Tool || $item instanceof Armor) {
            $enchantment = StringToEnchantmentParser::getInstance()->parse($enchantmentName);

            if ($enchantment !== null) {
                $enchantmentInstance = new EnchantmentInstance($enchantment, $level);
                $item->addEnchantment($enchantmentInstance);
                $player->getInventory()->setItemInHand($item);
                $player->sendMessage("You applied $enchantmentName (Level $level) to your item.");
            } else {
                $player->sendMessage("Invalid enchantment selected. Please try again.");
            }
        } else {
            $player->sendMessage("You can only enchant tools and armor items.");
        }
    }
}
