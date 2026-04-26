#!/bin/sh
# Generates dnsmasq config from environment, then starts.
# NOTE: DNS redirection works for real PS3 hardware only.
# RPCS3 requires a patched plugin.sprx — see docs/rpcs3-plugin-blocker.md
set -e

: "${SERVER_IP:?SERVER_IP is required}"

cat > /etc/dnsmasq.conf <<EOF
# NetworkMemories — MGO2 DNS
# Auto-generated from environment — do not edit manually.
# ⚠ This only affects real PS3 hardware.
# RPCS3 users need a patched plugin.sprx (see docs/rpcs3-plugin-blocker.md)

no-resolv
no-hosts
keep-in-foreground
log-queries

# MGO2 konami domains → SERVER_IP
address=/konami.net/${SERVER_IP}
address=/mgo.konami.com/${SERVER_IP}
address=/mg.mgo.konami.com/${SERVER_IP}

# Fallback DNS
server=8.8.8.8
server=8.8.4.4
EOF

echo "[dns] Resolving MGO2 domains → ${SERVER_IP}"
echo "[dns] NOTE: DNS redirect only works for real PS3. RPCS3 needs patched plugin.sprx"
exec dnsmasq
