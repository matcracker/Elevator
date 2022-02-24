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

use pocketmine\block\Air;
use pocketmine\block\BaseSign;
use pocketmine\block\utils\SignText;
use pocketmine\block\WallSign;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use UnexpectedValueException;
use function mb_strtolower;

final class EventListener implements Listener{

	public function onSignChange(SignChangeEvent $event) : void{
		$text = $event->getSign()->getText();
		$lines = $text->getLines();

		//Adjust sign lift text.
		$lineIndex = Main::getSignLine();
		$line = TextFormat::clean(TextFormat::colorize(mb_strtolower($lines[$lineIndex])));

		if($line === mb_strtolower(Main::getSignUpText(true))){
			$line = Main::getSignUpText();
		}elseif($line === mb_strtolower(Main::getSignDownText(true))){
			$line = Main::getSignDownText();
		}else{
			return;
		}

		$player = $event->getPlayer();

		if(!$player->hasPermission("elevator.sign.create")){
			$player->sendMessage(Main::getMsgCreateDeny());

			return;
		}

		//Correct the line
		$lines[$lineIndex] = $line;

		$event->setNewText(new SignText($lines));
		$player->sendMessage(Main::getMsgCreateSuccess());
	}

	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			return;
		}

		$sign = $event->getBlock();
		if(!$sign instanceof WallSign){
			return;
		}

		$blockPos = $sign->getPosition();
		$world = $blockPos->getWorld();

		$x = (int) $blockPos->getX();
		$y = (int) $blockPos->getY();
		$z = (int) $blockPos->getZ();

		if(!self::isLiftSign($x, $y, $z, $world)){
			return;
		}

		$event->cancel();

		$player = $event->getPlayer();
		if(!$player->hasPermission("elevator.sign.use")){
			$player->sendMessage(Main::getMsgUseDeny());

			return;
		}

		$text = $sign->getText();

		$line = TextFormat::clean($text->getLine(Main::getSignLine()));
		$maxY = $world->getMaxY();
		$found = false;

		if($up = ($line === Main::getSignUpText(true))){
			$y++;
			for(; $y <= $maxY; $y++){
				if($found = self::isLiftSign($x, $y, $z, $world)){
					break;
				}
			}
		}elseif($line === Main::getSignDownText(true)){
			$y--;
			$mixY = $world->getMinY();
			for(; $y >= $mixY; $y--){
				if($found = self::isLiftSign($x, $y, $z, $world)){
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
				$ground = $world->getBlockAt($x, $y, $z);
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

	public static function isLiftSign(int $x, int $y, int $z, World $world) : bool{
		$sign = $world->getBlockAt($x, $y, $z);

		if(!$sign instanceof BaseSign){
			return false;
		}

		$line = TextFormat::clean($sign->getText()->getLine(Main::getSignLine()));

		return $line === Main::getSignUpText(true) || $line === Main::getSignDownText(true);
	}

	private static function getCenterBlock(Vector3 $vector3) : Vector3{
		return $vector3->add(0.5, 0, 0.5);
	}
}