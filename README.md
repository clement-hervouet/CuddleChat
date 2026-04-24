# CuddleChat
A simple web app to make to plushies cuddle on the distance

# CuddleChat — Déploiement

## Structure à placer
```
/etc/docker/volumes/caddy/site/peluches/
├── config/config.php
├── includes/auth.php
├── includes/db.php
├── actions/post_lettre.php
├── uploads/          ← doit être writable par le process PHP
├── home.php
└── sql.sql
```

## Base de données
Importer `sql.sql` dans le conteneur `caddy-db` :
```bash
docker exec -i caddy-db mysql -u$MYSQL_USER -p$MYSQL_PASSWORD < sql.sql
```

## Variables d'environnement
Le conteneur PHP/Caddy doit exposer :
- `MYSQL_USER`
- `MYSQL_PASSWORD`

## Permissions uploads
```bash
chmod 775 /etc/docker/volumes/caddy/site/peluches/uploads
```

## Caddyfile — route à ajouter
```
peluches.domain.fr {
    root * /srv/peluches
    php_fastcgi php:9000
    file_server
}
```
(adapter le nom de domaine et l'alias PHP selon votre config)

## Dépendances
- PHP 8.x avec extensions : pdo_mysql, fileinfo
- MySQL 8 (caddy-db)
- Session partagée avec le système login existant (même domaine)