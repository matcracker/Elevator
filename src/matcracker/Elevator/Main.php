<?php

/*
 * Elevator
 *
 * Copyright (C) 2020-2022
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author matcracker
 * @link https://www.github.com/matcracker/Elevator
 *
*/

declare(strict_types=1);

namespace matcracker\Elevator;

use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use function mkdir;
use function strtolower;

final class Main extends PluginBase{

	private static array $configData = [];

	public static function getSignLine() : int{
		return (int) self::$configData["signs"]["line"];
	}

	public static function getSignUpText(bool $clean = false) : string{
		return self::getSignText("up-text", $clean);
	}

	private static function getSignText(string $sign, bool $clean = false) : string{
		$text = TextFormat::colorize(self::$configData["signs"][$sign]);

		if($clean){
			return TextFormat::clean($text);
		}

		return $text;
	}

	public static function getSignDownText(bool $clean = false) : string{
		return self::getSignText("down-text", $clean);
	}

	public static function getMsgCreateDeny() : string{
		return TextFormat::colorize(self::$configData["messages"]["sign-create-deny"]);
	}

	public static function getMsgCreateSuccess() : string{
		return TextFormat::colorize(self::$configData["messages"]["sign-create-success"]);
	}

	public static function getMsgUseDeny() : string{
		return TextFormat::colorize(self::$configData["messages"]["sign-use-deny"]);
	}

	public static function getMsgNoDestination() : string{
		return TextFormat::colorize(self::$configData["messages"]["no-destination"]);
	}

	public static function getMsgTeleportUp() : string{
		return TextFormat::colorize(self::$configData["messages"]["teleport-up"]);
	}

	public static function getMsgTeleportDown() : string{
		return TextFormat::colorize(self::$configData["messages"]["teleport-down"]);
	}

	public static function getMsgElevatorNotSafe() : string{
		return TextFormat::colorize(self::$configData["messages"]["elevator-not-safe"]);
	}

	public function onLoad() : void{
		@mkdir($this->getDataFolder());
		self::$configData = $this->getConfig()->getAll();
	}

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
		UpdateNotifier::checkUpdate($this->getName(), $this->getDescription()->getVersion());
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(strtolower($command->getName()) !== "elevator"){
			return false;
		}

		if(!$command->testPermission($sender)){
			return false;
		}

		if(!isset($args[0])){
			return false;
		}

		if(strtolower($args[0]) === "reload"){
			if(!$sender->hasPermission("elevator.command.reload")){
				$sender->sendMessage($command->getPermissionMessage() ?? $this->getServer()->getLanguage()->translateString(TextFormat::RED . "%commands.generic.permission"));

				return false;
			}
			$this->reloadPlugin();
			$sender->sendMessage(TextFormat::GREEN . "Plugin successfully reloaded.");

			return true;
		}

		return false;
	}

	private function reloadPlugin() : void{
		$this->reloadConfig();
		self::$configData = $this->getConfig()->getAll();
	}
}