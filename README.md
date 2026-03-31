# Uncensor

A plugin for PocketMine-MP that automatically bypasses Minecraft Bedrock's built-in client-side profanity filter.

<img width="1920" height="1080" alt="Screenshot" src="https://github.com/user-attachments/assets/04395bca-beb4-4472-98f7-551c100f908c" />


## Credits

This plugin is a fork of [**dktapps-pm-pl/Uncensor**](https://github.com/dktapps-pm-pl/Uncensor/) by [**@dktapps**](https://github.com/dktapps) - the original author and creator of the concept. All credit for the original idea and implementation goes to him.

## Background

Minecraft Bedrock Edition includes a client-side profanity filter that automatically censors specific words in chat messages received from the server. This filter is applied locally on the player's device, meaning it cannot be removed by server-side configuration - even if the server operator has no intention of censoring chat.

The original [Uncensor](https://github.com/dktapps-pm-pl/Uncensor/) plugin by dktapps solved this by inserting an invisible character (`U+FEFF`, Zero-Width No-Break Space) after the first letter of each filtered word. For example, `fuck` becomes `f​uck` (with a ZWNBSP between `f` and `u`). The idea was that the client's filter wouldn't match the modified text, while the invisible character would be visually imperceptible.

### The problem with invisible characters

Through testing, we discovered that Minecraft Bedrock's client either:
- Renders `U+FEFF` and similar invisible Unicode characters with visible width (appearing as a space), or
- The profanity filter normalizes/strips invisible characters before checking

This caused server messages to display as `f uck` instead of `fuck` - defeating the purpose of the plugin.

### The `§r` approach (this fork)

This fork replaces the invisible character technique with Minecraft's native **formatting reset code** (`§r`). Instead of `f​uck`, the word becomes `f§ruck`:

- The profanity filter does not normalize `§` format codes, so `f§ruck` is not recognized as a profanity word.
- `§r` is a zero-width formatting instruction (reset to default) - it produces no visible output.
- Formatting is preserved: the plugin tracks active color and style codes (e.g., `§a`, `§l`) before each word and re-applies them after the `§r`. For example, `§a§lfuck` becomes `§a§lf§r§a§luck` - visually identical.

### Other improvements over the original

| Feature | Original (dktapps) | This fork |
|---|---|---|
| Word breaker | `U+FEFF` (invisible char) | `§r` (format reset) |
| `TYPE_TRANSLATION` packets | Skipped | Processed (fixes `/say` and similar) |
| Formatting preservation | Not applicable | ✅ Tracks and restores `§` codes |
| Bundled word list | ❌ (must extract from game) | ✅ Included in resources |
| Join notification | ❌ | ✅ Shows censored word list on join |

## How to use

1. Drop the plugin into your `plugins/` folder.
2. Start the server - the bundled `profanity_filter.wlist` will be extracted automatically.
3. That's it! All outgoing chat messages will be processed to bypass the client-side filter.

### Customizing the word list

The word list is located at `plugin_data/Uncensor/profanity_filter.wlist` (one word per line). You can add, remove, or replace words as needed. Changes take effect on server restart.

## How it works - Technical details

### 1. Word list loading

On enable, the plugin reads `profanity_filter.wlist` and builds a single regex pattern that matches any of the listed words (case-insensitive, Unicode-aware):
```
/(word1|word2|word3|...)/iu
```

### 2. The `unfilter()` method

For each outgoing text message, the plugin:
1. Finds all profanity word matches with their byte positions (`PREG_OFFSET_CAPTURE`)
2. Processes matches **from right to left** (so byte offsets remain valid as the string grows)
3. For each match:
   - Scans the text *before* the match to determine active formatting (color + styles)
   - Inserts `§r` + the active formatting codes after the first character
   - Example: `§aHello fuck` → `§aHello f§r§auck`

### 3. Packet interception

The `onDataPacketSend` handler intercepts all outgoing `TextPacket`s (chat, system, raw, translation, etc.) and runs `unfilter()` on both the message body and its parameters.

## Will it interfere with other chat plugins?

This plugin operates directly on `TextPacket` as it is being sent to the client. Most chat filter plugins process messages **before** they reach the packet stage, so by the time Uncensor sees the message, any server-side filtering has already been applied. It is therefore unlikely to interfere with existing plugins.

## License

This project is licensed under the [GNU General Public License v3.0](LICENSE).
