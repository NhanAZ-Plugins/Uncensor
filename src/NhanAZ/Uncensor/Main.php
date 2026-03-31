<?php

namespace NhanAZ\Uncensor;

use NhanAZ\libRegRsp\libRegRsp;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener {

	/** @var string[] */
	private array $words = [];
	/** @var string|null */
	private ?string $regex = null;

	public function onEnable(): void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$wordListPath = $this->getDataFolder() . "profanity_filter.wlist";
		if (!is_file($wordListPath)) {
			$this->saveResource("profanity_filter.wlist");
		}

		$this->words = file($wordListPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

		if (empty($this->words)) {
			$this->getLogger()->warning("profanity_filter.wlist is empty; Uncensor will stay idle.");
			return;
		}

		$this->regex = '/(' . implode('|', array_map('preg_quote', $this->words)) . ')/iu';

		libRegRsp::compileAndRegister($this, 'Uncensor');
	}

	protected function onDisable(): void {
		libRegRsp::unregister($this);
	}

	private function unfilter(string $message): string {
		if ($this->regex === null) {
			return $message;
		}
		return preg_replace_callback($this->regex, function (array $matches): string {
			return mb_substr($matches[0], 0, 1) . "\u{FE00}" . mb_substr($matches[0], 1);
		}, $message) ?? $message;
	}

	public function onDataPacketSend(DataPacketSendEvent $event): void {
		$packets = $event->getPackets();

		foreach ($packets as $pk) {
			if (!($pk instanceof TextPacket)) continue;
			if ($pk->type === TextPacket::TYPE_TRANSLATION) continue;

			$pk->message = $this->unfilter($pk->message);

			foreach ($pk->parameters as $k => $param) {
				$pk->parameters[$k] = $this->unfilter($param);
			}
		}
	}

	public function onPlayerJoin(PlayerJoinEvent $event): void {
		$player = $event->getPlayer();
		if (empty($this->words)) {
			return;
		}
		$player->sendMessage("§cUncensor: §eThe following words are censored:§f " . implode("§8,§f ", $this->words));
	}
}
