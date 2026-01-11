# 🚀 Deployment Guide - Stap voor Stap

## Voorbereiding

### 1. GitHub Repository Aanmaken

1. Ga naar https://github.com/new
2. Maak een nieuwe repository aan (bijv. `pokemon-tracker`)
3. Maak het repository **public** of **private** (jouw keuze)

### 2. Code naar GitHub Pushen

Open PowerShell in `c:\pokemon-tracker`:

```powershell
# Initialiseer git (als nog niet gedaan)
git init

# Voeg alle bestanden toe
git add .

# Eerste commit
git commit -m "Initial commit: Pokemon Tracker met sociale features"

# Koppel aan GitHub (vervang jouw-username!)
git branch -M main
git remote add origin https://github.com/jouw-username/pokemon-tracker.git

# Push naar GitHub
git push -u origin main
```

## Portainer Deployment

### Stap 1: Log in op Portainer

- Open je NAS Portainer: `http://nas-ip:9000`
- Log in met je credentials

### Stap 2: Nieuwe Stack Maken

1. Klik in het menu op **Stacks**
2. Klik op **+ Add stack**
3. Geef de stack een naam: `pokemon-tracker`

### Stap 3: Repository Configureren

Kies **Repository** als bouwmethode en vul in:

- **Repository URL**: `https://github.com/jouw-username/pokemon-tracker`
- **Repository reference**: `refs/heads/main`
- **Compose path**: `docker-compose.prod.yml`

Als je repository private is:
- Klik op **Authentication**
- Vul je GitHub username en Personal Access Token in

### Stap 4: Environment Variables

Scroll naar **Environment variables** en voeg deze toe:

| Variable | Voorbeeld Waarde | Opmerking |
|----------|-----------------|-----------|
| `HTTP_PORT` | `8280` | Externe poort |
| `MYSQL_ROOT_PASSWORD` | `SuperVeiligWachtwoord123!` | Verander dit! |
| `MYSQL_DATABASE` | `pokemon_cards` | Database naam |
| `MYSQL_USER` | `pokemon_user` | Database gebruiker |
| `MYSQL_PASSWORD` | `VeiligDBWachtwoord456!` | Verander dit! |
| `VITE_API_URL` | `http://192.168.1.100:8280/api` | Vervang IP! |

**BELANGRIJK**: 
- Vervang `192.168.1.100` met het **daadwerkelijke IP van je NAS**
- Gebruik **sterke wachtwoorden** (geen voorbeelden overnemen!)

### Stap 5: Deploy!

1. Klik onderaan op **Deploy the stack**
2. Portainer gaat nu:
   - De code van GitHub clonen
   - Alle Docker images bouwen
   - De containers starten
   - De database initialiseren

Dit duurt **3-5 minuten**.

### Stap 6: Controleren

1. Klik op de stack naam `pokemon-tracker`
2. Je ziet 4 containers:
   - ✅ `pokemon_nginx` - Webserver
   - ✅ `pokemon_backend` - PHP API
   - ✅ `pokemon_db` - MySQL Database
   - ✅ `pokemon_frontend` - Vue build container (kan stopped zijn, dat is OK)

3. Check de logs:
   - Klik op een container
   - Klik op **Logs**
   - Controleer of er geen errors zijn

### Stap 7: Testen

Open in je browser: `http://nas-ip:8280`

Je zou nu de login pagina moeten zien!

## Updates Deployen

### Via Portainer (Automatisch)

Na elke `git push` naar GitHub:

1. Ga naar **Stacks** → `pokemon-tracker`
2. Klik op **Pull and redeploy**
3. Portainer haalt automatisch de laatste code en herstart

### Via CLI (Handmatig)

SSH naar je NAS:

```bash
cd /volume1/docker/pokemon-tracker  # Pas pad aan
git pull
docker-compose -f docker-compose.prod.yml up -d --build
```

## SSL/HTTPS Configureren (Optioneel maar aangeraden)

### Optie 1: Nginx Proxy Manager (Simpelst)

1. Installeer Nginx Proxy Manager via Portainer
2. Voeg een Proxy Host toe:
   - Domain: `pokemon.jouwdomain.com`
   - Forward to: `pokemon_nginx` poort `80`
   - Enable SSL: Let's Encrypt
3. Update `VITE_API_URL` naar: `https://pokemon.jouwdomain.com/api`
4. Redeploy de stack

### Optie 2: Synology/QNAP Reverse Proxy

Configureer in de NAS interface:
- Source: `pokemon.lokaal.nl` op poort 443
- Destination: `localhost:8280`

## Backup Instellen

### Handmatige Backup

```bash
# SSH naar NAS
ssh admin@nas-ip

# Database backup
docker exec pokemon_db mysqldump -u root -p pokemon_cards > backup.sql

# Volume backup
docker run --rm -v pokemon-tracker_db_data:/data -v $(pwd):/backup alpine tar czf /backup/db_backup.tar.gz /data
```

### Automatische Backup (Cron)

```bash
# Bewerk crontab
crontab -e

# Voeg toe (dagelijks om 3:00 AM):
0 3 * * * docker exec pokemon_db mysqldump -u root -pJOUW_PASSWORD pokemon_cards > /volume1/backups/pokemon-$(date +\%Y\%m\%d).sql
```

## Troubleshooting

### "502 Bad Gateway"

```bash
# Controleer logs
docker logs pokemon_nginx
docker logs pokemon_backend

# Herstart containers
docker restart pokemon_nginx pokemon_backend
```

### "Database connection failed"

```bash
# Check database
docker exec pokemon_db mysqladmin ping -h localhost -u root -p

# Check environment variables in Portainer
```

### "Frontend laadt niet"

```bash
# Check build logs
docker logs pokemon_frontend

# Verify VITE_API_URL is correct
# Redeploy met correcte URL
```

### Container start niet

```bash
# Bekijk alle logs
docker logs pokemon_nginx
docker logs pokemon_backend
docker logs pokemon_db
docker logs pokemon_frontend
```

## Veelgestelde Vragen

**Q: Kan ik de poort wijzigen?**
A: Ja, pas `HTTP_PORT` aan in environment variables en redeploy.

**Q: Hoe reset ik de database?**
A: Stop de stack, verwijder het `db_data` volume, start opnieuw.

**Q: Kan ik meerdere instances draaien?**
A: Ja, maak een nieuwe stack met andere poort en database naam.

**Q: Hoe maak ik een admin account?**
A: Registreer via de UI, of voeg direct toe in database:
```sql
INSERT INTO users (username, password, display_name, is_public) 
VALUES ('admin', '$2y$10$...', 'Administrator', 1);
```

**Q: Draait dit ook op Raspberry Pi?**
A: Ja! Gebruik ARM-compatible images (vaak automatisch).

## Support

Bij problemen:
1. Check eerst de logs
2. Controleer environment variables
3. Test database connectie
4. Maak een GitHub issue aan

## Volgende Stappen

Na succesvolle deployment:

1. ✅ Maak een admin account
2. ✅ Test alle functionaliteit
3. ✅ Configureer SSL/HTTPS
4. ✅ Setup automatische backups
5. ✅ Voeg vrienden toe en test chat
6. ✅ Begin je collectie toe te voegen!

---

**Veel succes met je deployment! 🚀**
