# Metal Gear Online 2 — Docker Server

> **NetworkMemories** fork of [GHzGangster/Nomad](https://github.com/GHzGangster/Nomad)  
> Fully dockerized MGO2 server emulator (PS3 / RPCS3)

---

## ⚠️ RPCS3 Client Redirect — Work in Progress

> **Real PS3 hardware: fully functional ✅**  
> **RPCS3 emulator: blocked 🚧 — fix in progress**

DNS redirection works correctly for real PS3 hardware. However, RPCS3 uses
the **SaveMGO plugin** (`plugin.sprx`) which **hardcodes** the SaveMGO server
address and ignores DNS entirely.

**Fixing this requires recompiling `plugin.sprx` with a patched address.**
This work is currently in progress.

→ **[Full details, current status & technical explanation](docs/rpcs3-plugin-blocker.md)**

---

## What's new in this fork

- ✅ Full Docker setup — zero manual Java installs
- ✅ All config via `.env` — no hardcoded IPs
- ✅ Decoupled from SaveMGO web API — self-hosted PHP API included
- ✅ MySQL schema included (`sql/schema.sql`)
- ✅ Integrated dnsmasq DNS container
- ✅ Admin panel at `/admin` (player management, RPCS3 status indicator)
- ✅ Backup & restore scripts
- ✅ Full documentation (architecture, RPCS3 blocker, build instructions)
- ✅ `.bin` test files moved to `test/fixtures/` (root kept clean)

---

## Requirements

- Docker ≥ 24 + Docker Compose v2
- Linux server (or WSL2) with a public IP
- PS3 with MGO2 disc **or** RPCS3 (see RPCS3 note above)

---

## Quick Start

```bash
# 1. Clone your fork
git clone https://github.com/NetworkMemories/nomad-docker.git
cd nomad-docker

# 2. Initialize
make init

# 3. Edit .env — required:
#    SERVER_IP, MYSQL_ROOT_PASSWORD, MYSQL_PASSWORD, ADMIN_PASSWORD, ADMIN_SECRET
nano .env

# 4. Build
make build

# 5. (Linux) Free port 53
make disable-systemd-resolved

# 6. Start
make run-daemon

# 7. Configure PS3 DNS → SERVER_IP
```

---

## Key `.env` Variables

| Variable | Description | Default |
|---|---|---|
| `SERVER_IP` | Your public server IP | **required** |
| `MYSQL_ROOT_PASSWORD` | MySQL root password | **required** |
| `MYSQL_PASSWORD` | App DB password | **required** |
| `ADMIN_PASSWORD` | Admin panel password | **required** |
| `ADMIN_SECRET` | Random secret token | **required** |
| `MGO2_PORT_MAIN` | Auth/lobby port | `10020` |
| `LOG_LEVEL` | INFO / DEBUG | `INFO` |

---

## Account Constraints

These are **enforced by the MGO2 client** and cannot be changed without
modifying the game binary:

| Field | Rule |
|---|---|
| Username | 8–32 characters, **lowercase + digits only** |
| Password | 4–16 characters, **digits only** |

---

## Ports

| Port | Purpose |
|---|---|
| 53 | DNS (UDP+TCP) |
| 80/443 | Web API + Admin panel |
| 10020 | MGO2 auth/lobby |
| 10100 | MGO2 game server |
| 10200 | MGO2 relay 1 |
| 10201 | MGO2 relay 2 |

---

## Admin Panel

`http://YOUR_SERVER_IP/admin`  
Login with `ADMIN_USER` / `ADMIN_PASSWORD` from `.env`

Features:
- Player list (kick, ban, unban, delete)
- Server status per port
- RPCS3 plugin status banner with link to docs

---

## `make` Commands

```
make help                     List all commands (with RPCS3 reminder)
make init                     First-time setup
make build                    Build all Docker images
make run-daemon               Start everything (background)
make stop                     Stop services
make logs                     Follow all logs
make shell-db                 MySQL shell
make backup                   Backup DB + data
make restore                  Restore latest backup
make disable-systemd-resolved Free port 53 (Linux)
```

---

## Documentation

| File | Content |
|---|---|
| [`docs/rpcs3-plugin-blocker.md`](docs/rpcs3-plugin-blocker.md) | 🚧 Full RPCS3 blocker analysis, fix status, tools |
| [`docs/architecture.md`](docs/architecture.md) | Server components, connection flows, port reference |
| [`docs/building.md`](docs/building.md) | Build from source, Maven, source patches applied |
| [`docs/troubleshooting.md`](docs/troubleshooting.md) | Common issues and fixes |

---

## Credits

- Original server: [GHzGangster/Nomad](https://github.com/GHzGangster/Nomad)
- SaveMGO community: [savemgo.com](https://savemgo.com)
- This fork: [NetworkMemories](https://network-memories.com)

## License

AGPL-3.0 (inherited from original project)
