# Troubleshooting — MGO2 Nomad

## RPCS3 ignores DNS / connects to SaveMGO instead

This is the known blocker. See [`docs/rpcs3-plugin-blocker.md`](rpcs3-plugin-blocker.md).  
Short answer: the SaveMGO `plugin.sprx` hardcodes its server address.
DNS redirect has no effect on it. Fix = recompile the plugin. In progress.

---

## PS3 hardware can't resolve MGO2 domains

1. Check DNS is running: `docker ps | grep nomad-dns`
2. Test: `nslookup mg.mgo.konami.com YOUR_SERVER_IP`
3. Port 53 conflict: `sudo ss -tulnp | grep :53` → `make disable-systemd-resolved`
4. Check logs: `make logs-dns`

---

## Nomad server won't start — DB connection refused

1. MySQL needs ~15s to initialize on first boot. Wait then check:
   ```bash
   make logs | grep nomad-mysql
   docker ps | grep nomad-mysql
   ```
2. Credentials changed after first run → wipe volume:
   ```bash
   make backup && make down-volumes && make run-daemon
   ```

---

## Web API returning 503

1. Check `nomad-web` is running: `docker ps | grep nomad-web`
2. Verify DB connection from web container:
   ```bash
   docker exec nomad-web php -r "new PDO('mysql:host=nomad-mysql;dbname=nomad', 'USER', 'PASS');"
   ```
3. Check logs: `make logs-web`

---

## Account creation fails

- Username must match: `^[a-z0-9]{8,32}$` (lowercase + digits, 8-32 chars)
- Password must match: `^[0-9]{4,16}$` (digits only, 4-16 chars)
- These constraints come from the MGO2 client — they cannot be relaxed server-side

---

## Admin panel not loading

1. Ensure `ADMIN_PASSWORD` and `ADMIN_SECRET` are set in `.env`
2. Check `nomad-web` logs: `make logs-web`
3. Firewall: ensure port 80 is open from your IP
