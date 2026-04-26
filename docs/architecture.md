# Architecture — MGO2 Nomad Server

## Overview

The Nomad server is composed of three layers that work together:

```
PS3 / RPCS3 Client
       │
       ▼
  [DNS Container]          ← resolves MGO2 domains → SERVER_IP
       │
       ├──► [Web API / PHP] ← account creation, session auth, HTTP endpoints
       │         │
       │         ▼
       │    [MySQL DB]      ← accounts, sessions, player data
       │
       └──► [Nomad Java Server (Netty)]
                 │          ← game lobby, matchmaking, relay
                 ▼
            [MySQL DB]      (same DB, shared)
```

---

## Component Details

### 1. DNS Container (dnsmasq)

Resolves MGO2-specific hostnames to `SERVER_IP`.

Captured domains:
```
*.konami.net           → SERVER_IP
*.mgo.konami.com       → SERVER_IP
mg.mgo.konami.com      → SERVER_IP  (main entry point)
```

> ⚠️ DNS redirect works for **real PS3** hardware only.
> For RPCS3, see `docs/rpcs3-plugin-blocker.md`.

---

### 2. Web API (PHP + Apache)

Handles HTTP endpoints that both the PS3 client and the Nomad Java server call.

Key endpoints:

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/account/create-mgo2` | Create account |
| `POST` | `/account/login` | Authenticate, return session token |
| `GET` | `/account/check` | Validate session token |
| `POST` | `/account/delete` | Delete account |
| `GET` | `/admin/` | Admin panel |

#### Account Constraints

These are **enforced by the MGO2 client** — they cannot be changed without
modifying the game binary:

| Field | Constraint |
|---|---|
| Username | 8–32 characters, **lowercase + digits only** |
| Password | 4–16 characters, **digits only** |

---

### 3. Nomad Java Server (Netty NIO)

The core game server written in Java using Netty for non-blocking I/O.

**Ports:**

| Port | Protocol | Purpose |
|---|---|---|
| 10020 | TCP | Main auth / lobby entry point |
| 10100 | TCP | Game server (room management) |
| 10200 | TCP | UDP relay 1 |
| 10201 | TCP | UDP relay 2 |

**game_id:** `mgo2`  
**Adapter:** `MGO2Adapter`

The Nomad server calls the Web API internally for authentication — it does not
directly connect to MySQL. This design (from the original repo) keeps the
game logic decoupled from the database schema.

```
Nomad Java ──HTTP──► Web API ──MySQL──► Database
```

---

### 4. MySQL Database

Shared between the Web API and (indirectly) the Nomad server.

Key tables:
- `accounts` — usernames, hashed passwords, creation date
- `sessions` — active session tokens, expiry
- `players` — game-specific stats, rank, gear
- `rooms` — active game rooms / lobbies

---

## Connection Flow (PS3 Hardware)

```
1. PS3 network config: DNS = SERVER_IP
2. PS3 resolves mg.mgo.konami.com → SERVER_IP  (via dnsmasq)
3. PS3 connects to SERVER_IP:10020
4. Nomad server challenges PS3 for credentials
5. Nomad calls Web API: POST /account/login {username, password_hash}
6. Web API verifies against DB → returns session token
7. Nomad hands session token to PS3
8. PS3 enters lobby on port 10100
9. Game rooms served via 10200/10201 relay
```

## Connection Flow (RPCS3)

```
1. RPCS3 network config: DNS = SERVER_IP
2. RPCS3 loads SaveMGO plugin.sprx
3. ⛔ plugin.sprx IGNORES DNS and connects to hardcoded SaveMGO address
4. Connection never reaches our Nomad server

→ Blocked until plugin.sprx is patched and recompiled.
   See docs/rpcs3-plugin-blocker.md
```

---

## Port Forwarding Reference

If your server is behind a NAT/router, forward these ports to the server:

| Port | Protocol | Required for |
|---|---|---|
| 53 | TCP + UDP | DNS (PS3 clients) |
| 80 | TCP | Web API (HTTP) |
| 443 | TCP | Web API (HTTPS) |
| 10020 | TCP | MGO2 auth/lobby |
| 10100 | TCP | MGO2 game server |
| 10200 | TCP | MGO2 relay 1 |
| 10201 | TCP | MGO2 relay 2 |
| 8080 | TCP | Admin panel (optional, can restrict) |
