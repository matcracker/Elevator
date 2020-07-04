<?php

/*
 * Elevator
 *
 * Copyright (C) 2020
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

use pocketmine\block\Air;
use pocketmine\block\SignPost;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;
use UnexpectedValueException;
use function floor;
use function strtolower;

final class EventListener implements Listener{

	public function onSignChange(SignChangeEvent $event) : void{
		//Adjust sign lift text.
		$lineIndex = Main::getSignLine();
		$line = TextFormat::clean(TextFormat::colorize(strtolower($event->getLine($lineIndex))));

		if($line === strtolower(Main::getSignUpText(true))){
			$line = Main::getSignUpText();
		}elseif($line === strtolower(Main::getSignDownText(true))){
			$line = Main::getSignDownText();
		}else{
			return;
		}

		$player = $event->getPlayer();

		if(!$player->hasPermission("elevator.sign.create")){
			$player->sendMessage(Main::getMsgCreateDeny());

			return;
		}

		$event->setLine($lineIndex, $line);
		$player->sendMessage(Main::getMsgCreateSuccess());
	}

	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			return;
		}

		$block = $event->getBlock();
		if(!$block instanceof SignPost){
			return;
		}

		$level = $block->getLevel();
		if($level === null){
			return;
		}

		$x = (int) floor($block->getX());
		$y = (int) floor($block->getY());
		$z = (int) floor($block->getZ());

		if(($clickedSign = self::isLiftSign($x, $y, $z, $level)) === null){
			return;
		}

		$event->setCancelled();

		$player = $event->getPlayer();
		if(!$player->hasPermission("elevator.sign.use")){
			$player->sendMessage(Main::getMsgUseDeny());

			return;
		}

		$line = TextFormat::clean($clickedSign->getLine(Main::getSignLine()));
		$maxY = $level->getWorldHeight();
		$found = false;

		if($up = ($line === Main::getSignUpText(true))){
			$y++;
			for(; $y <= $maxY; $y++){
				if($found = (self::isLiftSign($x, $y, $z, $level) !== null)){
					break;
				}
			}
		}elseif($line === Main::getSignDownText(true)){
			$y--;
			for(; $y >= 0; $y--){
				if($found = (self::isLiftSign($x, $y, $z, $level) !== null)){
					break;
				}
			}
		}else{
			throw new UnexpectedValueException("How could you have been here?");
		}

		if($found){
			$y--;
			$safe = false;
			$maxY = $y - 1;
			for(; $y >= $maxY; $y--){
				$ground = $level->getBlockAt($x, $y, $z);
				if($safe = (!$ground instanceof Air)){
					$y++;
					break;
				}
			}

			if($safe){
				$player->sendMessage($up ? Main::getMsgTeleportUp() : Main::getMsgTeleportDown());
				$player->teleport(self::getCenterBlock(new Vector3($x, $y, $z)));
			}else{
				$player->sendMessage(Main::getMsgElevatorNotSafe());
			}

		}else{
			$player->sendMessage(Main::getMsgNoDestination());
		}
	}

	public static function isLiftSign(int $x, int $y, int $z, Level $level) : ?Sign{
		$liftDest = $level->getTileAt($x, $y, $z);
		if(!$liftDest instanceof Sign){
			return null;
		}

		$line = TextFormat::clean($liftDest->getLine(Main::getSignLine()));

		if($line !== Main::getSignUpText(true) && $line !== Main::getSignDownText(true)){
			return null;
		}

		return $liftDest;
	}

	private static function getCenterBlock(Vector3 $vector3) : Vector3{
		return $vector3->add(0.5, 0, 0.5);
	}
}