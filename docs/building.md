# Building Nomad from Source

## Requirements

- Java 8 JDK (required — the original code targets Java 8)
- Maven 3.x (build system defined in `pom.xml`)
- Docker ≥ 24 (for containerized build)

> The Docker build handles everything automatically via the Dockerfile.
> Manual build instructions below are for development / debugging only.

---

## Automatic (Docker — recommended)

```bash
make build
# → Docker builds the JAR inside a Maven container, then packages it
#   into a slim JRE-8 runtime image. No local Java install needed.
```

---

## Manual Build (for development)

```bash
# 1. Clone your fork
git clone https://github.com/NetworkMemories/nomad-docker.git
cd nomad-docker

# 2. Build with Maven
mvn clean package -DskipTests

# Output: target/nomad-*.jar

# 3. Run directly (for testing)
java \
  -Dserver.ip=127.0.0.1 \
  -Ddb.api.url=http://localhost/api \
  -Dserver.port.main=10020 \
  -Dserver.port.game=10100 \
  -Dserver.port.relay1=10200 \
  -Dserver.port.relay2=10201 \
  -jar target/nomad-*.jar
```

---

## Configuration via JVM System Properties

The `docker/server/entrypoint.sh` injects these from `.env` automatically:

| Property | Env Var | Description |
|---|---|---|
| `server.ip` | `SERVER_IP` | Public IP |
| `db.api.url` | `WEB_API_INTERNAL_URL` | Internal URL of the Web API |
| `server.port.main` | `MGO2_PORT_MAIN` | Auth/lobby port (default 10020) |
| `server.port.game` | `MGO2_PORT_GAME` | Game port (default 10100) |
| `server.port.relay1` | `MGO2_PORT_RELAY1` | Relay 1 (default 10200) |
| `server.port.relay2` | `MGO2_PORT_RELAY2` | Relay 2 (default 10201) |
| `log.level` | `LOG_LEVEL` | INFO / DEBUG / WARN / ERROR |

---

## Source Modifications Applied

### 1. Decouple from SaveMGO Web API

The original Nomad is hardcoded to call SaveMGO's web API at `savemgo.com`.
We replace this with a configurable URL:

```java
// BEFORE (original):
String apiUrl = "https://savemgo.com/api";

// AFTER (patched):
String apiUrl = System.getProperty("db.api.url", "http://localhost/api");
```

### 2. Bind to 0.0.0.0

Netty server must bind to `0.0.0.0` to be reachable inside Docker:

```java
// BEFORE:
bootstrap.bind("localhost", port);

// AFTER:
bootstrap.bind("0.0.0.0", port);
```

### 3. Port configuration via system properties

```java
int mainPort  = Integer.parseInt(System.getProperty("server.port.main",  "10020"));
int gamePort  = Integer.parseInt(System.getProperty("server.port.game",  "10100"));
int relay1    = Integer.parseInt(System.getProperty("server.port.relay1", "10200"));
int relay2    = Integer.parseInt(System.getProperty("server.port.relay2", "10201"));
```

---

## .bin Files in Repository Root

The original Nomad repo contains many `.bin` files at the root
(`gameinfo.bin`, `gamelist.bin`, `friends-list.bin`, etc.).

These are **binary test/seed data files** used during development to simulate
PS3 network packets. They are not required for running the server in production.

In this fork, they have been moved to `test/fixtures/` to keep the root clean.
