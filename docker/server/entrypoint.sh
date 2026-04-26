#!/bin/sh
# =============================================================================
# NetworkMemories — Nomad (MGO2) Server Entrypoint
# Injects environment variables as JVM system properties
# =============================================================================
set -e

echo "[nomad] Starting Metal Gear Online 2 server..."
echo "[nomad] Server IP      : ${SERVER_IP}"
echo "[nomad] Web API URL    : ${WEB_API_INTERNAL_URL}"
echo "[nomad] Main port      : ${MGO2_PORT_MAIN:-10020}"
echo "[nomad] Game port      : ${MGO2_PORT_GAME:-10100}"
echo "[nomad] Relay ports    : ${MGO2_PORT_RELAY1:-10200} / ${MGO2_PORT_RELAY2:-10201}"

exec java \
  -Dserver.ip="${SERVER_IP}" \
  -Ddb.api.url="${WEB_API_INTERNAL_URL:-http://nomad-web/api}" \
  -Dserver.port.main="${MGO2_PORT_MAIN:-10020}" \
  -Dserver.port.game="${MGO2_PORT_GAME:-10100}" \
  -Dserver.port.relay1="${MGO2_PORT_RELAY1:-10200}" \
  -Dserver.port.relay2="${MGO2_PORT_RELAY2:-10201}" \
  -Dlog.level="${LOG_LEVEL:-INFO}" \
  -jar /app/nomad.jar "$@"
