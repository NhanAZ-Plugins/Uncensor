# Uncensor

A plugin for PocketMine-MP that automatically bypasses Minecraft Bedrock's built-in client-side profanity filter.

<img width="1920" height="1080" alt="Screenshot" src="https://github.com/user-attachments/assets/04395bca-beb4-4472-98f7-551c100f908c" />

## How to use

1. Drop the plugin into your `plugins/` folder.
2. Start the server - the bundled `profanity_filter.wlist` will be extracted automatically.
3. That's it! All outgoing chat messages will be processed to bypass the client-side filter.

### Customizing the word list

The word list is located at `plugin_data/Uncensor/profanity_filter.wlist` (one word per line). You can add, remove, or replace words as needed. Changes take effect on server restart.

## Will it interfere with other chat plugins?

This plugin operates directly on `TextPacket` as it is being sent to the client. Most chat filter plugins process messages **before** they reach the packet stage, so by the time Uncensor sees the message, any server-side filtering has already been applied. It is therefore unlikely to interfere with existing plugins.

## Credits

This plugin is a fork of [**dktapps-pm-pl/Uncensor**](https://github.com/dktapps-pm-pl/Uncensor/) by [**@dktapps**](https://github.com/dktapps) - the original author and creator of the concept. All credit for the original idea and implementation goes to him.

## License

This project is licensed under the [GNU General Public License v3.0](LICENSE).
