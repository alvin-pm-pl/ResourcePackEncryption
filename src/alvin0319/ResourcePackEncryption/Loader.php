<?php

declare(strict_types=1);

namespace alvin0319\ResourcePackEncryption;

use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackInfoEntry;
use pocketmine\plugin\PluginBase;

final class Loader extends PluginBase{

	/** @var string[] */
	private array $encryptionKeys = [];

	protected function onEnable() : void{
		$this->saveDefaultConfig();
		foreach($this->getServer()->getResourcePackManager()->getResourceStack() as $resourcePack){
			$uuid = $resourcePack->getPackId();
			if($this->getConfig()->getNested("resource-packs.{$uuid}", "") !== ""){
				$encryptionKey = $this->getConfig()->getNested("resource-packs.{$uuid}");
				$this->encryptionKeys[$uuid] = $encryptionKey;
				$this->getLogger()->debug("Loaded encryption key for resource pack $uuid");
			}
		}
		$this->getServer()->getPluginManager()->registerEvent(DataPacketSendEvent::class, function(DataPacketSendEvent $event) : void{
			$packets = $event->getPackets();
			foreach($packets as $packet){
				if($packet instanceof ResourcePacksInfoPacket){
					foreach($packet->resourcePackEntries as $index => $entry){
						if(isset($this->encryptionKeys[$entry->getPackId()])){
							$contentId = $this->encryptionKeys[$entry->getPackId()];
							$packet->resourcePackEntries[$index] = new ResourcePackInfoEntry($entry->getPackId(), $entry->getVersion(), $entry->getSizeBytes(), $contentId, $entry->getSubPackName(), $entry->getPackId(), $entry->hasScripts(), $entry->isRtxCapable());
						}
					}
				}
			}
		}, EventPriority::HIGHEST, $this);
	}
}
