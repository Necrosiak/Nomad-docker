# 🚧 RPCS3 Client Redirect — Current Blocker & Status

## Summary

> **Status: Work in progress**  
> DNS redirection alone is **not enough** to redirect RPCS3 clients to a
> custom MGO2 server. The root cause is a hardcoded server address inside the
> SaveMGO RPCS3 plugin (`plugin.sprx`). Fixing this requires recompiling the
> plugin from source with a patched address.  
> **This work is currently in progress.**

---

## Background

Metal Gear Online 2 runs on PS3. The PS3 version can be emulated on PC using
**RPCS3**. However, MGO2 online requires a plugin to handle network
authentication and session management — this plugin is called **SaveMGO plugin**
(`plugin.sprx`), developed by the SaveMGO community.

The plugin is loaded by RPCS3 at launch and acts as a middleware between the
game and the network server.

---

## The Problem

The SaveMGO plugin (`plugin.sprx`) **hardcodes** the address of SaveMGO's own
server directly in its binary:

```
Hardcoded target: savemgo.com (or its IP)
```

This means that even if you:
- Redirect DNS so that `savemgo.com` resolves to your own server ✅
- Configure RPCS3's network settings to use your DNS ✅
- Run your own Nomad server correctly ✅

...the plugin will **ignore all of that** and connect directly to the hardcoded
address, bypassing DNS entirely.

This is not a bug — it was intentionally designed this way by SaveMGO to prevent
unauthorized server forks from easily intercepting their users' connections.

---

## Why DNS Redirect Is Not Enough

Standard DNS-based redirection works like this:
```
Client asks DNS: "What is the IP of savemgo.com?"
DNS answers:     "It's YOUR_SERVER_IP"
Client connects: YOUR_SERVER_IP ✅
```

But the SaveMGO plugin does this instead:
```
Plugin ignores DNS entirely
Plugin connects directly to: [hardcoded IP or hostname resolved at compile time]
Result: YOUR_SERVER_IP never gets used ❌
```

This is different from how PS3 hardware works — on real PS3, the game uses
standard network stack and DNS redirection works fine. The blocker is
**specific to RPCS3 + the SaveMGO plugin**.

---

## The Fix: Recompile the Plugin

The only way to redirect RPCS3 clients to a custom server is to:

1. **Obtain the plugin source code** — the SaveMGO plugin is not fully public,
   but the relevant network portions can be identified via binary analysis
2. **Patch the hardcoded address** — replace `savemgo.com` / hardcoded IP
   with your own `SERVER_IP` or a configurable environment variable
3. **Recompile for PS3/Cell architecture** — the plugin targets the PS3's
   Cell Broadband Engine (PPU), requiring the PS3 SDK or open-source
   toolchain (`ps3toolchain` / `ppu-lv2`)
4. **Package as `.sprx`** — PS3 plugin format (Relocatable ELF for Cell)
5. **Distribute to users** — users replace their `plugin.sprx` with the
   patched version in their RPCS3 plugin directory

---

## Current Progress

| Step | Status |
|---|---|
| Identify hardcoded address in binary | ✅ Confirmed |
| Locate relevant source / reconstruct patch | 🔄 In progress |
| Set up PS3 cross-compilation toolchain | 🔄 In progress |
| Compile patched `.sprx` | ⏳ Pending |
| Test with RPCS3 + Nomad server | ⏳ Pending |
| Document installation for users | ⏳ Pending |

---

## Workaround: Real PS3 Hardware

On a **real PS3**, the MGO2 client does NOT use the SaveMGO plugin —
it uses the game's native network stack. In this case:

- DNS redirection **works correctly** ✅
- Configure your PS3's Primary DNS → `YOUR_SERVER_IP`
- The Nomad server handles authentication and lobby normally

So the server is already functional for **real PS3 hardware**.
The RPCS3 emulator blocker only affects **PC emulation**.

---

## Tools & Resources

### PS3 Cross-Compilation Toolchain

To compile `.sprx` plugins for PS3:

```bash
# Option 1: ps3toolchain (open source)
# https://github.com/ps3dev/ps3toolchain
git clone https://github.com/ps3dev/ps3toolchain.git
cd ps3toolchain && ./toolchain.sh

# Compiler: ppu-lv2-gcc (targets Cell PPU)
# Linker produces .elf → convert to .sprx with make_fself
```

### RPCS3 Plugin Format

- `.sprx` = Relocatable ELF (SELF) for Cell Broadband Engine
- Loaded via RPCS3's LLE module system
- Can be configured in: RPCS3 → Config → Advanced → Load Libraries

### Binary Analysis

To find the hardcoded address in an existing `plugin.sprx`:
```bash
# Extract strings
strings plugin.sprx | grep -E "savemgo|[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+"

# Or use a hex editor / Ghidra with PS3 plugin (PPC BE architecture)
```

---

## What Users Can Expect

Once the patched plugin is ready:

1. Download `plugin.sprx` (NetworkMemories build) from the releases page
2. Place in RPCS3's `dev_hdd0/game/BLUS30346/USRDIR/` or equivalent
3. In RPCS3: Config → Advanced → Load Libraries → add the plugin
4. Set DNS in RPCS3 network settings → `YOUR_SERVER_IP`
5. Launch MGO2 — it will connect to the NetworkMemories Nomad server

---

## Following Progress

Watch this repository for updates:  
`https://github.com/NetworkMemories/nomad-docker`

Updates will be committed to:
- `docs/rpcs3-plugin-blocker.md` (this file) — status updates
- `releases/` — compiled `.sprx` when ready
- `tools/plugin-patch/` — patch source and build instructions
