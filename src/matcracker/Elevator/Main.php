<?php

declare(strict_types=1);

namespace matcracker\Elevator;

use pocketmine\block\SignPost;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;
use UnexpectedValueException;
use function floor;
use function strtolower;

class Main extends PluginBase implements Listener{

	public const LIFT_UP = "[Lift Up]";
	public const LIFT_DOWN = "[Lift Down]";

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onSignChange(SignChangeEvent $event) : void{
		$player = $event->getPlayer();
		if(!$player->hasPermission("elevator.sign.create")){
			$player->sendMessage(TextFormat::RED . "You don't have permission to create an elevator sign.");

			return;
		}

		//Adjust sign lift text.
		$line = TextFormat::clean(strtolower($event->getLine(1)));

		if($line === strtolower(self::LIFT_UP)){
			$line = self::LIFT_UP;
		}elseif($line === strtolower(self::LIFT_DOWN)){
			$line = self::LIFT_DOWN;
		}else{
			return;
		}

		$event->setLine(1, TextFormat::DARK_BLUE . $line);
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
			$player->sendMessage(TextFormat::RED . "You don't have permission to use the elevator sign.");

			return;
		}

		$line = TextFormat::clean($clickedSign->getLine(1));

		$teleportPos = null;
		$maxY = $level->getWorldHeight();

		if($up = ($line === self::LIFT_UP)){
			$y++;
			for(; $y <= $maxY; $y++){
				if(self::isLiftSign($x, $y, $z, $level) !== null){
					$teleportPos = new Vector3($x, $y, $z);
					break;
				}
			}
		}elseif($line === self::LIFT_DOWN){
			$y--;
			for(; $y >= 0; $y--){
				if(self::isLiftSign($x, $y, $z, $level)){
					$teleportPos = new Vector3($x, $y, $z);
					break;
				}
			}
		}else{
			throw new UnexpectedValueException("What?");
		}

		if($teleportPos !== null){
			$player->sendMessage(TextFormat::GREEN . "Teleporting " . ($up ? "up" : "down") . ".");
			$player->teleport(self::getCenterBlock($teleportPos));
		}else{
			$player->sendMessage(TextFormat::RED . "Could not find an elevator destination.");
		}
	}

	public static function isLiftSign(int $x, int $y, int $z, Level $level) : ?Sign{
		$liftDest = $level->getTileAt($x, $y, $z);
		if(!$liftDest instanceof Sign){
			return null;
		}

		$line = TextFormat::clean($liftDest->getLine(1));

		if($line !== self::LIFT_UP && $line !== self::LIFT_DOWN){
			return null;
		}

		return $liftDest;
	}

	private static function getCenterBlock(Vector3 $vector3) : Vector3{
		$x = $vector3->getX() ? 0.5 : -0.5;
		$z = $vector3->getZ() ? 0.5 : -0.5;

		return $vector3->add($x, 0, $z);
	}
}