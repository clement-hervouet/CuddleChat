# CuddleChat — Setup

## Prérequis

### Système
- Docker + Docker Compose
- Réseau Docker `caddy_default` existant
- Port 80 et 443 ouverts
- Port 25 ouvert (envoi mail)

### Conteneurs requis
| Conteneur | Image | Rôle |
|---|---|---|
| `caddy-caddy-1` | caddy custom | Reverse proxy |
| `caddy-php-1` | php custom | Exécution PHP |
| `caddy-db` | mysql:latest | Base de données |
| `postfix` | boky/postfix | Envoi mail |

---

## Base de données

### Importer le schéma
```bash
sudo docker exec -i caddy-db mysql -u root -p < setup/sql.sql
```

### Importer l'utilisateur restreint
```bash
sudo docker exec -i caddy-db mysql -u root -p < setup/user.sql
```

> Remplacer `MOT_DE_PASSE_FORT` dans `user.sql` avant import.

### Bases utilisées
| Base | Rôle |
|---|---|
| `users_base` | Comptes utilisateurs (existant) |
| `cuddlechat_base` | Lettres (nouveau) |

### Utilisateur SQL restreint
- **User** : `app.cuddlechat`
- **Droits** : `SELECT, INSERT, UPDATE, DELETE` sur `cuddlechat_base` uniquement

---

## Variables d'environnement

À injecter dans le service PHP du `compose.yml` :

```yaml
environment:
  CUDDLECHAT_DB_USER: "app.cuddlechat"
  CUDDLECHAT_DB_PASSWORD: "MOT_DE_PASSE_FORT"
  CUDDLECHAT_MAIL_USER_6: "adresse_destinataire_user6@example.com"
  CUDDLECHAT_MAIL_USER_7: "adresse_destinataire_user7@example.com"
```

---

## Permissions

### Dossier uploads
```bash
sudo chmod 777 /etc/docker/volumes/caddy/site/CuddleChat/uploads
```

---

## Dockerfile PHP

Extensions et outils requis :

```dockerfile
# WebP + GD
RUN apt-get update && apt-get install -y libwebp-dev zlib1g-dev libjpeg-dev libpng-dev \
    && docker-php-ext-configure gd --with-webp --with-jpeg \
    && docker-php-ext-install gd \
    && rm -rf /var/lib/apt/lists/*

# Envoi mail via Postfix
RUN apt-get update && apt-get install -y msmtp msmtp-mta && rm -rf /var/lib/apt/lists/*
COPY msmtprc /etc/msmtprc
RUN chmod 644 /etc/msmtprc

# Logs PHP
RUN echo "log_errors = On" >> /usr/local/etc/php/php.ini \
    && echo "error_log = /var/log/php/error.log" >> /usr/local/etc/php/php.ini \
    && echo "display_errors = Off" >> /usr/local/etc/php/php.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/php.ini \
    && mkdir -p /var/log/php && chmod 777 /var/log/php
```

### msmtprc
```
defaults
auth           off
tls            off
logfile        /var/log/msmtp.log

account        postfix
host           postfix
port           25
from           noreply@clement-hervouet.fr

account default : postfix
```

---

## Postfix

### compose.yml
```yaml
services:
  postfix:
    image: boky/postfix
    container_name: postfix
    restart: unless-stopped
    environment:
      HOSTNAME: clement-hervouet.fr
      ALLOWED_SENDER_DOMAINS: clement-hervouet.fr
      RELAYHOST: "[smtp.gmail.com]:587"
      RELAYHOST_USERNAME: clement.hervouet.fr@gmail.com
      RELAYHOST_PASSWORD: "xxxx xxxx xxxx xxxx"
    networks:
      - caddy_default

networks:
  caddy_default:
    external: true
```

> `RELAYHOST_PASSWORD` = mot de passe d'application Google (pas le mot de passe du compte).

### Générer un mot de passe d'application Google
1. Activer la validation en deux étapes sur le compte Gmail
2. Mon compte Google → Sécurité → Mots de passe des applications
3. Générer pour "Mail" + "Autre"

---

## Caddyfile

```caddy
lettre.clement-hervouet.fr {
    root * /srv/CuddleChat

    php_fastcgi php:9000

    file_server

    @static {
        path *.css *.js *.png *.jpg *.jpeg *.webp *.svg *.ico *.woff *.woff2
    }
    header @static Cache-Control "public, max-age=2592000, immutable"

    @uploads {
        path /uploads/*
    }
    header @uploads Cache-Control "public, max-age=604800"
}
```

---

## DNS OVH

| Type | Nom | Valeur |
|---|---|---|
| SPF | `@` | `v=spf1 include:_spf.google.com -all` |

---

## Comptes utilisateurs

Les peluches correspondent aux `id_user` **6** et **7** dans `users_base`.
- `CUDDLECHAT_MAIL_USER_6` = adresse notifiée quand l'utilisateur 6 poste
- `CUDDLECHAT_MAIL_USER_7` = adresse notifiée quand l'utilisateur 7 poste

---

## Logs

```bash
# Erreurs PHP
sudo docker exec caddy-php-1 tail -f /var/log/php/error.log

# Actions applicatives
sudo docker exec caddy-php-1 tail -f /var/log/php/cuddlechat.log

# Postfix
sudo docker logs postfix
```