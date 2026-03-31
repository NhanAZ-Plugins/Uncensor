<?php

namespace NhanAZ\Uncensor;

use NhanAZ\libRegRsp\libRegRsp;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener
{

	/** @var string[] */
	private array $words = [];
	/** @var string|null */
	private ?string $regex = null;

	public function onEnable(): void
	{
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
	}

	/**
	 * Extracts the active Minecraft formatting (last color + accumulated styles) from text.
	 * In Minecraft, a color code (§0-§f) resets all styles; §r resets everything.
	 */
	private function getActiveFormats(string $text): string
	{
		$color = '';
		$styles = [];

		if (preg_match_all('/§([0-9a-fk-or])/iu', $text, $matches)) {
			foreach ($matches[1] as $code) {
				$code = strtolower($code);
				if ($code === 'r') {
					$color = '';
					$styles = [];
				}
				elseif (strpos('0123456789abcdef', $code) !== false) {
					$color = '§' . $code;
					$styles = []; // color resets styles in Minecraft
				}
				else {
					$styles[$code] = true;
				}
			}
		}

		$result = $color;
		foreach (array_keys($styles) as $s) {
			$result .= '§' . $s;
		}
		return $result;
	}

	private function unfilter(string $message): string
	{
		if ($this->regex === null) {
			return $message;
		}

		if (preg_match_all($this->regex, $message, $allMatches, PREG_OFFSET_CAPTURE) === 0) {
			return $message;
		}

		// Process from right to left so byte offsets stay valid
		for ($i = count($allMatches[0]) - 1; $i >= 0; $i--) {
			[$word, $offset] = $allMatches[0][$i];
			$activeFormat = $this->getActiveFormats(substr($message, 0, $offset));
			$firstChar = mb_substr($word, 0, 1);
			$rest = mb_substr($word, 1);
			$replacement = $firstChar . "§r" . $activeFormat . $rest;
			$message = substr_replace($message, $replacement, $offset, strlen($word));
		}

		return $message;
	}

	public function onDataPacketSend(DataPacketSendEvent $event): void
	{
		$packets = $event->getPackets();

		foreach ($packets as $pk) {
			if (!($pk instanceof TextPacket))
				continue;

			$pk->message = $this->unfilter($pk->message);

			foreach ($pk->parameters as $k => $param) {
				$pk->parameters[$k] = $this->unfilter($param);
			}
		}
	}
}
