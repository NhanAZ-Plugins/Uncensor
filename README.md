# UncensorAZ

UncensorAZ inserts Minecraft formatting resets into listed words in outgoing Bedrock text packets. This can alter how the client applies its built-in text filter while preserving the visible text. The result depends on the Bedrock client and has not been verified with a connected client in this maintenance campaign.

## Requirements and compatibility

- The manifest declares PocketMine-MP API 4.0.0 and Axolotl-PM API 5.0.0. The formatter tests and PHPStan at maximum level pass against Axolotl-PM 5.0.0 and 5.49.1 source. API 4 and intermediate 5.x releases have not been tested here.
- PHP 8.1 or newer is required by the tested Axolotl-PM 5.x source revisions.
- No external plugin or runtime library is used by the active code path. The unused `libRegRsp` import does not register the bundled resource pack.

The existing 0.0.1 release PHAR passed an isolated startup and word list extraction test on Axolotl-PM 5.49.1. This does not prove the client displays every word as intended.

## Installation and word list

Download the PHAR from [GitHub Releases](https://github.com/NhanAZ-Plugins/Uncensor/releases), place it in the server's `plugins` directory, and restart Axolotl-PM. On first startup, the plugin copies its word list to `plugins/UncensorAZ/profanity_filter.wlist` when the server uses the default plugins directory. If that directory is configured elsewhere, find the file in UncensorAZ's data directory there.

Put one entry on each line of the word list, then restart to apply edits. Entries are matched without regard to letter case, including when they appear inside longer words. An empty list leaves outgoing text unchanged. The plugin has no command or permission.

The formatter runs on outgoing `TextPacket` messages and parameters after earlier server plugins may have changed them. It cannot restore text already removed by another plugin. The `Uncensor Pack` directory is kept for project history but is not registered or required by the active implementation.

## Build and verification

The [build workflow](.github/workflows/build.yml) tests formatting behavior, runs PHPStan at maximum level and uses [DevTools](https://github.com/NhanAZ/DevTools) to build and inspect a downloadable PHAR. The workflow pins DevTools and Axolotl-PM source to reviewed commits. It does not create a stable release on each push.

Run `php tests/formatting.php /path/to/Axolotl-PM` with installed Axolotl-PM dependencies to check the visible text and formatting behavior locally. For a local PHAR build, follow the [DevTools CLI guide](https://github.com/NhanAZ/DevTools/blob/main/docs/cli.md). A passing formatter fixture or server boot does not prove a client-side filter result.

## Support, upstream and license

UncensorAZ is a fork of [dktapps-pm-pl/Uncensor](https://github.com/dktapps-pm-pl/Uncensor/) by [dktapps](https://github.com/dktapps). The original author and concept are credited here, and the existing [LGPL-3.0 license](LICENSE) is retained. Report issues in [GitHub Issues](https://github.com/NhanAZ-Plugins/Uncensor/issues) or contact [NhanAZ Discord](https://discord.gg/j2X83ujT6c).
